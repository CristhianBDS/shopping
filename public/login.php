<?php
// public/login.php — Login clientes
require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../inc/flash.php';

$CONTEXT    = 'public';
$PAGE_TITLE = 'Ingresar';
$BASE       = BASE_URL;

if (session_status() !== PHP_SESSION_ACTIVE) session_start();
if (!empty($_SESSION['user']) && (($_SESSION['user']['role'] ?? '') !== '')) {
  header('Location: '.$BASE.'/public/index.php'); exit;
}

if (empty($_SESSION['csrf_login_public'])) {
  $_SESSION['csrf_login_public'] = bin2hex(random_bytes(32));
}
$csrf = $_SESSION['csrf_login_public'];

$email = '';
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $token = $_POST['csrf'] ?? '';
  if (!hash_equals($_SESSION['csrf_login_public'] ?? '', $token)) {
    $errors[] = 'CSRF inválido. Recarga la página.';
  }

  $email = trim((string)($_POST['email'] ?? ''));
  $pwd   = (string)($_POST['password'] ?? '');
  if ($email === '' || $pwd === '') {
    $errors[] = 'Completa email y contraseña.';
  }

  if (!$errors) {
    try {
      $pdo = getConnection();
      $st  = $pdo->prepare('SELECT id,name,email,role,is_active,password_hash FROM users WHERE email=? LIMIT 1');
      $st->execute([$email]);
      $u = $st->fetch(PDO::FETCH_ASSOC);

      if ($u && (int)$u['is_active'] === 1 && password_verify($pwd, (string)$u['password_hash'])) {
        if (function_exists('session_regenerate_id')) session_regenerate_id(true);
        $_SESSION['user'] = [
          'id'    => (int)$u['id'],
          'name'  => (string)$u['name'],
          'email' => (string)$u['email'],
          'role'  => (string)$u['role'],
        ];
        unset($_SESSION['csrf_login_public']);
        flash_success('¡Bienvenido!');
        header('Location: '.$BASE.'/public/index.php'); exit;
      } else {
        $errors[] = 'Credenciales inválidas o usuario inactivo.';
      }
    } catch (Throwable $e) {
      $errors[] = 'Error al validar el acceso.';
      if (defined('DEBUG') && DEBUG) { $errors[] = $e->getMessage(); }
    }
  }
}

include __DIR__ . '/../templates/header.php';
?>
<h1 class="h4 mb-3">Ingresar</h1>
<?php if ($errors): ?>
  <div class="alert alert-danger"><ul class="mb-0"><?php foreach ($errors as $e): ?><li><?= htmlspecialchars($e) ?></li><?php endforeach; ?></ul></div>
<?php endif; ?>

<form method="post" class="form-auth" autocomplete="off" novalidate>
  <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf) ?>">
  <div class="mb-3">
    <label class="form-label">Email</label>
    <input type="email" name="email" class="form-control" required value="<?= htmlspecialchars($email) ?>">
  </div>
  <div class="mb-3">
    <label class="form-label">Contraseña</label>
    <input type="password" name="password" class="form-control" required>
  </div>
  <button class="btn btn-primary w-100">Entrar</button>
  <a class="btn btn-outline-secondary mt-2" href="<?= $BASE ?>/public/registro.php">Crear cuenta</a>
</form>
<?php include __DIR__ . '/../templates/footer.php'; ?>
