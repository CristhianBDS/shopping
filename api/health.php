<?php
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config/db.php';

try {
  $pdo = getConnection(); // PDO
  $pdo->query("SELECT 1"); // simple ping
  echo json_encode(['ok' => true, 'time' => date('Y-m-d H:i:s'), 'db' => 'conectado'], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['ok' => false, 'error' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
