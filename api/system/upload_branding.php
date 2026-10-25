<?php
header('Content-Type: application/json');
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';

require_master_admin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_FILES['asset'])) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'An image file is required']);
    exit;
}

$kind = $_POST['kind'] ?? '';
$allowedKinds = [
    'dashboard_logo' => 'logo',
    'login_logo' => 'logo',
    'login_background' => 'background',
];
if (!isset($allowedKinds[$kind])) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Invalid branding asset']);
    exit;
}

$file = $_FILES['asset'];
if (!is_array($file) || $file['error'] !== UPLOAD_ERR_OK) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Image upload failed']);
    exit;
}

$maxBytes = $allowedKinds[$kind] === 'background' ? 8 * 1024 * 1024 : 2 * 1024 * 1024;
if ($file['size'] <= 0 || $file['size'] > $maxBytes) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Image is too large']);
    exit;
}

$imageInfo = @getimagesize($file['tmp_name']);
$allowedMimeTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
if (!$imageInfo || !in_array($imageInfo['mime'], $allowedMimeTypes, true)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Only JPG, PNG, GIF, and WebP images are supported']);
    exit;
}

$extension = [
    'image/jpeg' => 'jpg',
    'image/png' => 'png',
    'image/gif' => 'gif',
    'image/webp' => 'webp',
][$imageInfo['mime']];
$uploadDirectory = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'branding';
if (!is_dir($uploadDirectory) && !mkdir($uploadDirectory, 0755, true)) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Unable to create upload directory']);
    exit;
}

$filename = $kind . '_' . bin2hex(random_bytes(12)) . '.' . $extension;
$destination = $uploadDirectory . DIRECTORY_SEPARATOR . $filename;
if (!move_uploaded_file($file['tmp_name'], $destination)) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Unable to save image']);
    exit;
}

echo json_encode([
    'ok' => true,
    'url' => '../uploads/branding/' . $filename,
]);
