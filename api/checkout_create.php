<?php
// api/checkout_create.php — Adaptado a tu schema (sin pago online)
declare(strict_types=1);

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/../config/db.php';

header('Content-Type: application/json; charset=utf-8');

// (Opcional) CSRF si envías token desde el form
// if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf'] ?? '')) {
//   http_response_code(400);
//   echo json_encode(['ok' => false, 'error' => 'CSRF inválido']);
//   exit;
// }

$raw = file_get_contents('php://input');
$data = json_decode($raw ?? '', true);

$name    = trim($data['name']    ?? '');
$email   = trim($data['email']   ?? '');
$phone   = trim($data['phone']   ?? '');
$address = trim($data['address'] ?? '');
$city    = trim($data['city']    ?? ''); // opcional
$zip     = trim($data['zip']     ?? ''); // opcional
$notes   = trim($data['notes']   ?? '');
$items   = is_array($data['items'] ?? null) ? $data['items'] : [];

if (!$name || !$email || !$address || !count($items)) {
  http_response_code(422);
  echo json_encode(['ok' => false, 'error' => 'Datos incompletos']);
  exit;
}

// Normaliza ítems para order_items (tu tabla requiere: product_id, name, price, qty, subtotal)
$normItems = [];
foreach ($items as $it) {
  $pid   = (int)($it['product_id'] ?? $it['id'] ?? 0);
  $qty   = max(1, (int)($it['qty'] ?? 1));
  $price = (float)($it['price'] ?? 0);
  $iname = trim((string)($it['name'] ?? ''));
  if ($pid > 0) {
    $normItems[] = [
      'product_id' => $pid,
      'name'       => $iname,
      'qty'        => $qty,
      'price'      => $price,
      'subtotal'   => $qty * $price,
    ];
  }
}

if (!count($normItems)) {
  http_response_code(422);
  echo json_encode(['ok' => false, 'error' => 'Carrito vacío o sin IDs válidos']);
  exit;
}

// Total del pedido
$total = 0.0;
foreach ($normItems as $it) { $total += $it['subtotal']; }

$pdo = null;

try {
  $pdo = getConnection();
  $pdo->beginTransaction();

  // INSERT en orders según tu schema real
  // columns: id, customer_name, email, phone, address, city, zip, notes, pay_method, total_amount, created_at, status
  $stmt = $pdo->prepare("
    INSERT INTO orders
      (customer_name, email, phone, address, city, zip, notes, pay_method, total_amount, created_at, status)
    VALUES
      (:customer_name, :email, :phone, :address, :city, :zip, :notes, :pay_method, :total_amount, NOW(), :status)
  ");
  $stmt->execute([
    ':customer_name' => $name,
    ':email'         => $email,
    ':phone'         => $phone,
    ':address'       => $address,
    ':city'          => $city,
    ':zip'           => $zip,
    ':notes'         => $notes,
    ':pay_method'    => 'offline',   // sin cobro por la web
    ':total_amount'  => $total,
    ':status'        => 'pendiente', // flujo: pendiente → confirmado → enviado
  ]);

  $orderId = (int)$pdo->lastInsertId();

  // INSERT en order_items
  $stmtItem = $pdo->prepare("
    INSERT INTO order_items (order_id, product_id, name, price, qty, subtotal)
    VALUES (:order_id, :product_id, :name, :price, :qty, :subtotal)
  ");
  foreach ($normItems as $it) {
    $stmtItem->execute([
      ':order_id'   => $orderId,
      ':product_id' => $it['product_id'],
      ':name'       => $it['name'],
      ':price'      => $it['price'],
      ':qty'        => $it['qty'],
      ':subtotal'   => $it['subtotal'],
    ]);
  }

  $pdo->commit();
  echo json_encode(['ok' => true, 'order_id' => $orderId, 'total' => $total]);

} catch (Throwable $e) {
  if ($pdo instanceof PDO && $pdo->inTransaction()) {
    $pdo->rollBack();
  }
  http_response_code(500);
  // Mostrar detalle si DEBUG=true
  if (defined('DEBUG') && DEBUG) {
    echo json_encode(['ok' => false, 'error' => 'Excepción en checkout', 'detail' => $e->getMessage()]);
  } else {
    echo json_encode(['ok' => false, 'error' => 'Error al registrar pedido']);
  }
}
