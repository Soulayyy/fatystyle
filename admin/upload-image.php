<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

// Protection temporaire légère pour l'admin front-end.
// À remplacer par une vraie authentification serveur si le site devient sensible.
if (($_SERVER['HTTP_X_FATY_ADMIN'] ?? '') !== 'faty-style-admin-2026') {
    http_response_code(403);
    echo json_encode(['ok' => false, 'message' => 'Accès refusé.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'message' => 'Méthode non autorisée.']);
    exit;
}

if (empty($_FILES['image']) || !is_uploaded_file($_FILES['image']['tmp_name'])) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'message' => 'Aucune image reçue.']);
    exit;
}

$file = $_FILES['image'];
if ($file['size'] > 5 * 1024 * 1024) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'message' => 'Image trop lourde. Taille max : 5 Mo.']);
    exit;
}

$finfo = new finfo(FILEINFO_MIME_TYPE);
$mime = $finfo->file($file['tmp_name']);
$allowed = [
    'image/jpeg' => 'jpg',
    'image/png' => 'png',
    'image/webp' => 'webp',
    'image/avif' => 'avif'
];

if (!isset($allowed[$mime])) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'message' => 'Format refusé. Utiliser jpg, png, webp ou avif.']);
    exit;
}

$root = realpath(__DIR__ . '/..');
$dir = $root . '/assets/images/admin';
if (!is_dir($dir)) {
    mkdir($dir, 0755, true);
}

$name = 'admin-' . date('Ymd-His') . '-' . bin2hex(random_bytes(4)) . '.' . $allowed[$mime];
$target = $dir . '/' . $name;

if (!move_uploaded_file($file['tmp_name'], $target)) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'message' => 'Upload impossible.']);
    exit;
}

echo json_encode([
    'ok' => true,
    'path' => 'assets/images/admin/' . $name,
    'message' => 'Image importée.'
]);
