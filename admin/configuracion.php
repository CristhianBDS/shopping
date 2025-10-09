<?php
// admin/configuracion.php — Config básica de tienda
require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../inc/auth.php';
require_once __DIR__ . '/../inc/flash.php';
require_once __DIR__ . '/../inc/settings.php';

$CONTEXT    = 'admin';
$PAGE_TITLE = 'Configuración';

require_admin();

$BASE = defined('BASE_URL') ? BASE_URL : '/shopping';

// CSRF
if (empty($_SESSION['csrf_settings'])) {
  $_SESSION['csrf_settings'] = bin2hex(random_bytes(32));
}
$csrf = $_SESSION['csrf_settings'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $token = $_POST['csrf'] ?? '';
  if (!hash_equals($_SESSION['csrf_settings'] ?? '', $token)) {
    flash_error('CSRF inválido. Recarga la página.');
    header('Location: ' . $BASE . '/admin/configuracion.php'); exit;
  }

  $shop  = trim((string)($_POST['shop_name'] ?? ''));
  $wa    = trim((string)($_POST['whatsapp_number'] ?? ''));
  $email = trim((string)($_POST['contact_email'] ?? ''));

  // Normalizaciones simples
  $wa    = preg_replace('/\s+/', '', $wa);
  $email = trim($email);

  setting_set('shop_name', $shop);
  setting_set('whatsapp_number', $wa);
  setting_set('contact_email', $email);

  unset($_SESSION['csrf_settings']);
  flash_success('Configuración guardada.');
  header('Location: ' . $BASE . '/admin/configuracion.php'); exit;
}

$shop  = setting_get('shop_name', 'Mi Tienda');
$wa    = setting_get('whatsapp_number', '');
$email = setting_get('contact_email', '');

include __DIR__ . '/../templates/header.php';
?>
<main class="container py-4">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h4 mb-0">Configuración</h1>
    <a class="btn btn-outline-secondary" href="<?= $BASE ?>/admin/index.php">Volver</a>
  </div>

  <form method="post" class="card shadow-sm">
    <div class="card-body">
      <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf) ?>">

      <div class="mb-3">
        <label class="form-label">Nombre de la tienda</label>
        <input type="text" name="shop_name" class="form-control" maxlength="120" value="<?= htmlspecialchars($shop) ?>">
      </div>

      <div class="mb-3">
        <label class="form-label">WhatsApp (E.164)</label>
        <input type="text" name="whatsapp_number" class="form-control" placeholder="+34123456789" value="<?= htmlspecialchars($wa) ?>">
        <div class="form-text">Formato recomendado internacional, ej: +34123456789</div>
      </div>

      <div class="mb-3">
        <label class="form-label">Email de contacto</label>
        <input type="email" name="contact_email" class="form-control" value="<?= htmlspecialchars($email) ?>">
      </div>

      <button class="btn btn-primary">Guardar</button>
    </div>
  </form>
</main>
<?php include __DIR__ . '/../templates/footer.php'; ?>
