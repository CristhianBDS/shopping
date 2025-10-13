<?php
// admin/login.php — Acceso al panel administrador
require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../inc/flash.php';
require_once __DIR__ . '/../inc/auth.php';

$CONTEXT    = 'admin';
$PAGE_TITLE = 'Acceso admin';
$BASE       = defined('BASE_URL') ? BASE_URL : '/shopping';

// Si ya está logueado como admin, redirige al panel principal
if (!empty($_SESSION['user']) && ($_SESSION['user']['role'] ?? '') === 'admin') {
  header('Location: ' . $BASE . '/admin/index.php');
  exit;
}

// Si viene desde logout, muestra mensaje
if (isset($_GET['bye'])) {
  flash_info('Sesión cerrada correctamente.');
}

// CSRF simple para el formulario
if (empty($_SESSION['csrf_login'])) {
  $_SESSION['csrf_login'] = bin2hex(random_bytes(32));
}
$csrf = $_SESSION['csrf_login'];

$errors = [];
$email  = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $token = $_POST['csrf'] ?? '';
  if (!hash_equals($_SESSION['csrf_login'] ?? '', $token)) {
    $errors[] = 'CSRF inválido. Recarga la página.';
  }

  $email    = trim((string)($_POST['email'] ?? ''));
  $password = (string)($_POST['password'] ?? '');

  if ($email === '' || $password === '') {
    $errors[] = 'Completa email y contraseña.';
  }

  if (!$errors) {
    try {
      $pdo = getConnection();
      $st  = $pdo->prepare('
        SELECT id, name, email, role, is_active, password_hash
        FROM users
        WHERE email = ?
        LIMIT 1
      ');
      $st->execute([$email]);
      $row = $st->fetch(PDO::FETCH_ASSOC);

      if (
        $row &&
        (int)($row['is_active'] ?? 0) === 1 &&
        password_verify($password, (string)$row['password_hash'])
      ) {
        if (function_exists('session_regenerate_id')) session_regenerate_id(true);

        $_SESSION['user'] = [
          'id'    => (int)$row['id'],
          'name'  => (string)($row['name'] ?? 'Admin'),
          'email' => (string)$row['email'],
          'role'  => (string)($row['role'] ?? 'admin'),
        ];

        $dest = $_SESSION['redirect_after_login'] ?? ($BASE . '/admin/index.php');
        unset($_SESSION['csrf_login'], $_SESSION['redirect_after_login']);
        header('Location: ' . $dest);
        exit;
      } else {
        $errors[] = 'Credenciales inválidas o usuario inactivo.';
      }
    } catch (Throwable $e) {
      $errors[] = 'Error al validar el acceso.';
      if (defined('DEBUG') && DEBUG) {
        $errors[] = $e->getMessage();
      }
    }
  }
}

include __DIR__ . '/../templates/header.php';
?>
<div class="form-container">
  <div class="card shadow-sm">
    <div class="card-body">
      <h1 class="h4 mb-3 text-center">Acceso administrador</h1>

      <?php if ($errors): ?>
        <div class="alert alert-danger">
          <ul class="mb-0">
            <?php foreach ($errors as $e): ?>
              <li><?= htmlspecialchars($e) ?></li>
            <?php endforeach; ?>
          </ul>
        </div>
      <?php endif; ?>

      <form method="post" autocomplete="off" novalidate>
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
      </form>
    </div>
  </div>
</div>
<?php include __DIR__ . '/../templates/footer.php'; ?>
