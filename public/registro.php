<?php
// public/registro.php — Crear cuenta (member)
require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../inc/flash.php';
require_once __DIR__ . '/../inc/auth.php';

$CONTEXT    = 'public';
$PAGE_TITLE = 'Hazte socio';
$BASE       = defined('BASE_URL') ? BASE_URL : '/shopping';

if (session_status() !== PHP_SESSION_ACTIVE) session_start();

// Token CSRF único para este formulario usando el helper global
$csrf = auth_csrf();

$name = trim((string)($_POST['name'] ?? ''));
$email = trim((string)($_POST['email'] ?? ''));
$pwd1 = (string)($_POST['password'] ?? '');
$pwd2 = (string)($_POST['password2'] ?? '');
$terms = isset($_POST['terms']);
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  // CSRF
  $token = $_POST['csrf'] ?? '';
  if (!verify_csrf($token)) {
    $errors[] = 'CSRF inválido. Recarga la página.';
  }

  // Validaciones básicas
  if ($name === '') $errors[] = 'Ingresa tu nombre completo.';
  if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Email inválido.';
  if (strlen($pwd1) < 6) $errors[] = 'La contraseña debe tener al menos 6 caracteres.';
  if ($pwd1 !== $pwd2) $errors[] = 'Las contraseñas no coinciden.';
  if (!$terms) $errors[] = 'Debes aceptar los términos y condiciones.';

  if (!$errors) {
    try {
      $pdo = getConnection();

      // ¿Email ya existe?
      $st = $pdo->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
      $st->execute([$email]);
      if ($st->fetch()) {
        $errors[] = 'Ya existe una cuenta con ese email.';
      } else {
        // Crear usuario como MEMBER (no admin)
        $hash = password_hash($pwd1, PASSWORD_DEFAULT);
        $st = $pdo->prepare('
          INSERT INTO users (name, email, password_hash, role, is_active, created_at, updated_at)
          VALUES (?, ?, ?, "member", 1, NOW(), NOW())
        ');
        $st->execute([$name, $email, $hash]);

        // Autologin y redirección
        $uid = (int)$pdo->lastInsertId();
        if (function_exists('session_regenerate_id')) session_regenerate_id(true);
        $_SESSION['user'] = [
          'id'    => $uid,
          'name'  => $name,
          'email' => $email,
          'role'  => 'member',
        ];
        flash_success('¡Cuenta creada! Bienvenido.');
        header('Location: '.$BASE.'/public/beneficios.php'); exit;
      }
    } catch (Throwable $e) {
      $errors[] = 'No se pudo crear la cuenta.';
      if (defined('DEBUG') && DEBUG) $errors[] = $e->getMessage();
    }
  }
}

include __DIR__ . '/../templates/header.php';
?>
<section class="container py-5">
  <h1 class="text-center mb-3">Hazte socio</h1>
  <?php if ($errors): ?>
    <div class="alert alert-danger"><ul class="mb-0"><?php foreach ($errors as $e): ?><li><?= htmlspecialchars($e) ?></li><?php endforeach; ?></ul></div>
  <?php endif; ?>

  <form method="post" class="mx-auto" style="max-width: 560px" autocomplete="off" novalidate>
    <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf) ?>">

    <div class="mb-3">
      <label class="form-label">Nombre completo</label>
      <input type="text" name="name" class="form-control" required value="<?= htmlspecialchars($name) ?>">
    </div>

    <div class="mb-3">
      <label class="form-label">Email</label>
      <input type="email" name="email" class="form-control" required value="<?= htmlspecialchars($email) ?>">
    </div>

    <div class="row g-3">
      <div class="col-md-6">
        <label class="form-label">Contraseña</label>
        <input type="password" name="password" class="form-control" required>
      </div>
      <div class="col-md-6">
        <label class="form-label">Repetir contraseña</label>
        <input type="password" name="password2" class="form-control" required>
      </div>
    </div>

    <div class="form-check my-3">
      <input class="form-check-input" type="checkbox" name="terms" id="terms" <?= $terms?'checked':'' ?>>
      <label class="form-check-label" for="terms">Acepto los <a href="#">términos y condiciones</a>.</label>
    </div>

    <div class="d-flex gap-2">
      <button class="btn btn-primary">Crear cuenta</button>
      <a class="btn btn-outline-secondary" href="<?= $BASE ?>/public/login.php">Ya tengo cuenta</a>
    </div>
  </form>
</section>
<?php include __DIR__ . '/../templates/footer.php'; ?>
