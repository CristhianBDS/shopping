<?php
// admin/pedido.php — Detalle de pedido con detección de columnas flexibles
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

// --- CSRF panel (para cambios de estado)
if (empty($_SESSION['csrf_admin'])) $_SESSION['csrf_admin'] = bin2hex(random_bytes(32));
$csrf = $_SESSION['csrf_admin'];

// --- util: detectar columna existente en una tabla
function pickColumn(PDO $pdo, string $table, array $cands): ?string {
  $stmt = $pdo->prepare("SHOW COLUMNS FROM `$table`");
  $stmt->execute();
  $cols = array_map(fn($r) => strtolower((string)$r['Field']), $stmt->fetchAll(PDO::FETCH_ASSOC));
  foreach ($cands as $c) if (in_array(strtolower($c), $cols, true)) return $c;
  return null;
}

// --- POST: cambiar estado
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
     $pdo->prepare("UPDATE orders SET status=? WHERE id=?")->execute([$status, $id]); 
      flash_success('Estado actualizado a: '.$status);
    } catch (Throwable $e) {
      flash_error('No se pudo actualizar el estado.');
    }
  }
  header('Location: ' . BASE_URL . '/admin/pedido.php?id='.$id); exit;
}

// --- Cargar pedido
$stO = $pdo->prepare("SELECT * FROM orders WHERE id=?");
$stO->execute([$id]);
$o = $stO->fetch(PDO::FETCH_ASSOC);
if (!$o) { http_response_code(404); die('Pedido no encontrado'); }

// --- Detectar columnas reales en order_items
$nameCol  = pickColumn($pdo, 'order_items', ['product_name','name','product','titulo','title']) ?? 'product_name';
$priceCol = pickColumn($pdo, 'order_items', ['unit_price','price','precio','unitprice']) ?? 'unit_price';
$qtyCol   = pickColumn($pdo, 'order_items', ['quantity','qty','cantidad','cant','qty_ordered']) ?? 'quantity';

// --- Traer ítems con alias estables
$sqlI = "SELECT 
           product_id, 
           `$nameCol`  AS product_name, 
           `$priceCol` AS unit_price, 
           `$qtyCol`   AS quantity
         FROM order_items
         WHERE order_id = ?";
$stI = $pdo->prepare($sqlI);
$stI->execute([$id]);
$items = $stI->fetchAll(PDO::FETCH_ASSOC);

// --- Utilidades
function eur($n){ return '€ ' . number_format((float)$n, 2, ',', '.'); }

// Calcular total por si total_amount no existe o es NULL
$calcTotal = 0.0;
foreach ($items as $r) { $calcTotal += ((float)$r['unit_price']) * ((int)$r['quantity']); }
$shownTotal = isset($o['total_amount']) && $o['total_amount'] !== null ? (float)$o['total_amount'] : $calcTotal;

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
            <thead>
              <tr><th>Producto</th><th class="text-end">Precio</th><th class="text-end">Cant.</th><th class="text-end">Subtotal</th></tr>
            </thead>
            <tbody>
              <?php foreach ($items as $r): 
                $sub = ((float)$r['unit_price']) * ((int)$r['quantity']); ?>
                <tr>
                  <td><?= htmlspecialchars($r['product_name'] ?? '-') ?></td>
                  <td class="text-end"><?= eur($r['unit_price'] ?? 0) ?></td>
                  <td class="text-end"><?= (int)($r['quantity'] ?? 0) ?></td>
                  <td class="text-end"><?= eur($sub) ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
            <tfoot>
              <tr><th colspan="3" class="text-end">Total</th><th class="text-end"><?= eur($calcTotal) ?></th></tr>
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
        <div class="mb-2 text-muted small"><?= htmlspecialchars($o['created_at'] ?? '') ?></div>
        <ul class="list-unstyled mb-3">
          <li><strong><?= htmlspecialchars($o['customer_name'] ?? '-') ?></strong></li>
          <li><?= htmlspecialchars($o['email'] ?? '-') ?> · <?= htmlspecialchars($o['phone'] ?? '-') ?></li>
          <li><?= htmlspecialchars($o['address'] ?? '-') ?>, <?= htmlspecialchars($o['city'] ?? '-') ?> (<?= htmlspecialchars($o['zip'] ?? '-') ?>)</li>
          <?php if (!empty($o['doc'])): ?><li>Doc: <?= htmlspecialchars($o['doc']) ?></li><?php endif; ?>
          <?php if (!empty($o['notes'])): ?><li>Notas: <?= nl2br(htmlspecialchars($o['notes'])) ?></li><?php endif; ?>
        </ul>

        <form method="post" class="d-flex gap-2 align-items-end">
          <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf) ?>">
          <div>
            <label class="form-label">Estado</label>
            <select name="status" class="form-select">
              <?php foreach (['pendiente','pagado','enviado','cancelado'] as $st): ?>
                <option value="<?= $st ?>" <?= ($o['status'] ?? '')===$st?'selected':'' ?>><?= ucfirst($st) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <button class="btn btn-primary">Actualizar</button>
        </form>

        <hr>
        <div class="d-flex justify-content-between">
          <span class="text-muted">Total (mostrado)</span>
          <strong><?= eur($shownTotal) ?></strong>
        </div>
      </div>
    </div>
  </div>
</div>

<?php include __DIR__ . '/../templates/footer.php'; ?>
