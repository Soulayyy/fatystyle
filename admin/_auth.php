<?php
declare(strict_types=1);

function admin_json_error(int $status, string $message): never
{
    http_response_code($status);
    echo json_encode(['ok' => false, 'message' => $message]);
    exit;
}

function require_admin_token(): void
{
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Pragma: no-cache');
    header('X-Content-Type-Options: nosniff');

    $expected = trim((string) getenv('FATYSTYLE_ADMIN_TOKEN'));
    if ($expected === '') {
        admin_json_error(503, 'Administration non configurée sur ce serveur.');
    }

    $provided = (string) ($_SERVER['HTTP_X_FATY_ADMIN'] ?? '');
    if ($provided === '' || !hash_equals($expected, $provided)) {
        admin_json_error(403, 'Identifiants incorrects.');
    }
}
