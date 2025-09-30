<?php
// admin/index.php — dashboard básico
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/../inc/auth.php';

$CONTEXT = 'admin';
$PAGE_TITLE = 'Dashboard';

requireLogin();

$pdo = getConnection();

// métrica simple de productos
$row = $pdo->query("
  SELECT
    COUNT(*) AS total,
    SUM(CASE WHEN is_active=1 THEN 1 ELSE 0 END) AS activos,
    SUM(CASE WHEN is_active=0 THEN 1 ELSE 0 END) AS inactivos
  FROM products
")->fetch(PDO::FETCH_ASSOC) ?: ['total'=>0,'activos'=>0,'inactivos'=>0];

include __DIR__ . '/../templates/header.php';
?>
<div class="row g-3">
  <div class="col-md-4">
    <div class="card shadow-sm">
      <div class="card-body">
        <h5 class="card-title mb-1">Productos</h5>
        <div class="text-muted mb-2">Visión general</div>
        <ul class="list-unstyled mb-3">
          <li>Total: <strong><?= (int)$row['total'] ?></strong></li>
          <li>Activos: <strong><?= (int)$row['activos'] ?></strong></li>
          <li>Inactivos: <strong><?= (int)$row['inactivos'] ?></strong></li>
        </ul>
        <a class="btn btn-sm btn-primary" href="<?= BASE_URL ?>/admin/productos.php">Gestionar productos</a>
      </div>
    </div>
  </div>

  <div class="col-md-4">
    <div class="card shadow-sm">
      <div class="card-body">
        <h5 class="card-title mb-1">Pedidos</h5>
        <div class="text-muted mb-2">Atajos</div>
        <a class="btn btn-sm btn-outline-primary" href="<?= BASE_URL ?>/admin/pedidos.php">Ver pedidos</a>
      </div>
    </div>
  </div>
</div>
<?php include __DIR__ . '/../templates/footer.php'; ?>
