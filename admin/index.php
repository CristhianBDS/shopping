<?php
// admin/index.php — Dashboard básico (solo admin)
require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../inc/auth.php';
require_once __DIR__ . '/../inc/flash.php';

$CONTEXT    = 'admin';
$PAGE_TITLE = 'Dashboard';

// Solo admin (si quieres permitir cualquier usuario logueado, usa require_login())
require_admin();

$pdo = getConnection();

// Métrica simple de productos
$row = $pdo->query("
  SELECT
    COUNT(*) AS total,
    SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) AS activos,
    SUM(CASE WHEN is_active = 0 THEN 1 ELSE 0 END) AS inactivos
  FROM products
")->fetch(PDO::FETCH_ASSOC) ?: ['total'=>0,'activos'=>0,'inactivos'=>0];

include __DIR__ . '/../templates/header.php';

// Si NO llamas a flash_render() dentro de header.php, descomenta la siguiente línea:
// flash_render();

$BASE = defined('BASE_URL') ? BASE_URL : '/shopping';
?>
<main class="container py-4">
  <div class="row g-3">
    <div class="col-md-4">
      <div class="card shadow-sm">
        <div class="card-body">
          <h5 class="card-title mb-1">Productos</h5>
          <div class="text-muted mb-2">Visión general</div>
          <ul class="list-unstyled mb-3">
            <li>Total: <strong><?= (int)($row['total'] ?? 0) ?></strong></li>
            <li>Activos: <strong><?= (int)($row['activos'] ?? 0) ?></strong></li>
            <li>Inactivos: <strong><?= (int)($row['inactivos'] ?? 0) ?></strong></li>
          </ul>
          <a class="btn btn-sm btn-primary" href="<?= $BASE ?>/admin/productos.php">Gestionar productos</a>
        </div>
      </div>
    </div>

    <div class="col-md-4">
      <div class="card shadow-sm">
        <div class="card-body">
          <h5 class="card-title mb-1">Pedidos</h5>
          <div class="text-muted mb-2">Atajos</div>
          <a class="btn btn-sm btn-outline-primary" href="<?= $BASE ?>/admin/pedidos.php">Ver pedidos</a>
        </div>
      </div>
    </div>
  </div>
</main>
<?php include __DIR__ . '/../templates/footer.php'; ?>
