<?php
// public/cuenta.php — Panel de cuenta para usuarios logueados

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../inc/auth.php';

$CONTEXT    = 'public';
$PAGE_TITLE = 'Mi cuenta';
$BASE       = defined('BASE_URL') ? BASE_URL : '/shopping';

// Solo usuarios con sesión
requireLogin();

$user   = currentUser();
$userId = $user['id'] ?? null;

// =========================
// Métricas del socio
// =========================
$totalAmount = 0.0;
$countOrders = 0;
$lastOrder   = null;
$nivel       = 'Nuevo';

if ($userId) {
    $pdo = getConnection();
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Total gastado (excluyendo cancelados)
    $stmtTotal = $pdo->prepare("
        SELECT COALESCE(SUM(total_amount), 0) AS total
        FROM orders
        WHERE user_id = :uid
          AND status <> 'cancelado'
    ");
    $stmtTotal->execute([':uid' => $userId]);
    $totalAmount = (float)$stmtTotal->fetchColumn();

    // Número de pedidos
    $stmtCount = $pdo->prepare("
        SELECT COUNT(*) AS c
        FROM orders
        WHERE user_id = :uid
          AND status <> 'cancelado'
    ");
    $stmtCount->execute([':uid' => $userId]);
    $countOrders = (int)$stmtCount->fetchColumn();

    // Último pedido
    $stmtLast = $pdo->prepare("
        SELECT id, total_amount, status, created_at
        FROM orders
        WHERE user_id = :uid
        ORDER BY created_at DESC
        LIMIT 1
    ");
    $stmtLast->execute([':uid' => $userId]);
    $lastOrder = $stmtLast->fetch(PDO::FETCH_ASSOC) ?: null;

    // Nivel según lo gastado
    if ($totalAmount >= 300) {
        $nivel = 'Oro';
    } elseif ($totalAmount >= 150) {
        $nivel = 'Plata';
    } elseif ($totalAmount > 0) {
        $nivel = 'Bronce';
    }
}

include __DIR__ . '/../templates/header.php';
?>

<main class="page page-cuenta py-4 py-md-5">
  <section class="container">

    <header class="mb-4 mb-md-5">
      <h1 class="mb-0">Mi cuenta</h1>
    </header>

    <!-- Tabs -->
    <ul class="nav nav-tabs mb-4" role="tablist">
      <li class="nav-item" role="presentation">
        <button
          class="nav-link active"
          id="tab-perfil-tab"
          data-bs-toggle="tab"
          data-bs-target="#tab-perfil"
          type="button"
          role="tab"
          aria-controls="tab-perfil"
          aria-selected="true">
          Perfil
        </button>
      </li>
      <li class="nav-item" role="presentation">
        <button
          class="nav-link"
          id="tab-password-tab"
          data-bs-toggle="tab"
          data-bs-target="#tab-password"
          type="button"
          role="tab"
          aria-controls="tab-password"
          aria-selected="false">
          Contraseña
        </button>
      </li>
      <li class="nav-item" role="presentation">
        <button
          class="nav-link"
          id="tab-resumen-tab"
          data-bs-toggle="tab"
          data-bs-target="#tab-resumen"
          type="button"
          role="tab"
          aria-controls="tab-resumen"
          aria-selected="false">
          Resumen
        </button>
      </li>
    </ul>

    <div class="tab-content">

      <!-- PERFIL -->
      <div
        class="tab-pane fade show active"
        id="tab-perfil"
        role="tabpanel"
        aria-labelledby="tab-perfil-tab">

        <div class="card border-0 shadow-sm">
          <div class="card-body">
            <div class="mb-3">
              <label class="form-label">Nombre</label>
              <input
                type="text"
                class="form-control"
                value="<?= htmlspecialchars($user['name'] ?? '') ?>"
                disabled>
            </div>

            <div class="mb-3">
              <label class="form-label">Email</label>
              <input
                type="email"
                class="form-control"
                value="<?= htmlspecialchars($user['email'] ?? '') ?>"
                disabled>
            </div>

            <p class="text-muted small mb-0">
              Estos datos se configuraron al crear tu cuenta.
              Si necesitas modificarlos, puedes contactarnos por nuestros canales de ayuda.
            </p>
          </div>
        </div>
      </div>

      <!-- CONTRASEÑA -->
      <div
        class="tab-pane fade"
        id="tab-password"
        role="tabpanel"
        aria-labelledby="tab-password-tab">

        <div class="card border-0 shadow-sm">
          <div class="card-body">
            <p class="text-muted small mb-3">
              Aquí podrás cambiar tu contraseña en futuras versiones del sistema.
              De momento, si necesitas actualizarla, escríbenos directamente y te ayudamos.
            </p>

            <div class="row g-3">
              <div class="col-md-6">
                <label class="form-label">Contraseña actual</label>
                <input type="password" class="form-control" disabled placeholder="Función en desarrollo">
              </div>
              <div class="col-md-6">
                <label class="form-label">Nueva contraseña</label>
                <input type="password" class="form-control" disabled placeholder="Función en desarrollo">
              </div>
            </div>

            <button type="button" class="btn btn-primary mt-3" disabled>
              Guardar cambios
            </button>
          </div>
        </div>
      </div>

      <!-- RESUMEN -->
      <div
        class="tab-pane fade"
        id="tab-resumen"
        role="tabpanel"
        aria-labelledby="tab-resumen-tab">

        <!-- Tarjetas con métricas -->
        <div class="row g-3 mb-4">
          <div class="col-md-4">
            <div class="card h-100 shadow-sm border-0">
              <div class="card-body">
                <p class="text-muted mb-1 small">Nivel actual</p>
                <p class="h5 mb-0">
                  <?= htmlspecialchars($nivel) ?>
                </p>
              </div>
            </div>
          </div>

          <div class="col-md-4">
            <div class="card h-100 shadow-sm border-0">
              <div class="card-body">
                <p class="text-muted mb-1 small">Total gastado</p>
                <p class="h5 mb-0">
                  <?= number_format($totalAmount, 2, ',', '.') ?> €
                </p>
              </div>
            </div>
          </div>

          <div class="col-md-4">
            <div class="card h-100 shadow-sm border-0">
              <div class="card-body">
                <p class="text-muted mb-1 small">Pedidos realizados</p>
                <p class="h5 mb-0">
                  <?= (int)$countOrders ?>
                </p>
              </div>
            </div>
          </div>
        </div>

        <!-- Último pedido -->
        <?php if ($lastOrder): ?>
          <div class="card shadow-sm border-0 mb-3">
            <div class="card-body small">
              <p class="text-muted mb-2">Último pedido</p>
              <p class="mb-1">
                <strong>#<?= (int)$lastOrder['id'] ?></strong>
                · <?= number_format((float)$lastOrder['total_amount'], 2, ',', '.') ?> €
              </p>
              <p class="mb-0 text-muted">
                Estado: <?= htmlspecialchars($lastOrder['status']) ?> ·
                Fecha: <?= htmlspecialchars($lastOrder['created_at']) ?>
              </p>
            </div>
          </div>
        <?php else: ?>
          <p class="text-muted">
            Aún no has realizado ningún pedido como socio.
            Tu primera compra ya contará para tus beneficios.
          </p>
        <?php endif; ?>

        <!-- Enlaces útiles -->
        <div class="mt-4">
          <a href="<?= htmlspecialchars($BASE) ?>/public/pedidos.php" class="btn btn-outline-primary btn-sm">
            Ver historial de pedidos
          </a>
          <a href="<?= htmlspecialchars($BASE) ?>/public/beneficios.php" class="btn btn-link btn-sm">
            Ver todos los beneficios de socio
          </a>
        </div>

      </div>
    </div>

  </section>
</main>

<?php include __DIR__ . '/../templates/footer.php'; ?>
