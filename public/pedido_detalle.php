<?php
// public/pedido_detalle.php — Detalle de un pedido (match por email de la sesión)
require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../inc/flash.php';
require_once __DIR__ . '/../inc/auth.php';

$CONTEXT    = 'public';
$PAGE_TITLE = 'Detalle del pedido';
$BASE       = defined('BASE_URL') ? BASE_URL : '/shopping';

requireLogin();

$user  = currentUser();
$email = (string)$user['email'];
$id    = (int)($_GET['id'] ?? 0);

if ($id <= 0) {
  http_response_code(400);
  die('Pedido inválido');
}

$pdo = getConnection();

/**
 * Tu tabla orders (según captura) tiene:
 * id, customer_name, email, phone, address, city, zip, notes, pay_method, total_amount, created_at, status
 * Verificamos que el pedido pertenezca al email logueado.
 */
$st = $pdo->prepare('
  SELECT id, customer_name, email, phone, address, city, zip, notes,
         pay_method, total_amount, created_at, status
  FROM orders
  WHERE id = ? AND email = ?
  LIMIT 1
');
$st->execute([$id, $email]);
$order = $st->fetch(PDO::FETCH_ASSOC);

if (!$order) {
  http_response_code(404);
  die('Pedido no encontrado');
}

/**
 * Ítems del pedido:
 * 1) Intento directo: order_items(product_name, qty, price)
 * 2) Fallback: JOIN products por product_id
 */
$items = [];
try {
  $q1 = $pdo->prepare('SELECT product_name, qty, price FROM order_items WHERE order_id = ?');
  $q1->execute([$id]);
  $items = $q1->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
  // ignoramos y probamos fallback
}

if (!$items) {
  try {
    $q2 = $pdo->prepare('
      SELECT p.name AS product_name, oi.qty, oi.price
      FROM order_items oi
      JOIN products p ON p.id = oi.product_id
      WHERE oi.order_id = ?
    ');
    $q2->execute([$id]);
    $items = $q2->fetchAll(PDO::FETCH_ASSOC);
  } catch (Throwable $e) {
    // si falla también, quedará vacío y mostraremos un aviso
    $items = [];
  }
}

// Cálculo de total desde ítems (por si quieres comparar con total_amount)
$calcTotal = 0.0;
foreach ($items as $it) {
  $qty   = (int)($it['qty'] ?? 0);
  $price = (float)($it['price'] ?? 0);
  $calcTotal += $qty * $price;
}

include __DIR__ . '/../templates/header.php';
?>
<section class="container py-5">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="mb-0">Pedido #<?= (int)$order['id'] ?></h1>
    <a class="btn btn-outline-secondary" href="<?= $BASE ?>/public/pedidos.php">← Volver a mis pedidos</a>
  </div>

  <div class="row g-4 mb-4">
    <div class="col-md-6">
      <div class="card h-100">
        <div class="card-body">
          <h5 class="card-title">Resumen</h5>
          <p class="mb-1"><strong>Fecha:</strong> <?= htmlspecialchars($order['created_at']) ?></p>
          <p class="mb-1"><strong>Estado:</strong> <span class="badge bg-secondary"><?= htmlspecialchars($order['status']) ?></span></p>
          <p class="mb-0"><strong>Total (guardado):</strong> € <?= number_format((float)$order['total_amount'], 2, ',', '.') ?></p>
          <?php if ($items): ?>
            <small class="text-muted">Total (recalculado): € <?= number_format($calcTotal, 2, ',', '.') ?></small>
          <?php endif; ?>
        </div>
      </div>
    </div>
    <div class="col-md-6">
      <div class="card h-100">
        <div class="card-body">
          <h5 class="card-title">Datos de entrega</h5>
          <p class="mb-1"><strong>Cliente:</strong> <?= htmlspecialchars($order['customer_name']) ?></p>
          <p class="mb-1"><strong>Email:</strong> <?= htmlspecialchars($order['email']) ?></p>
          <p class="mb-1"><strong>Teléfono:</strong> <?= htmlspecialchars($order['phone']) ?></p>
          <p class="mb-1"><strong>Dirección:</strong> <?= htmlspecialchars($order['address']) ?></p>
          <p class="mb-1"><strong>Ciudad:</strong> <?= htmlspecialchars($order['city']) ?></p>
          <p class="mb-1"><strong>CP:</strong> <?= htmlspecialchars($order['zip']) ?></p>
          <p class="mb-0"><strong>Método de pago:</strong> <?= htmlspecialchars($order['pay_method']) ?></p>
        </div>
      </div>
    </div>
  </div>

  <?php if (!$items): ?>
    <div class="alert alert-warning">No hay ítems asociados a este pedido o las columnas no coinciden con el esquema esperado.</div>
  <?php else: ?>
    <div class="table-responsive">
      <table class="table table-sm align-middle">
        <thead>
          <tr>
            <th>Producto</th>
            <th class="text-end">Cantidad</th>
            <th class="text-end">Precio</th>
            <th class="text-end">Subtotal</th>
          </tr>
        </thead>
        <tbody>
        <?php foreach ($items as $it):
          $name = $it['product_name'] ?? 'Producto';
          $qty  = (int)($it['qty'] ?? 0);
          $price = (float)($it['price'] ?? 0);
          $sub = $qty * $price;
        ?>
          <tr>
            <td><?= htmlspecialchars($name) ?></td>
            <td class="text-end"><?= $qty ?></td>
            <td class="text-end">€ <?= number_format($price, 2, ',', '.') ?></td>
            <td class="text-end">€ <?= number_format($sub, 2, ',', '.') ?></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
        <tfoot>
          <tr>
            <th colspan="3" class="text-end">Total</th>
            <th class="text-end">€ <?= number_format($calcTotal, 2, ',', '.') ?></th>
          </tr>
        </tfoot>
      </table>
    </div>
  <?php endif; ?>

  <?php if (!empty($order['notes'])): ?>
    <div class="card mt-4">
      <div class="card-body">
        <h5 class="card-title">Notas del pedido</h5>
        <p class="mb-0"><?= nl2br(htmlspecialchars($order['notes'])) ?></p>
      </div>
    </div>
  <?php endif; ?>
</section>
<?php include __DIR__ . '/../templates/footer.php'; ?>
