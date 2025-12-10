<?php
// api/checkout_create.php — Crear pedido y descontar stock
declare(strict_types=1);

require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../inc/auth.php';

header('Content-Type: application/json; charset=utf-8');

// Solo aceptamos POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  http_response_code(405);
  echo json_encode(['ok' => false, 'error' => 'Método no permitido']);
  exit;
}

// Solo usuarios logueados (por seguridad extra)
if (!isLoggedIn()) {
  http_response_code(401);
  echo json_encode(['ok' => false, 'error' => 'Debes iniciar sesión para finalizar la compra.']);
  exit;
}

// Leer JSON del body
$raw  = file_get_contents('php://input') ?: '';
$data = json_decode($raw, true);

if (!is_array($data)) {
  http_response_code(400);
  echo json_encode(['ok' => false, 'error' => 'Payload inválido.']);
  exit;
}

// Campos básicos
$name    = trim((string)($data['name']    ?? ''));
$email   = trim((string)($data['email']   ?? ''));
$phone   = trim((string)($data['phone']   ?? ''));
$address = trim((string)($data['address'] ?? ''));
$notes   = trim((string)($data['notes']   ?? ''));
$items   = $data['items'] ?? [];

if ($name === '' || $email === '' || $address === '') {
  http_response_code(400);
  echo json_encode(['ok' => false, 'error' => 'Nombre, email y dirección son obligatorios.']);
  exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
  http_response_code(400);
  echo json_encode(['ok' => false, 'error' => 'Email no válido.']);
  exit;
}

if (!is_array($items) || count($items) === 0) {
  http_response_code(400);
  echo json_encode(['ok' => false, 'error' => 'El carrito está vacío.']);
  exit;
}

// Usuario actual para guardar user_id en orders
$user    = currentUser();
$userId  = $user['id'] ?? null;

$pdo = getConnection();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

try {
  $pdo->beginTransaction();

  // Normalizamos items y comprobamos stock
  $cleanItems = [];
  $total = 0.0;

  $stmtProd = $pdo->prepare("
    SELECT id, name, price, stock, is_active
    FROM products
    WHERE id = ?
    FOR UPDATE
  ");

  foreach ($items as $it) {
    $pid = (int)($it['product_id'] ?? $it['id'] ?? 0);
    $qty = (int)($it['qty'] ?? 1);

    if ($pid <= 0 || $qty <= 0) {
      throw new RuntimeException('Producto o cantidad inválidos en el carrito.');
    }

    // Cargamos producto y bloqueamos la fila
    $stmtProd->execute([$pid]);
    $prod = $stmtProd->fetch(PDO::FETCH_ASSOC);

    if (!$prod) {
      throw new RuntimeException("El producto con ID {$pid} no existe.");
    }
    if ((int)$prod['is_active'] !== 1) {
      throw new RuntimeException("El producto \"{$prod['name']}\" no está activo.");
    }

    $stock = (int)($prod['stock'] ?? 0);
    if ($stock < $qty) {
      throw new RuntimeException(
        "Stock insuficiente para \"{$prod['name']}\". Disponible: {$stock}, solicitado: {$qty}."
      );
    }

    $price = (float)$prod['price'];
    $sub   = $price * $qty;
    $total += $sub;

    $cleanItems[] = [
      'product_id' => (int)$prod['id'],
      'name'       => (string)$prod['name'],
      'price'      => $price,
      'qty'        => $qty,
      'subtotal'   => $sub,
      'stock_new'  => $stock - $qty, // para luego actualizar
    ];
  }

  // =============================
  // Insertar pedido en orders
  // =============================

  $stmtOrder = $pdo->prepare("
    INSERT INTO orders
      (user_id, customer_name, email, phone, address, notes, total_amount, status, created_at)
    VALUES
      (:user_id, :name, :email, :phone, :address, :notes, :total_amount, :status, NOW())
  ");

  $stmtOrder->execute([
    ':user_id'      => $userId,
    ':name'         => $name,
    ':email'        => $email,
    ':phone'        => $phone,
    ':address'      => $address,
    ':notes'        => $notes,
    ':total_amount' => $total,
    ':status'       => 'pendiente',
  ]);

  $orderId = (int)$pdo->lastInsertId();

  // Detalle de pedido
  $stmtItem = $pdo->prepare("
    INSERT INTO order_items
      (order_id, product_id, name, price, qty, subtotal)
    VALUES
      (:order_id, :product_id, :name, :price, :qty, :subtotal)
  ");

  foreach ($cleanItems as $ci) {
    $stmtItem->execute([
      ':order_id'   => $orderId,
      ':product_id' => $ci['product_id'],
      ':name'       => $ci['name'],
      ':price'      => $ci['price'],
      ':qty'        => $ci['qty'],
      ':subtotal'   => $ci['subtotal'],
    ]);
  }

  // Actualizar stock en products
  $stmtStock = $pdo->prepare("
    UPDATE products
    SET stock = :stock_new, updated_at = NOW()
    WHERE id = :id
  ");

  foreach ($cleanItems as $ci) {
    $stmtStock->execute([
      ':stock_new' => max(0, (int)$ci['stock_new']),
      ':id'        => $ci['product_id'],
    ]);
  }

  $pdo->commit();

  echo json_encode([
    'ok'       => true,
    'order_id' => $orderId,
  ]);
  exit;

} catch (Throwable $e) {
  if ($pdo->inTransaction()) {
    $pdo->rollBack();
  }

  http_response_code(400);
  echo json_encode([
    'ok'    => false,
    'error' => $e->getMessage(), // para poder ver el motivo real en la consola
  ]);
  exit;
}
