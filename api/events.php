<?php
// api/events.php — lista de eventos en formato FullCalendar (GET)
require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/../config/db.php';

header('Content-Type: application/json; charset=utf-8');

try {
  $pdo = getConnection();

  // Rango opcional ?from=YYYY-MM-DD&to=YYYY-MM-DD
  $from = $_GET['from'] ?? null;
  $to   = $_GET['to']   ?? null;

  $sql = "SELECT id, title, type, start_at, end_at, all_day, color FROM events";
  $params = [];

  if ($from && $to) {
    $sql .= " WHERE (start_at <= ? AND (end_at IS NULL OR end_at >= ?))";
    $params = [$to . ' 23:59:59', $from . ' 00:00:00'];
  }
  $sql .= " ORDER BY start_at ASC";

  $st = $pdo->prepare($sql);
  $st->execute($params);
  $rows = $st->fetchAll(PDO::FETCH_ASSOC);

  // Adaptar a estructura que espera FullCalendar
  $events = array_map(function($r) {
    return [
      'id'    => (int)$r['id'],
      'title' => $r['title'],
      'start' => date('c', strtotime($r['start_at'])),
      'end'   => $r['end_at'] ? date('c', strtotime($r['end_at'])) : null,
      'allDay'=> (bool)$r['all_day'],
      'color' => $r['color'] ?: null,
    ];
  }, $rows);

  echo json_encode($events);
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['error' => true, 'message' => $e->getMessage()]);
}
