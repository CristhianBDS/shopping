<?php
// api/checkout_create.php — versión ajustada a tu esquema real
declare(strict_types=1);

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/bootstrap.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  http_response_code(405);
  echo json_encode(['ok'=>false, 'error'=>'Método no permitido']); exit;
}

$raw = file_get_contents('php://input');
$data = json_decode($raw, true);
if (!is_array($data)) {
  http_response_code(400);
  echo json_encode(['ok'=>false, 'error'=>'JSON inválido']); exit;
}

$customer = $data['customer'] ?? [];
$items    = $data['items']    ?? [];

function fail(int $code, string $msg) {
  http_response_code($code);
  echo json_encode(['ok'=>false, 'error'=>$msg]); exit;
}

// Validar campos obligatorios
$req = ['fullname','email','phone','address','city','zip'];
foreach ($req as $k) {
  if (!isset($customer[$k]) || trim((string)$customer[$k])==='') {
    fail(400, "Campo requerido: $k");
  }
}
if (!filter_var((string)$customer['email'], FILTER_VALIDATE_EMAIL)) {
  fail(400, "Email inválido");
}
if (!is_array($items) || count($items)===0) fail(400, "Carrito vacío");
foreach ($items as $it) {
  if (!isset($it['id'], $it['qty'])) fail(400, "Ítems inválidos");
  if ((int)$it['qty'] < 1 || (int)$it['qty'] > 99) fail(400, "Cantidad fuera de rango");
}

try {
  $pdo = getConnection();
  $pdo->beginTransaction();

  // Releer precios desde products
  $ids = array_map(fn($it) => (int)$it['id'], $items);
  $ph  = implode(',', array_fill(0, count($ids), '?'));
  $stmt = $pdo->prepare("SELECT id, name, price, is_active FROM products WHERE id IN ($ph)");
  $stmt->execute($ids);
  $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
  $byId = [];
  foreach ($rows as $r) { $byId[(int)$r['id']] = $r; }

  $total = 0.0;
  $cleanItems = [];
  foreach ($items as $it) {
    $pid = (int)$it['id'];
    $qty = (int)$it['qty'];
    if (!isset($byId[$pid])) fail(400, "Producto $pid inexistente");
    $p = $byId[$pid];
    if ((int)$p['is_active'] !== 1) fail(400, "Producto inactivo: ".$p['name']);
    $price = (float)$p['price'];
    $line  = $price * $qty;
    $total += $line;

    $cleanItems[] = [
      'product_id' => $pid,
      'name'       => (string)$p['name'],
      'price'      => $price,
      'qty'        => $qty,
      'subtotal'   => $line
    ];
  }

  // Insertar pedido en orders
  $sqlO = "INSERT INTO orders
    (customer_name, email, phone, address, city, zip, notes, pay_method, total_amount, created_at, status)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), ?)";
  $pdo->prepare($sqlO)->execute([
    (string)$customer['fullname'],
    (string)$customer['email'],
    (string)$customer['phone'],
    (string)$customer['address'],
    (string)$customer['city'],
    (string)$customer['zip'],
    (string)($customer['notes'] ?? ''),
    'tarjeta',
    number_format($total, 2, '.', ''),
    'pendiente'
  ]);
  $orderId = (int)$pdo->lastInsertId();

  // Insertar ítems en order_items
  $sqlI = "INSERT INTO order_items (order_id, product_id, name, price, qty, subtotal)
           VALUES (?, ?, ?, ?, ?, ?)";
  $stI = $pdo->prepare($sqlI);
  foreach ($cleanItems as $ci) {
    $stI->execute([
      $orderId,
      $ci['product_id'],
      $ci['name'],
      $ci['price'],
      $ci['qty'],
      $ci['subtotal']
    ]);
  }

  $pdo->commit();
  echo json_encode(['ok'=>true, 'order_id'=>$orderId]); exit;

} catch (Throwable $e) {
  if (isset($pdo) && $pdo->inTransaction()) $pdo->rollBack();
  http_response_code(500);
  echo json_encode(['ok'=>false, 'error'=>$e->getMessage()]); exit;
}
