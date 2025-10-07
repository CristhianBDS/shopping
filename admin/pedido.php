<?php
// admin/pedido.php — detalle de un pedido + cambio de estado
declare(strict_types=1);
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/../inc/auth.php';
require_once __DIR__ . '/../inc/flash.php';

$CONTEXT = 'admin';
$PAGE_TITLE = 'Pedido';
requireLogin();

$pdo = getConnection();
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) { http_response_code(400); die('ID inválido'); }

// CSRF panel
if (empty($_SESSION['csrf_admin'])) $_SESSION['csrf_admin'] = bin2hex(random_bytes(32));
$csrf = $_SESSION['csrf_admin'];

// POST: cambiar estado
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $tok = $_POST['csrf'] ?? '';
  if (!hash_equals($_SESSION['csrf_admin'] ?? '', $tok)) {
    flash_error('CSRF inválido.');
    header('Location: ' . BASE_URL . '/admin/pedido.php?id='.$id); exit;
  }
  $status = $_POST['status'] ?? '';
  $allowed = ['pendiente','pagado','enviado','cancelado'];
  if (!in_array($status, $allowed, true)) {
    flash_error('Estado no válido.');
  } else {
    try {
      $pdo->prepare("UPDATE orders SET status=?, updated_at=NOW() WHERE id=?")->execute([$status, $id]);
      flash_success('Estado actualizado a: '.$status);
    } catch (Throwable $e) {
      flash_error('No se pudo actualizar el estado.');
    }
  }
  header('Location: ' . BASE_URL . '/admin/pedido.php?id='.$id); exit;
}

// Cargar pedido + items
$ord = $pdo->prepare("SELECT * FROM orders WHERE id=?");
$ord->execute([$id]);
$o = $ord->fetch(PDO::FETCH_ASSOC);
if (!$o) { http_response_code(404); die('Pedido no encontrado'); }

$it = $pdo->prepare("SELECT product_id, product_name, unit_price, quantity FROM order_items WHERE order_id=?");
$it->execute([$id]);
$items = $it->fetchAll(PDO::FETCH_ASSOC);

function eur($n){ return '€ ' . number_format((float)$n, 2, ',', '.'); }

include __DIR__ . '/../templates/header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <h1 class="h3 mb-0">Pedido #<?= (int)$o['id'] ?></h1>
  <a class="btn btn-outline-secondary" href="<?= BASE_URL ?>/admin/pedidos.php">Volver</a>
</div>

<div class="row g-3">
  <div class="col-lg-7">
    <div class="card shadow-sm">
      <div class="card-body">
        <h5 class="card-title">Items</h5>
        <div class="table-responsive">
          <table class="table table-sm align-middle mb-0">
            <thead><tr><th>Producto</th><th class="text-end">Precio</th><th class="text-end">Cant.</th><th class="text-end">Subtotal</th></tr></thead>
            <tbody>
              <?php
              $sum = 0.0;
              foreach ($items as $r):
                $sub = (float)$r['unit_price'] * (int)$r['quantity'];
                $sum += $sub;
              ?>
              <tr>
                <td><?= htmlspecialchars($r['product_name']) ?></td>
                <td class="text-end"><?= eur($r['unit_price']) ?></td>
                <td class="text-end"><?= (int)$r['quantity'] ?></td>
                <td class="text-end"><?= eur($sub) ?></td>
              </tr>
              <?php endforeach; ?>
            </tbody>
            <tfoot>
              <tr><th colspan="3" class="text-end">Total</th><th class="text-end"><?= eur($sum) ?></th></tr>
            </tfoot>
          </table>
        </div>
      </div>
    </div>
  </div>

  <div class="col-lg-5">
    <div class="card shadow-sm">
      <div class="card-body">
        <h5 class="card-title">Cliente</h5>
        <div class="mb-2 text-muted small"><?= htmlspecialchars($o['created_at']) ?></div>
        <ul class="list-unstyled mb-3">
          <li><strong><?= htmlspecialchars($o['customer_name']) ?></strong></li>
          <li><?= htmlspecialchars($o['email']) ?> · <?= htmlspecialchars($o['phone']) ?></li>
          <li><?= htmlspecialchars($o['address']) ?>, <?= htmlspecialchars($o['city']) ?> (<?= htmlspecialchars($o['zip']) ?>)</li>
          <?php if (trim((string)$o['doc'])!==''): ?><li>Doc: <?= htmlspecialchars($o['doc']) ?></li><?php endif; ?>
          <?php if (trim((string)$o['notes'])!==''): ?><li>Notas: <?= nl2br(htmlspecialchars($o['notes'])) ?></li><?php endif; ?>
        </ul>

        <form method="post" class="d-flex gap-2 align-items-end">
          <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf) ?>">
          <div>
            <label class="form-label">Estado</label>
            <select name="status" class="form-select">
              <?php foreach (['pendiente','pagado','enviado','cancelado'] as $st): ?>
                <option value="<?= $st ?>" <?= $o['status']===$st?'selected':'' ?>><?= ucfirst($st) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <button class="btn btn-primary">Actualizar</button>
        </form>
      </div>
    </div>
  </div>
</div>

<?php include __DIR__ . '/../templates/footer.php'; ?>
