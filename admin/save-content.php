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

$raw = file_get_contents('php://input');
$data = json_decode($raw, true);

if (!is_array($data)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'message' => 'JSON invalide.']);
    exit;
}

$root = realpath(__DIR__ . '/..');
$target = $root . '/data/content.json';
$backupDir = $root . '/data/backups';

if (!is_dir($backupDir)) {
    mkdir($backupDir, 0755, true);
}

if (is_file($target)) {
    copy($target, $backupDir . '/content-' . date('Ymd-His') . '.json');
}

$json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
if ($json === false) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'message' => 'Impossible de sérialiser le contenu.']);
    exit;
}

if (file_put_contents($target, $json . PHP_EOL, LOCK_EX) === false) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'message' => 'Écriture impossible. Vérifier les droits du dossier data/.']);
    exit;
}

echo json_encode(['ok' => true, 'message' => 'Contenu sauvegardé.']);
