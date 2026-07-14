<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/_auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    admin_json_error(405, 'Méthode non autorisée.');
}

require_admin_token();

$raw = file_get_contents('php://input');
$data = json_decode($raw ?: '{}', true);
$username = is_array($data) ? trim((string) ($data['username'] ?? '')) : '';
$expectedUser = trim((string) (getenv('FATYSTYLE_ADMIN_USER') ?: 'admin'));

if ($username === '' || !hash_equals($expectedUser, $username)) {
    admin_json_error(403, 'Identifiants incorrects.');
}

echo json_encode(['ok' => true]);
