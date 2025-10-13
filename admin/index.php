<?php
// admin/index.php — Dashboard administrador
require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../inc/flash.php';
require_once __DIR__ . '/../inc/auth.php';

$CONTEXT    = 'admin';
$PAGE_TITLE = 'Panel de control';
$BASE       = BASE_URL;

require_admin(); // solo admin

$pdo = getConnection();

/* ===========================
   Totales básicos
   =========================== */
$totals = [
  'orders'   => 0,
  'products' => 0,
  'users'    => 0,
  'sales'    => 0.0,
];

try {
  $totals['orders']   = (int)$pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn();
  $totals['products'] = (int)$pdo->query("SELECT COUNT(*) FROM products WHERE is_active=1")->fetchColumn();
  $totals['users']    = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE is_active=1")->fetchColumn();
} catch (Throwable $e) {
  if (DEBUG) flash_error($e->getMessage());
}

/* ===========================
   Helpers para columnas dinámicas
   =========================== */
function columnExists(PDO $pdo, string $table, string $column): bool {
  $st = $pdo->prepare("
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?
  ");
  $st->execute([$table, $column]);
  return (bool)$st->fetchColumn();
}
function firstExistingColumn(PDO $pdo, string $table, array $candidates): ?string {
  foreach ($candidates as $c) if (columnExists($pdo, $table, $c)) return $c;
  return null;
}

/* ===========================
   Ventas totales (robusto)
   =========================== */
$amountCol = firstExistingColumn($pdo, 'orders', ['total','grand_total','amount','total_amount','importe']);
$qtyCol    = firstExistingColumn($pdo, 'order_items', ['quantity','qty','cantidad']);
$priceCol  = firstExistingColumn($pdo, 'order_items', ['price','unit_price','precio']);

try {
  if ($amountCol) {
    $sqlSales = "SELECT COALESCE(SUM($amountCol),0) FROM orders WHERE status IN ('pagado','completado')";
    $totals['sales'] = (float)$pdo->query($sqlSales)->fetchColumn();
  } elseif ($qtyCol && $priceCol) {
    $st = $pdo->prepare("
      SELECT COALESCE(SUM(oi.$priceCol * oi.$qtyCol),0)
      FROM order_items oi
      JOIN orders o ON o.id = oi.order_id
      WHERE o.status IN ('pagado','completado')
    ");
    $st->execute();
    $totals['sales'] = (float)$st->fetchColumn();
  } else {
    $totals['sales'] = 0.0;
  }
} catch (Throwable $e) {
  if (DEBUG) flash_error($e->getMessage());
  $totals['sales'] = 0.0;
}

/* ===========================
   Ventas por mes (gráfico últimos 6 meses)
   =========================== */
$chartData = ['labels'=>[], 'values'=>[]];
try {
  if ($amountCol) {
    $stmt = $pdo->query("
      SELECT DATE_FORMAT(created_at,'%Y-%m') AS month, SUM($amountCol) AS total
      FROM orders
      WHERE status IN ('pagado','completado')
      GROUP BY DATE_FORMAT(created_at,'%Y-%m')
      ORDER BY month DESC
      LIMIT 6
    ");
  } elseif ($qtyCol && $priceCol) {
    $stmt = $pdo->query("
      SELECT DATE_FORMAT(o.created_at,'%Y-%m') AS month, SUM(oi.$priceCol * oi.$qtyCol) AS total
      FROM order_items oi
      JOIN orders o ON o.id = oi.order_id
      WHERE o.status IN ('pagado','completado')
      GROUP BY DATE_FORMAT(o.created_at,'%Y-%m')
      ORDER BY month DESC
      LIMIT 6
    ");
  } else {
    $stmt = false;
  }

  if ($stmt) {
    $rows = array_reverse($stmt->fetchAll(PDO::FETCH_ASSOC)); // antiguo → reciente
    foreach ($rows as $r) {
      $chartData['labels'][] = $r['month'];
      $chartData['values'][] = (float)$r['total'];
    }
  }
} catch (Throwable $e) {
  if (DEBUG) flash_error($e->getMessage());
}

include __DIR__ . '/../templates/header.php';
?>

<h1 class="h4 mb-4">📊 <span class="text-primary">Panel de control</span></h1>

<!-- Cards resumen -->
<div class="dashboard-cards">
  <div class="card dash-card">
    <div class="card-body text-center">
      <h2 class="h3 mb-1"><?= $totals['orders'] ?></h2>
      <p class="text-muted mb-0">Pedidos</p>
    </div>
  </div>
  <div class="card dash-card">
    <div class="card-body text-center">
      <h2 class="h3 mb-1"><?= $totals['products'] ?></h2>
      <p class="text-muted mb-0">Productos activos</p>
    </div>
  </div>
  <div class="card dash-card">
    <div class="card-body text-center">
      <h2 class="h3 mb-1"><?= $totals['users'] ?></h2>
      <p class="text-muted mb-0">Usuarios activos</p>
    </div>
  </div>
  <div class="card dash-card">
    <div class="card-body text-center">
      <h2 class="h3 mb-1">€ <?= number_format($totals['sales'], 2, ',', '.') ?></h2>
      <p class="text-muted mb-0">Ingresos totales</p>
    </div>
  </div>
</div>

<!-- Gráfico de ventas (línea) -->
<div class="card mt-4">
  <div class="card-body">
    <h5 class="card-title mb-3">Ventas por mes (últimos 6 meses)</h5>
    <div class="chart-container">
      <canvas id="chartVentas"></canvas>
    </div>
  </div>
</div>

<!-- Calendario mini (widget) -->
<div class="card mt-4">
  <div class="card-body">
    <div class="d-flex justify-content-between align-items-center mb-2">
      <h5 class="card-title mb-0">Calendario (vista rápida)</h5>
      <a class="btn btn-sm btn-outline-primary" href="<?= $BASE ?>/admin/calendario.php">Abrir calendario</a>
    </div>
    <div id="calendarMini"></div>
  </div>
</div>

<!-- Últimos pedidos -->
<div class="card mt-4 mb-4">
  <div class="card-body">
    <h5 class="card-title mb-3">Últimos pedidos</h5>
    <div class="table-responsive">
      <table class="table table-sm table-striped align-middle">
        <thead>
          <tr>
            <th>ID</th>
            <th>Cliente</th>
            <th>Fecha</th>
            <th>Estado</th>
            <th>Total</th>
          </tr>
        </thead>
        <tbody>
          <?php
          try {
            $orderTotalCol = $amountCol ?: null;
            $sql = "SELECT id, customer_name, status, created_at" . ($orderTotalCol ? ", $orderTotalCol AS total" : "") . "
                    FROM orders ORDER BY id DESC LIMIT 5";
            $q = $pdo->query($sql);
            foreach ($q as $r):
              $fecha = date('d/m/Y', strtotime($r['created_at']));
              $totalTxt = '-';
              if ($orderTotalCol && isset($r['total'])) {
                $totalTxt = '€ '.number_format((float)$r['total'], 2, ',', '.');
              }
          ?>
              <tr>
                <td><?= (int)$r['id'] ?></td>
                <td><?= htmlspecialchars($r['customer_name'] ?? '') ?></td>
                <td><?= $fecha ?></td>
                <td><?= ucfirst($r['status'] ?? '') ?></td>
                <td><?= $totalTxt ?></td>
              </tr>
          <?php
            endforeach;
          } catch (Throwable $e) { ?>
              <tr><td colspan="5" class="text-danger">Error al cargar pedidos</td></tr>
          <?php } ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', () => {
  const ctx = document.getElementById('chartVentas');
  if (!ctx) return;

  // Evitar inicialización doble si el script se ejecuta más de una vez
  if (window.__chartVentas) {
    window.__chartVentas.destroy();
  }

  const labels = <?= json_encode($chartData['labels'] ?? []) ?>;
  const values = <?= json_encode($chartData['values'] ?? []) ?>;

  window.__chartVentas = new Chart(ctx, {
    type: 'line',
    data: {
      labels,
      datasets: [{
        label: 'Ventas (€)',
        data: values,
        borderColor: '#0066FF',
        backgroundColor: 'rgba(0,102,255,0.15)',
        fill: true,
        tension: 0.35,
        pointRadius: 5,
        pointBackgroundColor: '#0066FF',
        pointBorderColor: '#fff',
        pointHoverRadius: 7,
        pointHoverBackgroundColor: '#0044cc',
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false, // usa la altura del contenedor .chart-container
      scales: { y: { beginAtZero: true } },
      plugins: { legend: { labels: { color: '#333', font: { weight: 'bold' } } } }
    }
  });
});
</script>

<!-- FullCalendar (mini) -->
<link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
  const mini = document.getElementById('calendarMini');
  if (!mini) return;
  const calMini = new FullCalendar.Calendar(mini, {
    initialView: 'dayGridMonth',
    height: 'auto',
    headerToolbar: { left:'prev,next', center:'title', right:'' },
    locale: 'es',
    events: { url: '<?= $BASE ?>/api/events.php' },
    eventClick: ({event}) => window.location.href = '<?= $BASE ?>/admin/evento_form.php?id=' + event.id,
  });
  calMini.render();
});
</script>

<?php include __DIR__ . '/../templates/footer.php'; ?>
