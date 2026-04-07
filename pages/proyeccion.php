<?php
require_once __DIR__ . '/../config/auth.php';
requireLogin();
if (!canDo('puede_ver_proyeccion')) { include __DIR__ . '/403.php'; exit; }

$db    = getDB();
$cobro = cobroActivo();

$anioActual = (int)date('Y');
$mesActual  = (int)date('n'); // 1-12

// ── BASE ACTUAL ──────────────────────────────────────────────
$stmtCap = $db->prepare("SELECT COALESCE(SUM(saldo_actual),0) FROM v_saldo_cuentas WHERE cobro_id=?");
$stmtCap->execute([$cobro]);
$capitalDisponible = (float)$stmtCap->fetchColumn();

$base = $db->prepare("
    SELECT
        (SELECT COALESCE(SUM(saldo_pendiente),0) FROM prestamos
         WHERE cobro_id=? AND estado NOT IN ('pagado','renovado','refinanciado','anulado')) AS saldo_pendiente,
        (SELECT COALESCE(SUM(interes_calculado),0) FROM prestamos
         WHERE cobro_id=? AND estado NOT IN ('pagado','renovado','refinanciado','anulado')) AS intereses_activos,
        (SELECT COUNT(*) FROM prestamos
         WHERE cobro_id=? AND estado NOT IN ('pagado','renovado','refinanciado','anulado')) AS num_prestamos
");
$base->execute([$cobro, $cobro, $cobro]);
$b = $base->fetch();

// Tasa promedio real
$tasaQ = $db->prepare("
    SELECT COALESCE(AVG(interes_valor), 20)
    FROM prestamos
    WHERE cobro_id=? AND estado NOT IN ('pagado','renovado','refinanciado','anulado')
    AND tipo_interes='porcentaje'
");
$tasaQ->execute([$cobro]);
$tasaPromedio = round((float)$tasaQ->fetchColumn(), 1);

// ── ESCENARIOS ───────────────────────────────────────────────
$cobros3M = $db->prepare("
    SELECT COALESCE(AVG(total),0) FROM (
        SELECT SUM(monto) AS total FROM capital_movimientos
        WHERE cobro_id=? AND tipo='cobro_cuota' AND es_entrada=1 AND anulado=0
        AND fecha >= DATE_SUB(CURDATE(), INTERVAL 3 MONTH)
        GROUP BY DATE_FORMAT(fecha,'%Y-%m')
    ) t
");
$cobros3M->execute([$cobro]);
$ingresoMensualBase = (float)$cobros3M->fetchColumn();
$ingresoMensualBase = max($ingresoMensualBase, $b['intereses_activos'] / 12);

function proyectarMeses(float $capitalInicial, float $carteraInicial, float $tasaMensual, float $crecimiento, int $meses): array {
    $data    = [];
    $capital = $capitalInicial;
    $cartera = $carteraInicial;
    for ($i = 1; $i <= $meses; $i++) {
        $interes  = $cartera * ($tasaMensual / 100);
        $cartera  = $cartera * (1 + $crecimiento / 100);
        $capital += $interes;
        $data[]   = ['mes' => $i, 'capital' => round($capital), 'cartera' => round($cartera), 'interes' => round($interes)];
    }
    return $data;
}

$carteraBase = (float)$b['saldo_pendiente'];
$escenarios  = [
    'conservador' => proyectarMeses($capitalDisponible, $carteraBase, $tasaPromedio, 0,  12),
    'moderado'    => proyectarMeses($capitalDisponible, $carteraBase, $tasaPromedio, 10, 12),
    'agresivo'    => proyectarMeses($capitalDisponible, $carteraBase, $tasaPromedio, 20, 12),
];

// ════════════════════════════════════════════════════════════
// INTERÉS COMPUESTO — OPCIÓN A
// Base: capital aportado por capitalistas, tasa compuesta mensual
// ════════════════════════════════════════════════════════════

// Capital base = total aportado por capitalistas del cobro activo
$stmtCapBase = $db->prepare("
    SELECT COALESCE(SUM(monto), 0)
    FROM capital_movimientos
    WHERE cobro_id=? AND tipo='ingreso_capital' AND es_entrada=1 AND anulado=0
");
$stmtCapBase->execute([$cobro]);
$capitalBase = (float)$stmtCapBase->fetchColumn();
if ($capitalBase <= 0) $capitalBase = $capitalDisponible; // fallback

// Cobros reales por mes del año actual (para comparar real vs esperado)
$stmtCobrosMes = $db->prepare("
    SELECT MONTH(fecha) AS mes, SUM(monto) AS cobrado
    FROM capital_movimientos
    WHERE cobro_id=? AND tipo='cobro_cuota' AND es_entrada=1
      AND anulado=0 AND YEAR(fecha)=?
    GROUP BY MONTH(fecha)
");
$stmtCobrosMes->execute([$cobro, $anioActual]);
$cobrosRealesPorMes = [];
while ($row = $stmtCobrosMes->fetch()) {
    $cobrosRealesPorMes[(int)$row['mes']] = (float)$row['cobrado'];
}
$mesesConDatos = count($cobrosRealesPorMes);

// Construir tabla 12 meses — interés compuesto sobre capital creciente
$nombresMeses = ['','Enero','Febrero','Marzo','Abril','Mayo','Junio',
                 'Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'];

$tablaAnual        = [];
$capitalActual     = $capitalBase;  // crece cada mes con los intereses
$totalInteresTabla = 0;
$totalCobradoTabla = 0;

for ($mes = 1; $mes <= 12; $mes++) {
    $esReal   = isset($cobrosRealesPorMes[$mes]) && $mes < $mesActual;
    $esActual = ($mes === $mesActual);

    // Interés esperado = capital acumulado × tasa (COMPUESTO)
    $interesEsperado  = round($capitalActual * ($tasaPromedio / 100));
    $capitalCierre    = $capitalActual + $interesEsperado;

    // Cobro real del mes (si existe)
    $cobradoReal = $cobrosRealesPorMes[$mes] ?? 0;

    // Diferencia real vs esperado
    $diferencia  = $esReal ? $cobradoReal - $interesEsperado : null;

    if ($esReal)        $tipo = 'real';
    elseif ($esActual)  $tipo = 'actual';
    else                $tipo = 'proyectado';

    $crecimiento = (($capitalCierre - $capitalBase) / $capitalBase) * 100;

    $tablaAnual[] = [
        'mes'              => $mes,
        'nombre'           => $nombresMeses[$mes],
        'capital_inicio'   => round($capitalActual),
        'interes_esperado' => $interesEsperado,
        'cobrado_real'     => round($cobradoReal),
        'diferencia'       => $diferencia !== null ? round($diferencia) : null,
        'capital_cierre'   => round($capitalCierre),
        'crecimiento'      => round($crecimiento, 1),
        'tipo'             => $tipo,
    ];

    $totalInteresTabla += $interesEsperado;
    $totalCobradoTabla += $cobradoReal;

    // El capital SIEMPRE crece con el interés compuesto (reinversión total)
    $capitalActual = $capitalCierre;
}

$carteraFinalAnio = $carteraActual;
$roiAnual = $capitalInicialComp > 0
    ? (($carteraFinalAnio - $capitalInicialComp) / $capitalInicialComp) * 100
    : 0;

$grafLabels  = array_column($tablaAnual, 'nombre');
$grafCartera = array_column($tablaAnual, 'cartera_cierre');
$grafTipos   = array_column($tablaAnual, 'tipo');

$pageTitle   = 'Proyección';
$pageSection = 'Proyección';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
  <div>
    <h1>PROYECCIÓN</h1>
    <p>// Escenarios financieros y crecimiento proyectado</p>
  </div>
</div>

<!-- Stats base -->
<div class="stats-grid" style="grid-template-columns:repeat(4,1fr);margin-bottom:1.75rem">
  <div class="stat-card" style="border-color:#22c55e33">
    <div class="stat-label">SALDO EN CAJA</div>
    <div class="stat-value" style="color:#22c55e"><?= fmt($capitalDisponible) ?></div>
    <div class="stat-sub">Disponible ahora</div>
  </div>
  <div class="stat-card orange">
    <div class="stat-label">CARTERA ACTIVA</div>
    <div class="stat-value"><?= fmt($carteraBase) ?></div>
    <div class="stat-sub">Saldo pendiente total</div>
  </div>
  <div class="stat-card" style="border-color:#818cf833">
    <div class="stat-label">TASA PROMEDIO</div>
    <div class="stat-value" style="color:#818cf8"><?= $tasaPromedio ?>%</div>
    <div class="stat-sub">Interés mensual pactado</div>
  </div>
  <div class="stat-card">
    <div class="stat-label">PRÉSTAMOS ACTIVOS</div>
    <div class="stat-value" style="color:var(--muted)"><?= $b['num_prestamos'] ?></div>
    <div class="stat-sub">En cartera</div>
  </div>
</div>

<!-- ── Escenarios ─────────────────────────────────────────── -->
<div class="card mb-4">
  <div class="card-header">
    <h3>◈ ESCENARIOS DE CRECIMIENTO — 12 MESES</h3>
  </div>
  <div class="card-body">
    <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:1.5rem;margin-bottom:1.5rem">
      <?php
      $escs = [
        'conservador' => ['Sin crecimiento',     '#6b7280', '0%'],
        'moderado'    => ['Crecimiento moderado', '#818cf8', '+10%/mes'],
        'agresivo'    => ['Crecimiento agresivo', '#22c55e', '+20%/mes'],
      ];
      foreach ($escs as $key => [$label, $color, $badge]):
        $ultimo = end($escenarios[$key]);
      ?>
      <div class="stat-card" style="border-color:<?= $color ?>33;cursor:pointer" onclick="showEscenario('<?= $key ?>')">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:.5rem">
          <div class="stat-label"><?= strtoupper($key) ?></div>
          <span style="font-family:var(--font-mono);font-size:.65rem;color:<?= $color ?>;border:1px solid <?= $color ?>44;padding:2px 8px;border-radius:4px"><?= $badge ?></span>
        </div>
        <div class="stat-value" style="color:<?= $color ?>"><?= fmt($ultimo['capital']) ?></div>
        <div class="stat-sub"><?= $label ?> · mes 12</div>
      </div>
      <?php endforeach; ?>
    </div>
    <canvas id="chart-escenarios" height="100"></canvas>
    <div style="margin-top:1.5rem;overflow-x:auto">
      <table>
        <thead>
          <tr><th>Mes</th><th>Capital</th><th>Cartera</th><th>Interés</th></tr>
        </thead>
        <tbody id="tabla-escenario-body"></tbody>
      </table>
    </div>
  </div>
</div>

<!-- ── Interés Compuesto Año Real ────────────────────────── -->
<div class="card mb-4">
  <div class="card-header" style="border-bottom:1px solid var(--border)">
    <h3>◈ INTERÉS COMPUESTO — AÑO <?= $anioActual ?></h3>
    <p style="font-family:var(--font-mono);font-size:.68rem;color:var(--muted);margin-top:.3rem">
      // Histórico real + proyección con reinversión total · Tasa <?= $tasaPromedio ?>% mensual
    </p>
  </div>
  <div class="card-body">

    <!-- Mini cards -->
    <?php
    $ultimoMes       = end($tablaAnual);
    $capitalFinalAnio = $ultimoMes['capital_cierre'];
    $roiAnual         = $capitalBase > 0
        ? (($capitalFinalAnio - $capitalBase) / $capitalBase) * 100
        : 0;
    ?>
    <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:1rem;margin-bottom:1.5rem">
      <div class="stat-card" style="border-color:#818cf833;padding:.85rem">
        <div class="stat-label" style="font-size:.6rem">TASA MENSUAL</div>
        <div style="font-family:var(--font-display);font-size:1.5rem;color:#818cf8;font-weight:700"><?= $tasaPromedio ?>%</div>
        <div class="stat-sub">Interés compuesto mensual</div>
      </div>
      <div class="stat-card" style="border-color:#22c55e33;padding:.85rem">
        <div class="stat-label" style="font-size:.6rem">CAPITAL BASE <?= $anioActual ?></div>
        <div style="font-family:var(--font-display);font-size:1.5rem;color:#22c55e;font-weight:700"><?= fmt($capitalBase) ?></div>
        <div class="stat-sub">Aportado por capitalistas</div>
      </div>
      <div class="stat-card" style="border-color:#f59e0b33;padding:.85rem">
        <div class="stat-label" style="font-size:.6rem">CAPITAL PROYECTADO DIC</div>
        <div style="font-family:var(--font-display);font-size:1.5rem;color:#f59e0b;font-weight:700"><?= fmt($capitalFinalAnio) ?></div>
        <div class="stat-sub">Con reinversión total</div>
      </div>
      <div class="stat-card" style="border-color:<?= $roiAnual >= 0 ? '#22c55e33' : '#ef444433' ?>;padding:.85rem">
        <div class="stat-label" style="font-size:.6rem">ROI ANUAL</div>
        <div style="font-family:var(--font-display);font-size:1.5rem;color:<?= $roiAnual >= 0 ? '#22c55e' : '#ef4444' ?>;font-weight:700">
          <?= ($roiAnual >= 0 ? '+' : '') . round($roiAnual, 1) ?>%
        </div>
        <div class="stat-sub">Crecimiento acumulado</div>
      </div>
    </div>

    <!-- Gráfico -->
    <canvas id="chart-compuesto" height="90" style="margin-bottom:1.5rem"></canvas>

    <!-- Tabla -->
    <div style="overflow-x:auto">
      <table>
        <thead>
          <tr>
            <th>MES</th>
            <th style="text-align:right">CAPITAL INICIO</th>
            <th style="text-align:right">INTERÉS ESPERADO</th>
            <th style="text-align:right">COBRADO REAL</th>
            <th style="text-align:right">DIFERENCIA</th>
            <th style="text-align:right">CAPITAL CIERRE</th>
            <th style="text-align:right">CRECIMIENTO</th>
            <th style="text-align:center">TIPO</th>
          </tr>
        </thead>
        <tbody>
        <?php foreach ($tablaAnual as $f):
          $rowStyle  = $f['tipo']==='actual'     ? 'background:rgba(129,140,248,.07);outline:1px solid #818cf844;'
                     : ($f['tipo']==='proyectado' ? 'opacity:.7;' : '');
          $crecColor = $f['crecimiento'] >= 0 ? '#22c55e' : '#ef4444';
          $difColor  = $f['diferencia'] === null ? 'var(--muted)'
                     : ($f['diferencia'] >= 0 ? '#22c55e' : '#ef4444');
        ?>
          <tr style="<?= $rowStyle ?>">
            <td class="text-mono" style="font-weight:600">
              <?= $f['nombre'] ?>
              <?php if ($f['tipo']==='actual'): ?>
                <span style="font-size:.55rem;background:#818cf822;color:#818cf8;padding:2px 5px;border-radius:3px;margin-left:4px">HOY</span>
              <?php elseif ($f['tipo']==='proyectado'): ?>
                <span style="font-size:.55rem;background:#f59e0b22;color:#f59e0b;padding:2px 5px;border-radius:3px;margin-left:4px">PROY</span>
              <?php endif; ?>
            </td>
            <td class="text-mono" style="text-align:right;color:var(--text-soft)"><?= fmt($f['capital_inicio']) ?></td>
            <td class="text-mono" style="text-align:right;color:#f59e0b">+<?= fmt($f['interes_esperado']) ?></td>
            <td class="text-mono" style="text-align:right;color:#22c55e">
              <?= $f['cobrado_real'] > 0 ? '+'.fmt($f['cobrado_real']) : '—' ?>
            </td>
            <td class="text-mono" style="text-align:right;color:<?= $difColor ?>">
              <?php if ($f['diferencia'] === null): ?>—
              <?php elseif ($f['diferencia'] >= 0): ?>+<?= fmt($f['diferencia']) ?>
              <?php else: ?><?= fmt($f['diferencia']) ?><?php endif; ?>
            </td>
            <td class="text-mono" style="text-align:right;font-weight:700"><?= fmt($f['capital_cierre']) ?></td>
            <td class="text-mono" style="text-align:right;color:<?= $crecColor ?>"><?= ($f['crecimiento']>=0?'+':'').$f['crecimiento'] ?>%</td>
            <td style="text-align:center">
              <?php if ($f['tipo']==='real'): ?>
                <span style="font-size:.6rem;background:#22c55e22;color:#22c55e;padding:2px 8px;border-radius:3px">REAL</span>
              <?php elseif ($f['tipo']==='actual'): ?>
                <span style="font-size:.6rem;background:#818cf822;color:#818cf8;padding:2px 8px;border-radius:3px">PARCIAL</span>
              <?php else: ?>
                <span style="font-size:.6rem;background:#f59e0b22;color:#f59e0b;padding:2px 8px;border-radius:3px">PROY</span>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
        <tfoot>
          <tr style="border-top:2px solid var(--border);background:var(--surface-2)">
            <td class="text-mono" style="font-weight:700">TOTAL <?= $anioActual ?></td>
            <td></td>
            <td class="text-mono" style="text-align:right;color:#f59e0b;font-weight:700">+<?= fmt($totalInteresTabla) ?></td>
            <td class="text-mono" style="text-align:right;color:#22c55e;font-weight:700">+<?= fmt($totalCobradoTabla) ?></td>
            <td></td>
            <td class="text-mono" style="text-align:right;font-weight:700;color:var(--accent)"><?= fmt($capitalFinalAnio) ?></td>
            <td class="text-mono" style="text-align:right;font-weight:700;color:<?= $roiAnual>=0 ? '#22c55e' : '#ef4444' ?>">
              <?= ($roiAnual>=0?'+':'').round($roiAnual,1) ?>%
            </td>
            <td></td>
          </tr>
        </tfoot>
      </table>
    </div>

    <p style="font-family:var(--font-mono);font-size:.62rem;color:var(--muted);margin-top:1rem;padding:.75rem;background:var(--surface-2);border-radius:6px;border-left:3px solid var(--border)">
      ⚠ SUPUESTOS: Capital base = aportes reales de capitalistas (<?= fmt($capitalBase) ?>).
      Interés compuesto mensual de <?= $tasaPromedio ?>% reinvertido en su totalidad cada mes.
      <?= $mesesConDatos ?> mes<?= $mesesConDatos!=1?'es':'' ?> con datos reales · <?= 12-$mesesConDatos ?> meses proyectados.
      No contempla mora, retiros ni impagos.
    </p>
  </div>
</div>

<?php
$escJson     = json_encode($escenarios);
$grafLabelsJ  = json_encode(array_column($tablaAnual, 'nombre'));
$grafCapitalJ = json_encode(array_column($tablaAnual, 'capital_cierre'));
$grafTiposJ   = json_encode(array_column($tablaAnual, 'tipo'));

$extraScript = <<<JS
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js"></script>
<script>
const escenarios = $escJson;
let chartEsc = null;

function fmt(n) {
    if (n >= 1000000) return '$' + (n/1000000).toFixed(1) + 'M';
    if (n >= 1000)    return '$' + Math.round(n/1000) + 'K';
    return '$' + Math.round(n).toLocaleString('es-CO');
}

function showEscenario(key) {
    const data   = escenarios[key];
    const colors = { conservador:'#6b7280', moderado:'#818cf8', agresivo:'#22c55e' };
    const color  = colors[key];
    if (chartEsc) {
        chartEsc.data.datasets[0].data             = data.map(d => d.capital);
        chartEsc.data.datasets[0].borderColor      = color;
        chartEsc.data.datasets[0].backgroundColor  = color + '22';
        chartEsc.update();
    }
    const tbody = document.getElementById('tabla-escenario-body');
    tbody.innerHTML = data.map(d => `
        <tr>
            <td class="text-mono">Mes \${d.mes}</td>
            <td class="text-mono" style="text-align:right;color:\${color}">\${fmt(d.capital)}</td>
            <td class="text-mono" style="text-align:right;color:var(--text-soft)">\${fmt(d.cartera)}</td>
            <td class="text-mono" style="text-align:right;color:#f59e0b">+\${fmt(d.interes)}</td>
        </tr>
    `).join('');
}

// Gráfico escenarios
const ctxEsc = document.getElementById('chart-escenarios').getContext('2d');
chartEsc = new Chart(ctxEsc, {
    type: 'line',
    data: {
        labels: Array.from({length:12}, (_,i) => 'Mes '+(i+1)),
        datasets: [{
            label: 'Capital',
            data: escenarios.moderado.map(d => d.capital),
            borderColor: '#818cf8',
            backgroundColor: '#818cf822',
            borderWidth: 2,
            fill: true,
            tension: 0.4,
            pointRadius: 4,
        }]
    },
    options: {
        responsive: true,
        plugins: { legend: { display: false } },
        scales: {
            y: { grid:{color:'#ffffff0a'}, ticks:{color:'#9ca3af', callback: v => fmt(v)} },
            x: { grid:{color:'#ffffff0a'}, ticks:{color:'#9ca3af'} }
        }
    }
});
showEscenario('moderado');

// Gráfico interés compuesto año real
const labels  = $grafLabelsJ;
const capital = $grafCapitalJ;
const tipos   = $grafTiposJ;

const bgMap = { real:'#22c55e', actual:'#818cf8', proyectado:'#f59e0b' };
const bgs   = tipos.map(t => bgMap[t] + '44');
const bords = tipos.map(t => bgMap[t]);

const ctxComp = document.getElementById('chart-compuesto').getContext('2d');
new Chart(ctxComp, {
    type: 'bar',
    data: {
        labels,
        datasets: [
            {
                type: 'line',
                label: 'Capital acumulado',
                data: capital,
                borderColor: '#818cf8',
                backgroundColor: 'transparent',
                borderWidth: 2,
                tension: 0.4,
                pointRadius: 5,
                pointBackgroundColor: bords,
                order: 1,
            },
            {
                type: 'bar',
                label: 'Capital cierre',
                data: capital,
                backgroundColor: bgs,
                borderColor: bords,
                borderWidth: 1,
                borderRadius: 4,
                order: 2,
            }
        ]
    },
    options: {
        responsive: true,
        plugins: {
            legend: {
                display: true,
                labels: {
                    color: '#9ca3af',
                    generateLabels: () => [
                        { text:'Real',       fillStyle:'#22c55e', strokeStyle:'#22c55e', lineWidth:2 },
                        { text:'Mes actual', fillStyle:'#818cf8', strokeStyle:'#818cf8', lineWidth:2 },
                        { text:'Proyectado', fillStyle:'#f59e0b', strokeStyle:'#f59e0b', lineWidth:2 },
                    ]
                }
            },
            tooltip: {
                callbacks: { label: ctx => 'Capital: ' + fmt(ctx.raw) }
            }
        },
        scales: {
            y: {
                grid: {color:'#ffffff0a'},
                ticks: {color:'#9ca3af', callback: v => fmt(v)},
                beginAtZero: false
            },
            x: { grid:{color:'#ffffff0a'}, ticks:{color:'#9ca3af'} }
        }
    }
});
</script>
JS;
require_once __DIR__ . '/../includes/footer.php';
?>