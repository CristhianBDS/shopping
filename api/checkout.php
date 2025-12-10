<?php
// api/checkout.php — Checkout rápido desde carrito (sin formulario largo)
declare(strict_types=1);

require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../inc/auth.php';

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');

// 1) Método
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'ok'   => false,
        'error'=> 'Método no permitido',
    ]);
    exit;
}

// 2) Usuario logueado
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode([
        'ok'   => false,
        'error'=> 'Debes iniciar sesión para finalizar la compra.',
    ]);
    exit;
}

// 3) Leer JSON
$raw  = file_get_contents('php://input') ?: '';
$data = json_decode($raw, true);

if (!is_array($data)) {
    http_response_code(400);
    echo json_encode([
        'ok'   => false,
        'error'=> 'JSON inválido.',
    ]);
    exit;
}

$cart      = $data['cart'] ?? [];
$notes     = trim((string)($data['notes'] ?? ''));
$payMethod = trim((string)($data['pay_method'] ?? 'pendiente'));

if (!is_array($cart) || count($cart) === 0) {
    http_response_code(400);
    echo json_encode([
        'ok'   => false,
        'error'=> 'El carrito está vacío.',
    ]);
    exit;
}

// Usuario actual
$user    = currentUser();
$userId  = $user['id'] ?? null;
$customer_name  = $user['name']  ?? 'Cliente registrado';
$customer_email = $user['email'] ?? '';
$customer_phone = $user['phone'] ?? '';

// 4) Conexión PDO
$pdo = getConnection();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

try {
    $pdo->beginTransaction();

    $total = 0.0;
    $items = [];

    $stmtProd = $pdo->prepare("
        SELECT id, name, price, stock, is_active
        FROM products
        WHERE id = ?
        FOR UPDATE
    ");

    foreach ($cart as $line) {
        $pid = (int)($line['id'] ?? $line['product_id'] ?? 0);
        $qty = (int)($line['qty'] ?? 1);

        if ($pid <= 0 || $qty <= 0) {
            throw new RuntimeException('Producto o cantidad inválidos en el carrito.');
        }

        $stmtProd->execute([$pid]);
        $product = $stmtProd->fetch(PDO::FETCH_ASSOC);

        if (!$product) {
            throw new RuntimeException("El producto con ID {$pid} no existe.");
        }
        if ((int)$product['is_active'] !== 1) {
            throw new RuntimeException("El producto \"{$product['name']}\" no está activo.");
        }

        $stock = (int)($product['stock'] ?? 0);
        if ($stock < $qty) {
            throw new RuntimeException(
                "Stock insuficiente para \"{$product['name']}\". Disponible: {$stock}, solicitado: {$qty}."
            );
        }

        $unitPrice = (float)$product['price'];
        $lineTotal = $unitPrice * $qty;
        $total    += $lineTotal;

        $items[] = [
            'product_id' => (int)$product['id'],
            'name'       => (string)$product['name'],
            'price'      => $unitPrice,
            'qty'        => $qty,
            'subtotal'   => $lineTotal,
            'stock_new'  => $stock - $qty,
        ];
    }

    // Insertar pedido
    $stmtOrder = $pdo->prepare("
        INSERT INTO orders
          (user_id, customer_name, email, phone, address, city, zip, notes, pay_method, total_amount, status, created_at)
        VALUES
          (:user_id, :customer_name, :email, :phone, '', '', '', :notes, :pay_method, :total_amount, :status, NOW())
    ");

    $stmtOrder->execute([
        ':user_id'       => $userId,
        ':customer_name' => $customer_name,
        ':email'         => $customer_email,
        ':phone'         => $customer_phone,
        ':notes'         => $notes,
        ':pay_method'    => $payMethod,
        ':total_amount'  => $total,
        ':status'        => 'pendiente',
    ]);

    $orderId = (int)$pdo->lastInsertId();

    // Insertar items
    $stmtItem = $pdo->prepare("
        INSERT INTO order_items
          (order_id, product_id, name, price, qty, subtotal)
        VALUES
          (:order_id, :product_id, :name, :price, :qty, :subtotal)
    ");

    foreach ($items as $it) {
        $stmtItem->execute([
            ':order_id'   => $orderId,
            ':product_id' => $it['product_id'],
            ':name'       => $it['name'],
            ':price'      => $it['price'],
            ':qty'        => $it['qty'],
            ':subtotal'   => $it['subtotal'],
        ]);
    }

    // Actualizar stock
    $stmtStock = $pdo->prepare("
        UPDATE products
        SET stock = :stock_new, updated_at = NOW()
        WHERE id = :id
    ");

    foreach ($items as $it) {
        $stmtStock->execute([
            ':stock_new' => max(0, (int)$it['stock_new']),
            ':id'        => $it['product_id'],
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
        'error' => $e->getMessage(),
    ]);
    exit;
}
