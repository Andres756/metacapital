<?php
require_once __DIR__ . '/../config/auth.php';
requireLogin();
if ($_SESSION['rol'] !== 'superadmin' && $_SESSION['rol'] !== 'admin') {
    include __DIR__ . '/403.php'; exit;
}

$db    = getDB();
$cobro = cobroActivo();

// Datos del cobro activo
$stmtC = $db->prepare("SELECT * FROM cobros WHERE id=?");
$stmtC->execute([$cobro]);
$cobroData = $stmtC->fetch();

// Datos del usuario actual
$stmtU = $db->prepare("SELECT * FROM usuarios WHERE id=?");
$stmtU->execute([$_SESSION['usuario_id']]);
$usuarioActual = $stmtU->fetch();

$pageTitle   = 'Configuración';
$pageSection = 'Configuracion';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
  <h1>CONFIGURACIÓN</h1>
  <p>// Ajustes del sistema y del cobro activo</p>
</div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:1.5rem">

  <!-- ============ DATOS DEL COBRO ============ -->
  <div class="card">
    <div class="card-header">
      <span class="card-title">DATOS DEL COBRO</span>
    </div>
    <div class="card-body">
      <form id="form-cobro">
        <div class="form-grid">
          <div class="field field-span2">
            <label>Nombre del cobro <span class="required">*</span></label>
            <input type="text" name="nombre" value="<?= htmlspecialchars($cobroData['nombre']??'') ?>" required>
          </div>
          <div class="field field-span2">
            <label>Descripción</label>
            <input type="text" name="descripcion" value="<?= htmlspecialchars($cobroData['descripcion']??'') ?>" placeholder="Ej: Cobro zona norte">
          </div>
          <div class="field">
            <label>Teléfono</label>
            <input type="text" name="telefono" value="<?= htmlspecialchars($cobroData['telefono']??'') ?>" placeholder="3001234567">
          </div>
          <div class="field">
            <label>Dirección</label>
            <input type="text" name="direccion" value="<?= htmlspecialchars($cobroData['direccion']??'') ?>" placeholder="Calle 1 # 2-3">
          </div>
        </div>
        <div style="margin-top:1rem">
          <button class="btn btn-primary" type="button" onclick="guardarCobro()">Guardar cambios</button>
        </div>
      </form>
    </div>
  </div>

  <!-- ============ LOGO DEL SISTEMA ============ -->
  <div class="card">
    <div class="card-header">
      <span class="card-title">LOGO DEL SISTEMA</span>
    </div>
    <div class="card-body">

      <!-- Vista previa actual -->
      <div style="margin-bottom:1.25rem">
        <div style="font-family:var(--font-mono);font-size:0.65rem;color:var(--muted);text-transform:uppercase;letter-spacing:1px;margin-bottom:0.6rem">Vista previa actual</div>
        <div style="background:var(--bg);border:1px solid var(--border);border-radius:var(--radius);padding:1.25rem;display:flex;align-items:center;justify-content:center;min-height:80px">
          <?php
            $logoPath = $_SERVER['DOCUMENT_ROOT'] . '/assets/img/logo.png';
            if (file_exists($logoPath)):
          ?>
            <img src="/assets/img/logo.png?v=<?= filemtime($logoPath) ?>"
                 alt="Logo actual" id="logo-preview"
                 style="max-height:60px;max-width:100%;object-fit:contain">
          <?php else: ?>
            <div id="logo-preview-placeholder" style="font-family:var(--font-display);font-size:1.5rem;letter-spacing:2px;color:var(--accent)">
              META <small style="font-size:0.8rem;color:var(--muted);display:block;text-align:center;letter-spacing:4px">CAPITAL</small>
            </div>
          <?php endif; ?>
        </div>
      </div>

      <!-- Zona de drop -->
      <div class="field" style="margin-bottom:1rem">
        <label>Subir nuevo logo</label>
        <div style="border:2px dashed var(--border);border-radius:var(--radius);padding:1.25rem;text-align:center;cursor:pointer;transition:border-color .2s"
             id="logo-dropzone"
             onclick="document.getElementById('logo-input').click()"
             ondragover="event.preventDefault();this.style.borderColor='var(--accent)'"
             ondragleave="this.style.borderColor='var(--border)'"
             ondrop="handleLogoDrop(event)">
          <div style="font-size:1.5rem;margin-bottom:0.4rem">🖼</div>
          <div style="font-size:0.8rem;color:var(--muted);font-family:var(--font-mono)">
            Arrastra aquí o haz click para seleccionar
          </div>
          <div style="font-size:0.68rem;color:var(--muted);margin-top:0.25rem">
            PNG, JPG, GIF, WEBP — Máx 2 MB
          </div>
          <div id="logo-filename" style="margin-top:0.5rem;font-size:0.75rem;color:var(--accent);font-family:var(--font-mono);display:none"></div>
        </div>
        <input type="file" id="logo-input" accept="image/png,image/jpeg,image/gif,image/webp"
               style="display:none" onchange="previewLogo(this)">
      </div>

      <div style="display:flex;gap:0.75rem;align-items:center">
        <button class="btn btn-primary" onclick="subirLogo()" id="btn-subir-logo" disabled>
          Guardar logo
        </button>
        <?php if (file_exists($logoPath ?? '')): ?>
        <button class="btn btn-ghost btn-sm" onclick="eliminarLogo()" style="color:var(--danger)">
          ✕ Eliminar logo
        </button>
        <?php endif; ?>
        <span id="logo-upload-status" style="font-size:0.75rem;font-family:var(--font-mono);color:var(--muted)"></span>
      </div>

    </div>
  </div>

  <!-- ============ MI PERFIL ============ -->
  <div class="card">
    <div class="card-header">
      <span class="card-title">MI PERFIL</span>
    </div>
    <div class="card-body">
      <form id="form-perfil">
        <div class="form-grid">
          <div class="field field-span2">
            <label>Nombre <span class="required">*</span></label>
            <input type="text" name="nombre" value="<?= htmlspecialchars($usuarioActual['nombre']) ?>" required>
          </div>
          <div class="field field-span2">
            <label>Email <span class="required">*</span></label>
            <input type="email" name="email" value="<?= htmlspecialchars($usuarioActual['email']) ?>" required>
          </div>
          <div class="field">
            <label>Nueva contraseña</label>
            <input type="password" name="password" placeholder="Dejar en blanco para no cambiar">
          </div>
          <div class="field">
            <label>Confirmar contraseña</label>
            <input type="password" name="password_confirm" placeholder="Repetir contraseña">
          </div>
        </div>
        <div style="margin-top:1rem;display:flex;align-items:center;gap:1rem">
          <button class="btn btn-primary" type="button" onclick="guardarPerfil()">Guardar perfil</button>
          <span style="font-family:var(--font-mono);font-size:0.7rem;color:var(--muted)">
            Rol: <strong style="color:var(--accent)"><?= strtoupper($usuarioActual['rol']) ?></strong>
          </span>
        </div>
      </form>
    </div>
  </div>

  <!-- ============ CATEGORÍAS DE GASTO ============ -->
<div class="card mt-2">
  <div class="card-header">
    <span class="card-title">CATEGORÍAS DE GASTO</span>
    <button class="btn btn-ghost btn-sm" onclick="openModal('modal-categoria')">+ Nueva categoría</button>
  </div>
  <?php
    $stmtCats = $db->prepare("SELECT * FROM categorias_gasto WHERE cobro_id=? ORDER BY nombre");
    $stmtCats->execute([$cobro]);
    $categoriasGasto = $stmtCats->fetchAll();
  ?>
  <?php if (empty($categoriasGasto)): ?>
  <div class="empty-state"><span class="empty-icon">◈</span><p>Sin categorías creadas</p></div>
  <?php else: ?>
  <div class="table-wrap">
    <table>
      <thead>
        <tr><th>Nombre</th><th>Estado</th><th></th></tr>
      </thead>
      <tbody>
        <?php foreach ($categoriasGasto as $cat): ?>
        <tr>
          <td><strong><?= htmlspecialchars($cat['nombre']) ?></strong></td>
          <td>
            <span class="badge <?= $cat['activa'] ? 'badge-green' : 'badge-muted' ?>">
              <?= $cat['activa'] ? 'ACTIVA' : 'INACTIVA' ?>
            </span>
          </td>
          <td>
            <button class="btn btn-ghost btn-sm"
                    onclick="toggleCategoria(<?= $cat['id'] ?>, <?= $cat['activa'] ? 0 : 1 ?>)">
              <?= $cat['activa'] ? 'Inactivar' : 'Activar' ?>
            </button>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
</div>

<!-- Modal nueva categoría -->
<div class="modal-overlay" id="modal-categoria">
  <div class="modal" style="max-width:400px">
    <div class="modal-header">
      <h2>NUEVA CATEGORÍA DE GASTO</h2>
      <button class="modal-close" onclick="closeModal('modal-categoria')">✕</button>
    </div>
    <div class="modal-body">
      <div class="field">
        <label>Nombre <span class="required">*</span></label>
        <input type="text" id="cat-nombre" placeholder="Ej: Transporte, Papelería, Salario...">
      </div>
    </div>
    <div class="modal-footer">
      <button class="btn btn-ghost" onclick="closeModal('modal-categoria')">Cancelar</button>
      <button class="btn btn-primary" onclick="crearCategoria()">CREAR</button>
    </div>
  </div>
</div>

</div>



<?php if ($_SESSION['rol'] === 'superadmin'): ?>
<!-- ============ ZONA DE PELIGRO ============ -->
<div class="card mt-2" style="border-color:rgba(255,60,60,0.3)">
  <div class="card-header">
    <span class="card-title" style="color:var(--danger)">ZONA DE PELIGRO</span>
  </div>
  <div class="card-body">
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem">
      <div style="padding:1rem;background:var(--bg);border:1px solid rgba(255,60,60,0.2);border-radius:var(--radius)">
        <div style="font-weight:600;margin-bottom:0.35rem">Exportar todos los datos</div>
        <div style="font-size:0.75rem;color:var(--muted);margin-bottom:0.75rem;font-family:var(--font-mono)">
          Descarga una copia completa en Excel
        </div>
        <button class="btn btn-ghost btn-sm" onclick="exportarTodo()">📦 Exportar backup</button>
      </div>
    </div>
  </div>
</div>
<?php endif; ?>

<!-- ============ INFO DEL SISTEMA ============ -->
<div class="card mt-2">
  <div class="card-header">
    <span class="card-title">INFORMACIÓN DEL SISTEMA</span>
  </div>
  <div class="card-body">
    <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:1rem">
      <?php
      $info = [
        ['PHP', phpversion()],
        ['Cobro activo', '#'.$cobro.' — '.($cobroData['nombre']??'—')],
        ['Usuario', $_SESSION['usuario_nombre'].' ('.strtoupper($_SESSION['rol']).')'],
        ['Servidor', $_SERVER['SERVER_SOFTWARE'] ?? 'Apache/XAMPP'],
        ['Fecha sistema', date('d M Y H:i')],
        ['Sesión', session_id() ? 'Activa' : 'Inactiva'],
      ];
      foreach ($info as [$k, $v]):
      ?>
      <div style="padding:0.75rem;background:var(--bg);border:1px solid var(--border);border-radius:var(--radius)">
        <div style="font-family:var(--font-mono);font-size:0.62rem;color:var(--muted);text-transform:uppercase;letter-spacing:1px;margin-bottom:0.25rem"><?= $k ?></div>
        <div style="font-size:0.82rem;font-weight:600"><?= htmlspecialchars($v) ?></div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</div>

<!-- MODAL NUEVO COBRO -->
<?php if ($_SESSION['rol'] === 'superadmin'): ?>
<div class="modal-overlay" id="modal-cobro">
  <div class="modal">
    <div class="modal-header">
      <h2>NUEVO COBRO</h2>
      <button class="modal-close" onclick="closeModal('modal-cobro')">✕</button>
    </div>
    <div class="modal-body">
      <form id="form-nuevo-cobro">
        <div class="form-grid">
          <div class="field field-span2">
            <label>Nombre <span class="required">*</span></label>
            <input type="text" name="nombre" required placeholder="Ej: Cobro Zona Norte">
          </div>
          <div class="field field-span2">
            <label>Descripción</label>
            <input type="text" name="descripcion" placeholder="Opcional">
          </div>
          <div class="field">
            <label>Teléfono</label>
            <input type="text" name="telefono" placeholder="3001234567">
          </div>
          <div class="field">
            <label>Dirección</label>
            <input type="text" name="direccion" placeholder="Calle 1 # 2-3">
          </div>
          <!-- Agregar dentro del form-grid del cobro, después del campo dirección -->
          <div class="field">
              <label>% Papelería por defecto</label>
              <input type="number" name="papeleria_pct"
                    value="<?= htmlspecialchars($cobroData['papeleria_pct'] ?? 10) ?>"
                    min="0" max="100" step="0.5"
                    placeholder="Ej: 10">
              <div style="font-size:0.72rem;color:var(--muted);margin-top:0.25rem;font-family:var(--font-mono)">
                  Se aplica automáticamente al crear préstamos. Editable por préstamo.
              </div>
          </div>
        </div>
      </form>
    </div>
    <div class="modal-footer">
      <button class="btn btn-ghost" onclick="closeModal('modal-cobro')">Cancelar</button>
      <button class="btn btn-primary" onclick="crearCobro()">CREAR</button>
    </div>
  </div>
</div>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

<script>
async function guardarCobro() {
    var data = Object.fromEntries(new FormData(document.getElementById('form-cobro')));
    data.action = 'editar_cobro';
    var res = await apiPost('/api/configuracion.php', data);
    if (res.ok) toast(res.msg);
    else toast(res.msg || 'Error', 'error');
}

async function guardarPerfil() {
    var data = Object.fromEntries(new FormData(document.getElementById('form-perfil')));
    if (data.password && data.password !== data.password_confirm) {
        toast('Las contraseñas no coinciden', 'error'); return;
    }
    if (data.password && data.password.length < 6) {
        toast('La contraseña debe tener al menos 6 caracteres', 'error'); return;
    }
    data.action = 'perfil';
    var res = await apiPost('/api/configuracion.php', data);
    if (res.ok) toast(res.msg);
    else toast(res.msg || 'Error', 'error');
}

async function crearCobro() {
    var data = Object.fromEntries(new FormData(document.getElementById('form-nuevo-cobro')));
    if (!data.nombre) { toast('El nombre es obligatorio', 'error'); return; }
    data.action = 'crear_cobro';
    var res = await apiPost('/api/configuracion.php', data);
    if (res.ok) {
        toast(res.msg);
        closeModal('modal-cobro');
        setTimeout(() => location.reload(), 800);
    } else toast(res.msg || 'Error', 'error');
}

async function cambiarCobro(id) {
    var res = await apiPost('/api/configuracion.php', { action: 'cambiar_cobro', cobro_id: id });
    if (res.ok) { toast('Cobro cambiado'); setTimeout(() => location.reload(), 600); }
    else toast(res.msg || 'Error', 'error');
}

async function vaciarDatos() {
    if (!confirm('⚠ ADVERTENCIA: Esto borrará TODOS los deudores, préstamos, pagos y movimientos del cobro activo.\n\nEscribe "VACIAR" para confirmar.')) return;
    var conf = prompt('Escribe VACIAR para confirmar:');
    if (conf !== 'VACIAR') { toast('Operación cancelada'); return; }
    var res = await apiPost('/api/configuracion.php', { action: 'vaciar' });
    if (res.ok) { toast(res.msg); setTimeout(() => location.reload(), 1000); }
    else toast(res.msg || 'Error', 'error');
}

async function exportarTodo() {
    window.location.href = '/api/configuracion.php?action=exportar';
}

// ---- LOGO ----
function previewLogo(input) {
    var file = input.files[0];
    if (!file) return;
    var nameEl = document.getElementById('logo-filename');
    nameEl.textContent = file.name;
    nameEl.style.display = 'block';
    document.getElementById('btn-subir-logo').disabled = false;

    // Preview inmediato
    var reader = new FileReader();
    reader.onload = function(e) {
        var prev = document.getElementById('logo-preview');
        var plh  = document.getElementById('logo-preview-placeholder');
        if (plh) plh.style.display = 'none';
        if (prev) {
            prev.src = e.target.result;
        } else {
            var img = document.createElement('img');
            img.id = 'logo-preview';
            img.src = e.target.result;
            img.style = 'max-height:60px;max-width:100%;object-fit:contain';
            document.querySelector('#logo-dropzone').closest('.card-body')
                    .querySelector('[style*="min-height:80px"]').appendChild(img);
        }
    };
    reader.readAsDataURL(file);
}

function handleLogoDrop(e) {
    e.preventDefault();
    document.getElementById('logo-dropzone').style.borderColor = 'var(--border)';
    var file = e.dataTransfer.files[0];
    if (!file) return;
    var input = document.getElementById('logo-input');
    var dt = new DataTransfer();
    dt.items.add(file);
    input.files = dt.files;
    previewLogo(input);
}

async function subirLogo() {
    var file = document.getElementById('logo-input').files[0];
    if (!file) return;
    var btn = document.getElementById('btn-subir-logo');
    var status = document.getElementById('logo-upload-status');
    btn.disabled = true;
    btn.textContent = 'Subiendo...';
    status.textContent = '';
    var fd = new FormData();
    fd.append('logo', file);
    try {
        var res = await fetch('/api/upload_logo.php', { method: 'POST', body: fd });
        var data = await res.json();
        if (data.ok) {
            toast(data.msg);
            status.textContent = '✓ Guardado';
            status.style.color = 'var(--accent)';
            setTimeout(function() { location.reload(); }, 800);
        } else {
            toast(data.msg, 'error');
            status.textContent = data.msg;
            status.style.color = 'var(--danger)';
            btn.disabled = false;
            btn.textContent = 'Guardar logo';
        }
    } catch(e) {
        toast('Error de red', 'error');
        btn.disabled = false;
        btn.textContent = 'Guardar logo';
    }
}

async function eliminarLogo() {
    if (!confirm('¿Eliminar el logo actual?')) return;
    var res = await apiPost('/api/upload_logo.php', { action: 'eliminar' });
    if (res.ok) { toast('Logo eliminado'); setTimeout(function(){ location.reload(); }, 600); }
    else toast(res.msg || 'Error', 'error');
}

async function crearCategoria() {
    const nombre = document.getElementById('cat-nombre').value.trim();
    if (!nombre) { toast('El nombre es obligatorio', 'error'); return; }
    const res = await apiPost('/api/gastos.php', { action: 'crear_categoria', nombre });
    if (res.ok) {
        toast(res.msg);
        closeModal('modal-categoria');
        setTimeout(() => location.reload(), 600);
    } else toast(res.msg || 'Error', 'error');
}

async function toggleCategoria(id, activa) {
    const res = await apiPost('/api/gastos.php', { action: 'toggle_categoria', id, activa });
    if (res.ok) { toast(res.msg); setTimeout(() => location.reload(), 600); }
    else toast(res.msg || 'Error', 'error');
}
</script>