<?php
// ============================================================
// api/upload_logo.php — Subir logo del sistema
// Guarda /assets/img/logo.png  y  /assets/img/logo.ico
// Solo superadmin o admin pueden acceder
// ============================================================
require_once __DIR__ . '/../config/auth.php';
requireLogin();

header('Content-Type: application/json');

// Solo admin/superadmin
if (!in_array($_SESSION['rol'], ['superadmin', 'admin'])) {
    echo json_encode(['ok' => false, 'msg' => 'Sin permiso']);
    exit;
}

// ── Acción eliminar ─────────────────────────────────────────
$input = json_decode(file_get_contents('php://input'), true) ?? [];
if (($input['action'] ?? '') === 'eliminar') {
    $pngPath = $_SERVER['DOCUMENT_ROOT'] . '/assets/img/logo.png';
    $icoPath = $_SERVER['DOCUMENT_ROOT'] . '/assets/img/logo.ico';
    if (file_exists($pngPath)) unlink($pngPath);
    if (file_exists($icoPath)) unlink($icoPath);
    echo json_encode(['ok' => true, 'msg' => 'Logo eliminado']);
    exit;
}

// ── Validar que llegó un archivo ────────────────────────────
if (empty($_FILES['logo']) || $_FILES['logo']['error'] !== UPLOAD_ERR_OK) {
    $errores = [
        UPLOAD_ERR_INI_SIZE   => 'El archivo supera el límite del servidor',
        UPLOAD_ERR_FORM_SIZE  => 'El archivo supera el límite del formulario',
        UPLOAD_ERR_PARTIAL    => 'La subida fue incompleta',
        UPLOAD_ERR_NO_FILE    => 'No se seleccionó ningún archivo',
        UPLOAD_ERR_NO_TMP_DIR => 'No hay carpeta temporal',
        UPLOAD_ERR_CANT_WRITE => 'No se pudo escribir en disco',
    ];
    $code = $_FILES['logo']['error'] ?? UPLOAD_ERR_NO_FILE;
    echo json_encode(['ok' => false, 'msg' => $errores[$code] ?? 'Error al subir archivo']);
    exit;
}

// ── Validar tipo de archivo ──────────────────────────────────
$tmpPath  = $_FILES['logo']['tmp_name'];
$mimeReal = mime_content_type($tmpPath);
$allowed  = ['image/png', 'image/jpeg', 'image/jpg', 'image/gif', 'image/webp'];

if (!in_array($mimeReal, $allowed)) {
    echo json_encode(['ok' => false, 'msg' => 'Formato no permitido. Usa PNG, JPG o GIF']);
    exit;
}

// ── Validar tamaño (máx 2 MB) ───────────────────────────────
if ($_FILES['logo']['size'] > 2 * 1024 * 1024) {
    echo json_encode(['ok' => false, 'msg' => 'El archivo no puede superar 2 MB']);
    exit;
}

// ── Verificar que GD está disponible ────────────────────────
if (!extension_loaded('gd')) {
    echo json_encode(['ok' => false, 'msg' => 'La extensión GD no está disponible en el servidor']);
    exit;
}

// ── Directorio destino ───────────────────────────────────────
$destDir = $_SERVER['DOCUMENT_ROOT'] . '/assets/img/';
if (!is_dir($destDir)) {
    mkdir($destDir, 0755, true);
}

// ── Cargar imagen original según MIME ───────────────────────
switch ($mimeReal) {
    case 'image/jpeg':
    case 'image/jpg':
        $src = imagecreatefromjpeg($tmpPath);
        break;
    case 'image/gif':
        $src = imagecreatefromgif($tmpPath);
        break;
    case 'image/webp':
        $src = imagecreatefromwebp($tmpPath);
        break;
    default: // png
        $src = imagecreatefrompng($tmpPath);
        break;
}

if (!$src) {
    echo json_encode(['ok' => false, 'msg' => 'No se pudo procesar la imagen']);
    exit;
}

$origW = imagesx($src);
$origH = imagesy($src);

// ── Guardar logo.png (máx 400px ancho, proporcional) ────────
$maxW   = 400;
$ratio  = $origW > $maxW ? $maxW / $origW : 1;
$newW   = (int)($origW * $ratio);
$newH   = (int)($origH * $ratio);

$png = imagecreatetruecolor($newW, $newH);

// Preservar transparencia
imagealphablending($png, false);
imagesavealpha($png, true);
$transparent = imagecolorallocatealpha($png, 0, 0, 0, 127);
imagefilledrectangle($png, 0, 0, $newW, $newH, $transparent);
imagealphablending($png, true);

imagecopyresampled($png, $src, 0, 0, 0, 0, $newW, $newH, $origW, $origH);

$pngPath = $destDir . 'logo.png';
if (!imagepng($png, $pngPath, 9)) {
    echo json_encode(['ok' => false, 'msg' => 'No se pudo guardar logo.png']);
    exit;
}
imagedestroy($png);

// ── Guardar logo.ico (32×32 y 16×16 en un solo .ico) ────────
// PHP no tiene soporte nativo para .ico, lo generamos a mano
$icoPath  = $destDir . 'logo.ico';
$sizes    = [32, 16];
$icoData  = '';
$images   = [];

foreach ($sizes as $size) {
    $ico = imagecreatetruecolor($size, $size);
    imagealphablending($ico, false);
    imagesavealpha($ico, true);
    $transp = imagecolorallocatealpha($ico, 0, 0, 0, 127);
    imagefilledrectangle($ico, 0, 0, $size, $size, $transp);
    imagealphablending($ico, true);
    imagecopyresampled($ico, $src, 0, 0, 0, 0, $size, $size, $origW, $origH);

    // Capturar PNG en memoria
    ob_start();
    imagepng($ico);
    $pngData = ob_get_clean();
    imagedestroy($ico);
    $images[] = $pngData;
}

imagedestroy($src);

// Construir archivo .ico manualmente
// Formato ICO: header + directorio de imágenes + datos de imágenes
$numImages  = count($images);
$headerSize = 6;                        // ICONDIR
$dirSize    = 16 * $numImages;          // ICONDIRENTRY × n
$offset     = $headerSize + $dirSize;

// ICONDIR: reservado=0, tipo=1 (ICO), count
$icoData  = pack('vvv', 0, 1, $numImages);

// ICONDIRENTRY por cada imagen
foreach ($images as $i => $imgData) {
    $size = strlen($imgData);
    $dim  = $sizes[$i];
    // width, height, colorCount, reserved, planes, bitCount, bytesInRes, imageOffset
    $icoData .= pack('CCCCvvVV',
        $dim >= 256 ? 0 : $dim,  // 0 = 256px
        $dim >= 256 ? 0 : $dim,
        0,    // colorCount (0 = sin paleta)
        0,    // reserved
        1,    // planes
        32,   // bitCount (RGBA)
        $size,
        $offset
    );
    $offset += $size;
}

// Datos de cada imagen
foreach ($images as $imgData) {
    $icoData .= $imgData;
}

if (file_put_contents($icoPath, $icoData) === false) {
    // logo.png se guardó bien, solo fallo el ico — no es crítico
    echo json_encode([
        'ok'  => true,
        'msg' => 'Logo guardado. No se pudo generar el .ico (permiso de escritura)',
        'url' => '/assets/img/logo.png?v=' . time()
    ]);
    exit;
}

echo json_encode([
    'ok'  => true,
    'msg' => 'Logo actualizado correctamente',
    'url' => '/assets/img/logo.png?v=' . time()
]);