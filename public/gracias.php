<?php
// public/gracias.php — Pantalla de confirmación de pedido
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/../inc/settings.php';
require_once __DIR__ . '/../templates/header.php';

$BASE = defined('BASE_URL') ? BASE_URL : '/shopping';
$orderId = isset($_GET['order']) ? (int)$_GET['order'] : 0;

// WhatsApp dinámico (desde settings)
$wa = preg_replace('/\D+/', '', setting_get('whatsapp_number', ''));
$waLink = $wa ? "https://wa.me/{$wa}" : '';
?>
<main class="container py-5">
  <div class="card shadow-sm mx-auto" style="max-width:720px;">
    <div class="card-body p-4 text-center">
      <h1 class="h3 mb-3">¡Gracias por tu pedido! 🎉</h1>

      <?php if ($orderId): ?>
        <p class="mb-1">Tu pedido <strong>#<?= htmlspecialchars((string)$orderId) ?></strong> fue registrado correctamente.</p>
      <?php else: ?>
        <p class="mb-1">Tu pedido fue registrado correctamente.</p>
      <?php endif; ?>

      <p class="text-muted">En las próximas horas nos pondremos en contacto contigo para <strong>confirmar la entrega a domicilio</strong>.</p>

      <?php if ($waLink): ?>
        <p class="mb-3">
          Si lo prefieres, puedes escribirnos por
          <a href="<?= htmlspecialchars($waLink) ?>" target="_blank" rel="noopener noreferrer">WhatsApp</a>.
        </p>
      <?php endif; ?>

      <div class="d-flex justify-content-center gap-2 mt-3">
        <a class="btn btn-primary" href="<?= $BASE ?>/public/index.php">Volver al inicio</a>
        <a class="btn btn-outline-secondary" href="<?= $BASE ?>/public/catalogo.php">Ver más productos</a>
      </div>
    </div>
  </div>
</main>

<?php require_once __DIR__ . '/../templates/footer.php'; ?>
