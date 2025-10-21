<?php
// public/pedidos.php — Historial de pedidos del cliente (match por email)
require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../inc/flash.php';
require_once __DIR__ . '/../inc/auth.php';

$CONTEXT    = 'public';
$PAGE_TITLE = 'Mis pedidos';
$BASE       = defined('BASE_URL') ? BASE_URL : '/shopping';

requireLogin();
$user = currentUser();
$email = (string)$user['email'];

$pdo = getConnection();

// IMPORTANTE: tu esquema usa email (no user_id)
$st = $pdo->prepare('
  SELECT id, status, total_amount, created_at
  FROM orders
  WHERE email = ?
  ORDER BY created_at DESC
');
$st->execute([$email]);
$orders = $st->fetchAll(PDO::FETCH_ASSOC);

include __DIR__ . '/../templates/header.php';
?>
<section class="container py-5">
  <h1 class="mb-4">Mis pedidos</h1>

  <?php if (!$orders): ?>
    <div class="alert alert-info">Aún no tienes pedidos.</div>
    <a class="btn btn-primary" href="<?= $BASE ?>/public/catalogo.php">Ir al catálogo</a>
  <?php else: ?>
    <div class="table-responsive">
      <table class="table table-sm align-middle">
        <thead>
          <tr>
            <th>#</th>
            <th>Fecha</th>
            <th>Estado</th>
            <th class="text-end">Total</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
        <?php foreach ($orders as $o): ?>
          <tr>
            <td><?= (int)$o['id'] ?></td>
            <td><?= htmlspecialchars($o['created_at']) ?></td>
            <td><span class="badge bg-secondary"><?= htmlspecialchars($o['status']) ?></span></td>
            <td class="text-end">€ <?= number_format((float)$o['total_amount'], 2, ',', '.') ?></td>
            <td class="text-end">
              <a class="btn btn-outline-secondary btn-sm" href="<?= $BASE ?>/public/pedido_detalle.php?id=<?= (int)$o['id'] ?>">Ver detalle</a>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</section>
<?php include __DIR__ . '/../templates/footer.php'; ?>
