<?php
// shopping/api/orders.php
declare(strict_types=1);

// --- Cabeceras: JSON + CORS (útil si llamas desde /public en el mismo host o futuro host) ---
header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Origin: *');            // ajusta en prod
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Preflight CORS
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') {
  http_response_code(204);
  exit;
}

// --- Carga de config/DB según tu estructura ---
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/db.php'; // <- aquí vive getConnection()

// --- Helper para responder ---
function respond(int $code, array $payload): never {
  http_response_code($code);
  echo json_encode($payload, JSON_UNESCAPED_UNICODE);
  exit;
}

// --- Sólo aceptamos POST ---
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
if ($method !== 'POST') {
  respond(405, ['ok' => false, 'error' => 'Método no permitido (usa POST)']);
}

// --- Leer y validar JSON ---
$raw = file_get_contents('php://input');
$data = json_decode($raw, true);

if (!is_array($data)) {
  respond(400, ['ok' => false, 'error' => 'Cuerpo JSON inválido']);
}

// Esperamos { customer: {...}, items: [...], total: number/string, pay?:string }
$customer = $data['customer'] ?? [];
$items    = $data['items'] ?? [];
$totalIn  = $data['total'] ?? 0;
$pay      = $data['pay']    ?? ($customer['pay'] ?? 'tarjeta');

// Normalizamos total si llegó como "€ 123,45" o similar
$normalizeMoney = function ($v): float {
  if (is_numeric($v)) return (float)$v;
  $s = (string)$v;
  // quita símbolos y deja separadores
  $s = preg_replace('/[^\d,.\-]/', '', $s) ?? '0';
  // si trae coma como decimal y punto de miles => quita punto y cambia coma por punto
  if (strpos($s, ',') !== false && strpos($s, '.') !== false) {
    $s = str_replace('.', '', $s);
    $s = str_replace(',', '.', $s);
  } else {
    // si sólo trae coma => úsala como decimal
    if (strpos($s, ',') !== false) $s = str_replace(',', '.', $s);
  }
  return (float)$s;
};
$total = $normalizeMoney($totalIn);

// Validaciones mínimas
$required = ['fullname','email','phone','address','city','zip'];
foreach ($required as $k) {
  if (!isset($customer[$k]) || trim((string)$customer[$k]) === '') {
    respond(422, ['ok'=>false, 'error'=>"Campo requerido: $k"]);
  }
}
if (!is_array($items) || count($items) === 0) {
  respond(422, ['ok'=>false, 'error'=>'El carrito está vacío']);
}

// Validar método de pago (debe coincidir con tu ENUM)
$allowedPays = ['tarjeta','transferencia','contraentrega'];
if (!in_array($pay, $allowedPays, true)) {
  $pay = 'tarjeta';
}

// --- Insert en BD ---
$pdo = null; // IMPORTANTE para que exista en el catch
try {
  $pdo = getConnection();
  $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
  $pdo->beginTransaction();

  // Insertar order
  $qOrder = $pdo->prepare("
    INSERT INTO orders
      (customer_name, email, phone, address, city, zip, notes, pay_method, total_amount)
    VALUES
      (:name, :email, :phone, :addr, :city, :zip, :notes, :pay, :total)
  ");
  $qOrder->execute([
    ':name'  => (string)$customer['fullname'],
    ':email' => (string)$customer['email'],
    ':phone' => (string)$customer['phone'],
    ':addr'  => (string)$customer['address'],
    ':city'  => (string)$customer['city'],
    ':zip'   => (string)$customer['zip'],
    ':notes' => isset($customer['notes']) ? (string)$customer['notes'] : null,
    ':pay'   => $pay,
    ':total' => $total,
  ]);
  $orderId = (int)$pdo->lastInsertId();

  // Insertar items
  $qItem = $pdo->prepare("
    INSERT INTO order_items
      (order_id, product_id, name, price, qty, subtotal)
    VALUES
      (:oid, :pid, :name, :price, :qty, :sub)
  ");

  foreach ($items as $p) {
    $pid   = isset($p['id'])    ? (int)$p['id'] : 0;
    $name  = isset($p['name'])  ? (string)$p['name'] : '';
    $price = isset($p['price']) ? (float)$p['price'] : 0.0;
    $qty   = isset($p['qty'])   ? (int)$p['qty']   : 1;
    $sub   = $price * $qty;

    $qItem->execute([
      ':oid'   => $orderId,
      ':pid'   => $pid,
      ':name'  => $name,
      ':price' => $price,
      ':qty'   => $qty,
      ':sub'   => $sub,
    ]);
  }

  $pdo->commit();
  respond(201, ['ok'=>true, 'order_id'=>$orderId]);

} catch (Throwable $e) {
  if ($pdo instanceof PDO && $pdo->inTransaction()) {
    $pdo->rollBack();
  }
  // En desarrollo es útil ver el detalle:
  respond(500, ['ok'=>false, 'error'=>'Error al guardar pedido', 'detail'=>$e->getMessage()]);
}
