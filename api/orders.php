<?php
// api/orders.php — Acciones sobre pedidos (admin)
require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../inc/auth.php';   // debe exponer require_admin()
require_once __DIR__ . '/../inc/flash.php';  // flash_success / flash_error / flash_info

header('X-Content-Type-Options: nosniff');

// ------------- RESPUESTA FLEXIBLE (define primero para Intelephense) -------------
/**
 * Si es fetch JSON → responde JSON.
 * Si es form POST → flash + redirect al detalle del pedido.
 */
function respond($ok, $msg, $orderId) {
  $accept    = isset($_SERVER['HTTP_ACCEPT']) ? $_SERVER['HTTP_ACCEPT'] : '';
  $wantsJson = (stripos($accept, 'application/json') !== false)
               || (stripos($_SERVER['CONTENT_TYPE'] ?? '', 'application/json') !== false);

  if ($wantsJson) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['ok' => (bool)$ok, 'message' => (string)$msg, 'order_id' => (int)$orderId]);
    return;
  }

  if ($ok) flash_success($msg); else flash_error($msg);
  $base = defined('BASE_URL') ? BASE_URL : '/shopping';
  $dest = ($orderId > 0) ? ($base . '/admin/pedido.php?id=' . (int)$orderId) : ($base . '/admin/pedidos.php');
  header('Location: ' . $dest);
  exit;
}

// ------------- ROUTER SENCILLO -------------
$action = $_GET['action'] ?? '';

switch ($action) {
  case 'update_status':
    update_status();
    break;

  default:
    http_response_code(400);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['ok' => false, 'error' => 'Acción inválida']);
    break;
}

// ------------- ACCIONES -------------
/**
 * Cambia el estado de un pedido con flujo válido:
 * pendiente -> confirmado -> enviado (o cancelado antes del final)
 */
function update_status() {
  require_admin(); // redirige si no es admin

  // Acepta form POST o JSON (fetch)
  $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
  $isJson = stripos($contentType, 'application/json') !== false;

  if ($isJson) {
    $raw     = file_get_contents('php://input') ?: '';
    $data    = json_decode($raw, true) ?: [];
    $csrf    = (string)($data['csrf'] ?? '');
    $orderId = (int)($data['order_id'] ?? 0);
    $status  = trim((string)($data['status'] ?? ''));
  } else {
    $csrf    = (string)($_POST['csrf'] ?? '');
    $orderId = isset($_POST['order_id']) ? (int)$_POST['order_id'] : 0;
    $status  = isset($_POST['status']) ? trim((string)$_POST['status']) : '';
  }

  // CSRF
  if (!hash_equals($_SESSION['csrf'] ?? '', $csrf)) {
    respond(false, 'CSRF inválido', $orderId);
    return;
  }

  if ($orderId <= 0 || $status === '') {
    respond(false, 'Datos inválidos', $orderId);
    return;
  }

  $allowedNext = [
    'pendiente'  => ['confirmado', 'cancelado'],
    'confirmado' => ['enviado', 'cancelado'],
    'enviado'    => [],
    'cancelado'  => [],
  ];

  $pdo = getConnection();

  // Obtiene estado actual
  $stmt = $pdo->prepare('SELECT status FROM orders WHERE id = ?');
  $stmt->execute([$orderId]);
  $current = $stmt->fetchColumn();

  if ($current === false) {
    respond(false, 'Pedido no encontrado', $orderId);
    return;
  }

  $current = (string)$current;
  $allowed = $allowedNext[$current] ?? [];

  if (!in_array($status, $allowed, true)) {
    respond(false, 'Transición no permitida: "' . $current . '" → "' . $status . '"', $orderId);
    return;
  }

  // Actualiza
  $up = $pdo->prepare('UPDATE orders SET status = :s WHERE id = :id');
  $up->execute([':s' => $status, ':id' => $orderId]);

  respond(true, 'Pedido actualizado a "' . $status . '"', $orderId);
}
