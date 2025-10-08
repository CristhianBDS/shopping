<?php
// admin/pedido.php — Detalle + cambio de estado
require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../inc/auth.php';
require_once __DIR__ . '/../inc/flash.php';

require_admin();

$BASE = defined('BASE_URL') ? BASE_URL : '/shopping';
$PAGE_TITLE = 'Detalle del pedido';

if (empty($_SESSION['csrf'])) {
  $_SESSION['csrf'] = bin2hex(random_bytes(32));
}
$CSRF = $_SESSION['csrf'];

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
  flash_error('ID de pedido inválido');
  header("Location: {$BASE}/admin/pedidos.php");
  exit;
}

$pdo = getConnection();

$stmt = $pdo->prepare("
  SELECT o.id, o.customer_name, o.email, o.phone, o.address, o.city, o.zip,
         o.notes, o.pay_method, o.total_amount, o.created_at, o.status
  FROM orders o
  WHERE o.id = ?
");
$stmt->execute([$id]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$order) {
  flash_error('Pedido no encontrado');
  header("Location: {$BASE}/admin/pedidos.php");
  exit;
}

$stmtI = $pdo->prepare("
  SELECT oi.product_id, oi.name, oi.price, oi.qty, oi.subtotal
  FROM order_items oi
  WHERE oi.order_id = ?
  ORDER BY oi.id ASC
");
$stmtI->execute([$id]);
$items = $stmtI->fetchAll(PDO::FETCH_ASSOC);

// helpers
function status_badge(string $s): string {
  $map = ['pendiente'=>'secondary','confirmado'=>'primary','enviado'=>'success','cancelado'=>'danger'];
  $cls = $map[$s] ?? 'secondary';
  return '<span class="badge bg-' . htmlspecialchars($cls) . '">' . htmlspecialchars(ucfirst($s)) . '</span>';
}
function next_status(?string $s): ?string {
  $s = $s ?: 'pendiente';
  if ($s === 'pendiente')  return 'confirmado';
  if ($s === 'confirmado') return 'enviado';
  return null;
}

include __DIR__ . '/../templates/header.php';

// Si NO renderizas en header.php, descomenta:
// flash_render();
?>
<main class="container py-4">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h4 mb-0">Pedido #<?= htmlspecialchars($order['id']) ?></h1>
    <a class="btn btn-outline-secondary" href="<?= $BASE ?>/admin/pedidos.php">← Volver</a>
  </div>

  <div class="row g-3">
    <section class="col-lg-8">
      <div class="card shadow-sm">
        <div class="card-body">
          <h2 class="h6">Cliente</h2>
          <div class="mb-3">
            <div><strong><?= htmlspecialchars($order['customer_name']) ?></strong></div>
            <div><a href="mailto:<?= htmlspecialchars($order['email']) ?>"><?= htmlspecialchars($order['email']) ?></a></div>
            <?php if (!empty($order['phone'])): ?>
              <div><a href="tel:<?= htmlspecialchars($order['phone']) ?>"><?= htmlspecialchars($order['phone']) ?></a></div>
            <?php endif; ?>
          </div>

          <h2 class="h6">Entrega</h2>
          <div class="mb-3">
            <div><?= htmlspecialchars($order['address']) ?></div>
            <div><?= htmlspecialchars($order['zip'] ?: '') ?> <?= htmlspecialchars($order['city'] ?: '') ?></div>
          </div>

          <?php if (!empty($order['notes'])): ?>
            <h2 class="h6">Notas</h2>
            <p class="mb-3"><?= nl2br(htmlspecialchars($order['notes'])) ?></p>
          <?php endif; ?>

          <h2 class="h6">Resumen</h2>
          <div class="table-responsive">
            <table class="table align-middle">
              <thead>
                <tr>
                  <th>Producto</th>
                  <th class="text-end">Precio</th>
                  <th class="text-end">Cantidad</th>
                  <th class="text-end">Subtotal</th>
                </tr>
              </thead>
              <tbody>
              <?php foreach ($items as $it): ?>
                <tr>
                  <td><?= htmlspecialchars($it['name'] ?: ('ID ' . $it['product_id'])) ?></td>
                  <td class="text-end">€ <?= number_format((float)$it['price'], 2, ',', '.') ?></td>
                  <td class="text-end"><?= (int)$it['qty'] ?></td>
                  <td class="text-end">€ <?= number_format((float)$it['subtotal'], 2, ',', '.') ?></td>
                </tr>
              <?php endforeach; ?>
              </tbody>
              <tfoot>
                <tr>
                  <th colspan="3" class="text-end">Total</th>
                  <th class="text-end">€ <?= number_format((float)$order['total_amount'], 2, ',', '.') ?></th>
                </tr>
              </tfoot>
            </table>
          </div>
        </div>
      </div>
    </section>

    <aside class="col-lg-4">
      <div class="card shadow-sm">
        <div class="card-body">
          <h2 class="h6">Estado del pedido</h2>
          <p class="mb-2">
            <?= status_badge($order['status']) ?>
            <small class="text-muted ms-2">Creado: <?= htmlspecialchars($order['created_at']) ?></small>
          </p>

          <div class="d-grid gap-2">
            <?php if ($ns = next_status($order['status'])): ?>
              <form method="post" action="<?= $BASE ?>/api/orders.php?action=update_status" class="d-grid gap-2">
                <input type="hidden" name="csrf" value="<?= htmlspecialchars($CSRF) ?>">
                <input type="hidden" name="order_id" value="<?= (int)$order['id'] ?>">
                <input type="hidden" name="status" value="<?= htmlspecialchars($ns) ?>">
                <button type="submit" class="btn btn-primary">Marcar como <?= htmlspecialchars($ns) ?></button>
              </form>
            <?php else: ?>
              <div class="alert alert-success mb-2" role="alert">
                No hay más acciones disponibles para este estado.
              </div>
            <?php endif; ?>

            <?php if ($order['status'] !== 'enviado' && $order['status'] !== 'cancelado'): ?>
              <form method="post" action="<?= $BASE ?>/api/orders.php?action=update_status" onsubmit="return confirm('¿Cancelar este pedido?');">
                <input type="hidden" name="csrf" value="<?= htmlspecialchars($CSRF) ?>">
                <input type="hidden" name="order_id" value="<?= (int)$order['id'] ?>">
                <input type="hidden" name="status" value="cancelado">
                <button type="submit" class="btn btn-outline-danger">Cancelar pedido</button>
              </form>
            <?php endif; ?>
          </div>

          <hr>
          <div class="small text-muted">
            Flujo: <code>pendiente → confirmado → enviado</code>. Cancelado es opcional.
          </div>
        </div>
      </div>
    </aside>
  </div>
</main>

<?php include __DIR__ . '/../templates/footer.php'; ?>
