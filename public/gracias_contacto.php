<?php
// public/gracias_contacto.php — Confirmación de contacto
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/../inc/auth.php';

$CONTEXT    = 'public';
$PAGE_TITLE = 'Gracias por contactarnos';
$BASE = defined('BASE_URL') ? BASE_URL : '/shopping';

// Sanitiza email si viene en el POST
$email = isset($_POST['email']) ? trim((string)$_POST['email']) : '';
if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
  $email = ''; // no mostrar formato inválido
}

include __DIR__ . '/../templates/header.php';
?>

<section class="thanks-section container py-5">
  <div class="thanks-card card shadow-sm">
    <div class="card-body text-center p-5">
      <h1 class="h2 mb-3">¡Gracias por tu mensaje! ✉️</h1>
      <p class="text-muted mb-2">
        Hemos recibido tu solicitud correctamente.
      </p>
      <p class="text-muted">
        Nuestros operarios se pondrán en contacto contigo <strong>lo antes posible</strong> para resolver tus dudas.
      </p>

      <?php if ($email !== ''): ?>
        <p class="small text-muted mt-2">
          Correo recibido: <strong><?= htmlspecialchars($email, ENT_QUOTES, 'UTF-8') ?></strong>
        </p>
      <?php endif; ?>

      <div class="mt-4 d-flex gap-2 justify-content-center">
        <a href="<?= $BASE ?>/public/index.php" class="btn btn-primary">Volver al inicio</a>
        <a href="<?= $BASE ?>/public/catalogo.php" class="btn btn-outline-dark">Ver más productos</a>
      </div>
    </div>
  </div>
</section>

<?php include __DIR__ . '/../templates/footer.php'; ?>
