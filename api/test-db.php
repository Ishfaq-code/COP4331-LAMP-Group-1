<?php

require_once __DIR__ . '/db.php';

header('Content-Type: application/json; charset=utf-8');

try {
    $db = getDB();
    $database = $db->query('SELECT DATABASE()')->fetchColumn();

    echo json_encode([
        'success' => true,
        'database' => $database,
    ]);
} catch (Throwable $e) {
    http_response_code(500);

    echo json_encode([
        'success' => false,
        'error' => 'Database test failed',
    ]);
}
