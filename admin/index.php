<?php
// admin/index.php — Dashboard principal de la tienda

declare(strict_types=1);

require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../inc/auth.php';
require_once __DIR__ . '/../inc/flash.php';
require_once __DIR__ . '/../inc/settings.php';

$CONTEXT    = 'admin';
$PAGE_TITLE = 'Dashboard';
$BREADCRUMB = 'Dashboard';

requireAdmin();

$BASE = defined('BASE_URL') ? BASE_URL : '/shopping';
$pdo  = getConnection();

// Helpers simples
function eur(float $n): string {
  return number_format($n, 2, ',', '.') . ' €';
}
function fdate(?string $s): string {
  if (!$s) return '-';
  try {
    return (new DateTime($s))->format('d/m/Y H:i');
  } catch (Throwable) {
    return (string)$s;
  }
}

// =======================
//  STATS BÁSICAS
// =======================

$stats = [
  'products_total' => 0,
  'users_total'    => 0,
  'users_admins'   => 0,
  'users_clients'  => 0,
  'orders_total'   => 0,
  'orders_pending' => 0,
  'orders_today'   => 0,
  'revenue_total'  => 0.0,
];

// 1) Productos
try {
  $stats['products_total'] = (int)$pdo->query('SELECT COUNT(*) FROM products')->fetchColumn();
} catch (Throwable $e) {
  // si la tabla se llama distinto no rompemos el dashboard
}

// 2) Usuarios
try {
  $stats['users_total']  = (int)$pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
  $stats['users_admins'] = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role = 'admin'")->fetchColumn();
  $stats['users_clients'] = $stats['users_total'] - $stats['users_admins'];
} catch (Throwable $e) {
  // ignoramos si aún no existe tabla users
}

// 3) Pedidos + ingresos
try {
  $stats['orders_total']   = (int)$pdo->query('SELECT COUNT(*) FROM orders')->fetchColumn();
  $stats['orders_pending'] = (int)$pdo->query("SELECT COUNT(*) FROM orders WHERE status = 'pendiente'")->fetchColumn();

  $todayCount = $pdo->query("SELECT COUNT(*) FROM orders WHERE DATE(created_at) = CURDATE()")->fetchColumn();
  $stats['orders_today'] = (int)$todayCount;

  $revenue = $pdo->query('SELECT SUM(total_amount) FROM orders')->fetchColumn();
  $stats['revenue_total'] = $revenue !== null ? (float)$revenue : 0.0;
} catch (Throwable $e) {
  // si falla algo, dejamos valores en 0
}

// =======================
//  ÚLTIMOS PEDIDOS
// =======================

$latestOrders = [];
try {
  $st = $pdo->prepare("
    SELECT id, customer_name, total_amount, status, created_at
    FROM orders
    ORDER BY created_at DESC
    LIMIT 5
  ");
  $st->execute();
  $latestOrders = $st->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
  $latestOrders = [];
}

include __DIR__ . '/../templates/header.php';
?>

<main class="container py-4">
  <h1 class="h4 mb-3">Dashboard</h1>

  <!-- Tarjetas de resumen -->
  <section class="dashboard-cards mb-4">
    <!-- Ventas totales -->
    <article class="card dash-card">
      <div class="card-body">
        <h2 class="h6 text-muted mb-1">Ingresos totales</h2>
        <p class="h4 mb-1"><?= eur($stats['revenue_total']) ?></p>
        <p class="small text-muted mb-0">
          Pedidos: <?= (int)$stats['orders_total'] ?> · Hoy: <?= (int)$stats['orders_today'] ?>
        </p>
      </div>
    </article>

    <!-- Pedidos -->
    <article class="card dash-card">
      <div class="card-body">
        <h2 class="h6 text-muted mb-1">Pedidos</h2>
        <p class="h4 mb-1"><?= (int)$stats['orders_total'] ?></p>
        <p class="small mb-0">
          <span class="badge bg-secondary">Pendientes: <?= (int)$stats['orders_pending'] ?></span>
        </p>
      </div>
    </article>

    <!-- Usuarios / clientes -->
    <article class="card dash-card">
      <div class="card-body">
        <h2 class="h6 text-muted mb-1">Usuarios</h2>
        <p class="h4 mb-1"><?= (int)$stats['users_total'] ?></p>
        <p class="small text-muted mb-0">
          Admin: <?= (int)$stats['users_admins'] ?> · Clientes: <?= (int)$stats['users_clients'] ?>
        </p>
      </div>
    </article>

    <!-- Productos -->
    <article class="card dash-card">
      <div class="card-body">
        <h2 class="h6 text-muted mb-1">Catálogo</h2>
        <p class="h4 mb-1"><?= (int)$stats['products_total'] ?></p>
        <p class="small text-muted mb-0">Productos publicados en la tienda</p>
      </div>
    </article>
  </section>

  <!-- Últimos pedidos -->
  <section class="card shadow-sm mb-4">
    <div class="card-body">
      <div class="d-flex justify-content-between align-items-center mb-2">
        <h2 class="h6 mb-0">Últimos pedidos</h2>
        <a class="btn btn-sm btn-outline-primary" href="<?= $BASE ?>/admin/pedidos.php">Ver todos</a>
      </div>

      <?php if (!$latestOrders): ?>
        <p class="text-muted small mb-0">Aún no hay pedidos registrados.</p>
      <?php else: ?>
        <div class="table-responsive">
          <table class="table table-sm align-middle mb-0">
            <thead class="table-light">
              <tr>
                <th>ID</th>
                <th>Cliente</th>
                <th class="text-end">Total</th>
                <th>Fecha</th>
                <th>Estado</th>
                <th class="text-end">Acciones</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($latestOrders as $o): ?>
                <tr>
                  <td>#<?= (int)$o['id'] ?></td>
                  <td><?= htmlspecialchars($o['customer_name'] ?? '-') ?></td>
                  <td class="text-end"><?= eur((float)($o['total_amount'] ?? 0)) ?></td>
                  <td><?= fdate($o['created_at'] ?? '') ?></td>
                  <td>
                    <span class="badge text-bg-secondary">
                      <?= htmlspecialchars(ucfirst((string)$o['status'])) ?>
                    </span>
                  </td>
                  <td class="text-end">
                    <a href="<?= $BASE ?>/admin/pedido.php?id=<?= (int)$o['id'] ?>" class="btn btn-sm btn-outline-secondary">
                      Ver
                    </a>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </div>
  </section>

  <!-- CTA rápida -->
  <section class="card shadow-sm">
    <div class="card-body d-flex flex-wrap gap-2 justify-content-between align-items-center">
      <div>
        <h2 class="h6 mb-1">Acciones rápidas</h2>
        <p class="small text-muted mb-0">Gestiona productos, pedidos y la configuración de la tienda.</p>
      </div>
      <div class="d-flex flex-wrap gap-2">
        <a class="btn btn-primary btn-sm" href="<?= $BASE ?>/admin/productos.php">Gestionar productos</a>
        <a class="btn btn-outline-primary btn-sm" href="<?= $BASE ?>/admin/pedidos.php">Revisar pedidos</a>
        <a class="btn btn-outline-secondary btn-sm" href="<?= $BASE ?>/admin/configuracion.php">Configuración</a>
      </div>
    </div>
  </section>
</main>

<?php include __DIR__ . '/../templates/footer.php'; ?>
