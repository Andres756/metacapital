<?php
require_once __DIR__ . '/../config/auth.php';
requireLogin();
if (!canDo('puede_ver_movimientos')) { include __DIR__ . '/403.php'; exit; }

$db    = getDB();
$cobro = cobroActivo();

// Filtros
$fechaDesde = $_GET['desde'] ?? date('Y-m-01'); // primer día del mes
$fechaHasta = $_GET['hasta'] ?? date('Y-m-d');
$tipoFiltro = $_GET['tipo'] ?? '';
$page       = max(1, (int)($_GET['page'] ?? 1));
$limit      = 25;
$offset     = ($page - 1) * $limit;

$where  = ['m.cobro_id=?', 'm.tipo != "prestamo_proporcional"', '(m.anulado=0 OR m.anulado IS NULL)'];
$params = [$cobro];

if ($fechaDesde) { $where[] = 'm.fecha >= ?'; $params[] = $fechaDesde; }
if ($fechaHasta) { $where[] = 'm.fecha <= ?'; $params[] = $fechaHasta; }
if ($tipoFiltro) { $where[] = 'm.tipo = ?';   $params[] = $tipoFiltro; }

$whereSQL = implode(' AND ', $where);

// Total registros
$stmtTotal = $db->prepare("SELECT COUNT(*) FROM capital_movimientos m WHERE $whereSQL");
$stmtTotal->execute($params);
$totalRegs = (int)$stmtTotal->fetchColumn();
$totalPags = ceil($totalRegs / $limit);

// Movimientos
$stmtM = $db->prepare("
    SELECT m.*,
        c.nombre AS cuenta_nombre,
        cap.nombre AS capitalista_nombre,
        d.nombre AS deudor_nombre,
        u.nombre AS usuario_nombre
    FROM capital_movimientos m
    LEFT JOIN cuentas c       ON c.id   = m.cuenta_id
    LEFT JOIN capitalistas cap ON cap.id = m.capitalista_id
    LEFT JOIN prestamos pr    ON pr.id  = m.prestamo_id
    LEFT JOIN deudores d      ON d.id   = pr.deudor_id
    LEFT JOIN usuarios u      ON u.id   = m.usuario_id
    WHERE $whereSQL
    ORDER BY m.fecha DESC, m.id DESC
    LIMIT $limit OFFSET $offset
");
$stmtM->execute($params);
$movimientos = $stmtM->fetchAll();

// Stats del período
$stmtStats = $db->prepare("
    SELECT
        COALESCE(SUM(CASE WHEN es_entrada=1 THEN monto ELSE 0 END), 0) AS total_entradas,
        COALESCE(SUM(CASE WHEN es_entrada=0 THEN monto ELSE 0 END), 0) AS total_salidas,
        COALESCE(SUM(CASE WHEN es_entrada=1 THEN monto ELSE -monto END), 0) AS balance,
        COUNT(*) AS total_movs
    FROM capital_movimientos m
    WHERE $whereSQL
");
$stmtStats->execute($params);
$stats = $stmtStats->fetch();

// Tipos disponibles para filtro
$tiposMap = [
    'ingreso_capital'  => 'Ingreso Capital',
    'retiro_capital'   => 'Retiro Capital',
    'prestamo'         => 'Préstamo Entregado',
    'cobro_cuota'      => 'Cobro Cuota',
    'redito'           => 'Rédito Pagado',
    'salida'           => 'Salida/Gasto',
    'ajuste'           => 'Ajuste',
];

$pageTitle   = 'Movimientos';
$pageSection = 'Movimientos';
require_once __DIR__ . '/../includes/header.php';
?>

<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1.5rem;flex-wrap:wrap;gap:1rem">
  <div>
    <h1 class="page-title">MOVIMIENTOS DE CAJA</h1>
    <p class="text-muted text-xs">// <?= $totalRegs ?> registros encontrados</p>
  </div>
</div>

<!-- Stats período -->
<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:1rem;margin-bottom:1.5rem">
  <div class="card">
    <div class="card-body" style="padding:1rem">
      <div class="text-muted text-xs mb-1" style="letter-spacing:1px">ENTRADAS</div>
      <div class="text-mono fw-600" style="font-size:1.3rem;color:var(--accent)"><?= fmt($stats['total_entradas']) ?></div>
    </div>
  </div>
  <div class="card">
    <div class="card-body" style="padding:1rem">
      <div class="text-muted text-xs mb-1" style="letter-spacing:1px">SALIDAS</div>
      <div class="text-mono fw-600" style="font-size:1.3rem;color:#ef4444"><?= fmt($stats['total_salidas']) ?></div>
    </div>
  </div>
  <div class="card">
    <div class="card-body" style="padding:1rem">
      <div class="text-muted text-xs mb-1" style="letter-spacing:1px">BALANCE PERÍODO</div>
      <div class="text-mono fw-600" style="font-size:1.3rem;color:<?= $stats['balance'] >= 0 ? 'var(--accent)' : '#ef4444' ?>">
        <?= fmt($stats['balance']) ?>
      </div>
    </div>
  </div>
  <div class="card">
    <div class="card-body" style="padding:1rem">
      <div class="text-muted text-xs mb-1" style="letter-spacing:1px">MOVIMIENTOS</div>
      <div class="text-mono fw-600" style="font-size:1.3rem;color:var(--text)"><?= $stats['total_movs'] ?></div>
    </div>
  </div>
</div>

<!-- Filtros -->
<div class="card mb-2">
  <div class="card-body" style="padding:0.75rem 1rem">
    <form method="GET" style="display:flex;flex-wrap:wrap;gap:0.75rem;align-items:flex-end">
      <div class="field" style="margin:0">
        <label style="font-size:0.65rem;letter-spacing:1px;color:var(--muted)">DESDE</label>
        <input type="date" name="desde" value="<?= htmlspecialchars($fechaDesde) ?>"
          style="background:var(--surface2);border:1px solid var(--border);color:var(--text);padding:0.4rem 0.6rem;border-radius:6px;font-size:0.8rem">
      </div>
      <div class="field" style="margin:0">
        <label style="font-size:0.65rem;letter-spacing:1px;color:var(--muted)">HASTA</label>
        <input type="date" name="hasta" value="<?= htmlspecialchars($fechaHasta) ?>"
          style="background:var(--surface2);border:1px solid var(--border);color:var(--text);padding:0.4rem 0.6rem;border-radius:6px;font-size:0.8rem">
      </div>
      <div class="field" style="margin:0">
        <label style="font-size:0.65rem;letter-spacing:1px;color:var(--muted)">TIPO</label>
        <select name="tipo" style="background:var(--surface2);border:1px solid var(--border);color:var(--text);padding:0.4rem 0.6rem;border-radius:6px;font-size:0.8rem">
          <option value="">Todos</option>
          <?php foreach ($tiposMap as $val => $label): ?>
          <option value="<?= $val ?>" <?= $tipoFiltro===$val?'selected':'' ?>><?= $label ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <button type="submit" class="btn btn-primary btn-sm">FILTRAR</button>
      <a href="?" class="btn btn-ghost btn-sm">Limpiar</a>
      <!-- Accesos rápidos -->
      <div style="display:flex;gap:0.4rem;margin-left:auto;flex-wrap:wrap">
        <a href="?desde=<?= date('Y-m-d') ?>&hasta=<?= date('Y-m-d') ?>" class="btn btn-ghost btn-sm">Hoy</a>
        <a href="?desde=<?= date('Y-m-d', strtotime('monday this week')) ?>&hasta=<?= date('Y-m-d') ?>" class="btn btn-ghost btn-sm">Esta semana</a>
        <a href="?desde=<?= date('Y-m-01') ?>&hasta=<?= date('Y-m-d') ?>" class="btn btn-ghost btn-sm">Este mes</a>
        <a href="?desde=<?= date('Y-m-01', strtotime('first day of last month')) ?>&hasta=<?= date('Y-m-t', strtotime('first day of last month')) ?>" class="btn btn-ghost btn-sm">Mes anterior</a>
      </div>
    </form>
  </div>
</div>

<!-- Tabla movimientos -->
<div class="card">
  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>Fecha</th>
          <th>Tipo</th>
          <th>Descripción</th>
          <th>Cuenta</th>
          <th>Deudor / Capitalista</th>
          <th style="text-align:right">Entrada</th>
          <th style="text-align:right">Salida</th>
          <th class="text-muted text-xs">Usuario</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($movimientos)): ?>
        <tr><td colspan="8" style="text-align:center;padding:2rem;color:var(--muted)">Sin movimientos en el período</td></tr>
        <?php else: ?>
        <?php foreach ($movimientos as $m): ?>
        <?php
          $tipoLabel = $tiposMap[$m['tipo']] ?? ucfirst(str_replace('_',' ',$m['tipo']));
          $tipoColor = match($m['tipo']) {
            'cobro_cuota'     => '#22c55e',
            'ingreso_capital' => '#818cf8',
            'prestamo'        => '#f97316',
            'retiro_capital'  => '#ef4444',
            'redito'          => '#f59e0b',
            'salida'          => '#ef4444',
            default           => 'var(--muted)'
          };
          $ref = $m['deudor_nombre'] ?? $m['capitalista_nombre'] ?? '—';
        ?>
        <tr>
          <td class="text-mono text-xs"><?= date('d M Y', strtotime($m['fecha'])) ?></td>
          <td>
            <span class="badge" style="background:<?= $tipoColor ?>22;color:<?= $tipoColor ?>;font-size:0.6rem;white-space:nowrap">
              <?= $tipoLabel ?>
            </span>
          </td>
          <td style="font-size:0.8rem"><?= htmlspecialchars($m['descripcion'] ?? '—') ?></td>
          <td class="text-muted text-xs"><?= htmlspecialchars($m['cuenta_nombre'] ?? '—') ?></td>
          <td class="text-muted text-xs"><?= htmlspecialchars($ref) ?></td>
          <td style="text-align:right" class="text-mono fw-600">
            <?php if ($m['es_entrada']): ?>
              <span style="color:#22c55e"><?= fmt($m['monto']) ?></span>
            <?php else: ?>—<?php endif; ?>
          </td>
          <td style="text-align:right" class="text-mono fw-600">
            <?php if (!$m['es_entrada']): ?>
              <span style="color:#ef4444"><?= fmt($m['monto']) ?></span>
            <?php else: ?>—<?php endif; ?>
          </td>
          <td class="text-muted text-xs"><?= htmlspecialchars($m['usuario_nombre'] ?? '—') ?></td>
        </tr>
        <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
      <?php if (!empty($movimientos)): ?>
      <tfoot>
        <tr style="border-top:2px solid var(--border)">
          <td colspan="5" style="text-align:right;font-size:0.75rem;color:var(--muted);padding-right:1rem">TOTAL PÁGINA</td>
          <td style="text-align:right" class="text-mono fw-600" style="color:#22c55e">
            <?= fmt(array_sum(array_map(fn($m) => $m['es_entrada'] ? $m['monto'] : 0, $movimientos))) ?>
          </td>
          <td style="text-align:right" class="text-mono fw-600" style="color:#ef4444">
            <?= fmt(array_sum(array_map(fn($m) => !$m['es_entrada'] ? $m['monto'] : 0, $movimientos))) ?>
          </td>
          <td></td>
        </tr>
      </tfoot>
      <?php endif; ?>
    </table>
  </div>

  <!-- Paginación -->
  <?php if ($totalPags > 1): ?>
  <div style="display:flex;justify-content:center;gap:0.5rem;padding:1rem">
    <?php for ($i=1; $i<=$totalPags; $i++): ?>
    <a href="?<?= http_build_query(array_merge($_GET, ['page'=>$i])) ?>"
       class="btn <?= $i===$page ? 'btn-primary' : 'btn-ghost' ?> btn-sm"><?= $i ?></a>
    <?php endfor; ?>
  </div>
  <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>