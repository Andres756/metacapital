<?php
require_once __DIR__ . '/../config/auth.php';
requireLogin();

header('Content-Type: application/json');

if (!in_array($_SESSION['rol'], ['superadmin', 'admin'])) {
    echo json_encode(['ok'=>false,'msg'=>'Sin permiso']); exit;
}

// ── Acción eliminar ─────────────────────────────────────────
$input = json_decode(file_get_contents('php://input'), true) ?? [];
if (($input['action'] ?? '') === 'eliminar') {
    $pngPath = $_SERVER['DOCUMENT_ROOT'] . '/assets/img/logo.png';
    $icoPath = $_SERVER['DOCUMENT_ROOT'] . '/assets/img/logo.ico';
    if (file_exists($pngPath)) unlink($pngPath);
    if (file_exists($icoPath)) unlink($icoPath);
    echo json_encode(['ok'=>true,'msg'=>'Logo eliminado']); exit;
}

// ── Validar archivo ─────────────────────────────────────────
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
    echo json_encode(['ok'=>false,'msg'=>$errores[$code] ?? 'Error al subir archivo']); exit;
}

$tmpPath  = $_FILES['logo']['tmp_name'];
$mimeReal = mime_content_type($tmpPath);
$allowed  = ['image/png','image/jpeg','image/jpg','image/gif','image/webp'];

if (!in_array($mimeReal, $allowed)) {
    echo json_encode(['ok'=>false,'msg'=>'Formato no permitido. Usa PNG, JPG o GIF']); exit;
}

if ($_FILES['logo']['size'] > 2 * 1024 * 1024) {
    echo json_encode(['ok'=>false,'msg'=>'El archivo no puede superar 2 MB']); exit;
}

if (!extension_loaded('gd')) {
    echo json_encode(['ok'=>false,'msg'=>'La extensión GD no está disponible']); exit;
}

// ── Directorio destino ───────────────────────────────────────
$destDir = $_SERVER['DOCUMENT_ROOT'] . '/assets/img/';
if (!is_dir($destDir)) mkdir($destDir, 0755, true);

// ── Cargar imagen ────────────────────────────────────────────
switch ($mimeReal) {
    case 'image/jpeg':
    case 'image/jpg':  $src = imagecreatefromjpeg($tmpPath); break;
    case 'image/gif':  $src = imagecreatefromgif($tmpPath);  break;
    case 'image/webp': $src = imagecreatefromwebp($tmpPath); break;
    default:           $src = imagecreatefrompng($tmpPath);  break;
}

if (!$src) {
    echo json_encode(['ok'=>false,'msg'=>'No se pudo procesar la imagen']); exit;
}

$origW = imagesx($src);
$origH = imagesy($src);

// ── Guardar logo.png ─────────────────────────────────────────
$maxW  = 400;
$ratio = $origW > $maxW ? $maxW / $origW : 1;
$newW  = (int)($origW * $ratio);
$newH  = (int)($origH * $ratio);

$png = imagecreatetruecolor($newW, $newH);
imagealphablending($png, false);
imagesavealpha($png, true);
$transparent = imagecolorallocatealpha($png, 0, 0, 0, 127);
imagefilledrectangle($png, 0, 0, $newW, $newH, $transparent);
imagealphablending($png, true);
imagecopyresampled($png, $src, 0, 0, 0, 0, $newW, $newH, $origW, $origH);

$pngPath = $destDir . 'logo.png';

// FIX: escribir a temporal y rename atómico para evitar race condition
$tmpPng = $pngPath . '.tmp.' . uniqid();
if (!imagepng($png, $tmpPng, 9)) {
    imagedestroy($png);
    imagedestroy($src);
    echo json_encode(['ok'=>false,'msg'=>'No se pudo guardar logo.png']); exit;
}
imagedestroy($png);
rename($tmpPng, $pngPath);

// ── Generar logo.ico (32×32 y 16×16) ────────────────────────
$icoPath = $destDir . 'logo.ico';
$sizes   = [32, 16];
$images  = [];

foreach ($sizes as $size) {
    $ico = imagecreatetruecolor($size, $size);
    imagealphablending($ico, false);
    imagesavealpha($ico, true);
    $transp = imagecolorallocatealpha($ico, 0, 0, 0, 127);
    imagefilledrectangle($ico, 0, 0, $size, $size, $transp);
    imagealphablending($ico, true);
    imagecopyresampled($ico, $src, 0, 0, 0, 0, $size, $size, $origW, $origH);

    // FIX: usar archivo temporal en lugar de ob_start para capturar PNG
    $tmpIco = tempnam(sys_get_temp_dir(), 'ico_');
    imagepng($ico, $tmpIco);
    $images[] = file_get_contents($tmpIco);
    unlink($tmpIco);
    imagedestroy($ico);
}

imagedestroy($src);

// Construir .ico manualmente
$numImages  = count($images);
$headerSize = 6;
$dirSize    = 16 * $numImages;
$offset     = $headerSize + $dirSize;

$icoData = pack('vvv', 0, 1, $numImages);

foreach ($images as $i => $imgData) {
    $dim      = $sizes[$i];
    $icoData .= pack('CCCCvvVV',
        $dim >= 256 ? 0 : $dim,
        $dim >= 256 ? 0 : $dim,
        0, 0, 1, 32,
        strlen($imgData),
        $offset
    );
    $offset += strlen($imgData);
}

foreach ($images as $imgData) {
    $icoData .= $imgData;
}

if (file_put_contents($icoPath, $icoData) === false) {
    echo json_encode([
        'ok'  => true,
        'msg' => 'Logo guardado. No se pudo generar el .ico (permiso de escritura)',
        'url' => '/assets/img/logo.png?v=' . time()
    ]); exit;
}

echo json_encode([
    'ok'  => true,
    'msg' => 'Logo actualizado correctamente',
    'url' => '/assets/img/logo.png?v=' . time()
]);