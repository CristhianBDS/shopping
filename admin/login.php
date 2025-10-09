<?php
// admin/login.php — Acceso al panel
require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/db.php';   // <- necesario para getConnection()
require_once __DIR__ . '/../inc/flash.php';
require_once __DIR__ . '/../inc/auth.php';

$CONTEXT    = 'admin';
$PAGE_TITLE = 'Acceso admin';

$BASE = defined('BASE_URL') ? BASE_URL : '/shopping';

// Si ya está logueado, redirige al dashboard
if (!empty($_SESSION['user']) && ($_SESSION['user']['role'] ?? '') === 'admin') {
  header('Location: ' . $BASE . '/admin/index.php');
  exit;
}

// Mensaje si viene de logout
if (isset($_GET['bye'])) {
  flash_info('Sesión cerrada correctamente.');
}

// CSRF simple para el form
if (empty($_SESSION['csrf_login'])) {
  $_SESSION['csrf_login'] = bin2hex(random_bytes(32));
}
$csrf = $_SESSION['csrf_login'];

$errors = [];
$email = '';

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
      $st  = $pdo->prepare('SELECT id, name, email, password, role FROM users WHERE email = ? LIMIT 1');
      $st->execute([$email]);
      $row = $st->fetch(PDO::FETCH_ASSOC);

      // Asumimos password hasheado con password_hash(); si no, adapta aquí.
      if ($row && password_verify($password, (string)$row['password'])) {
        // Sesión fuerte
        if (function_exists('session_regenerate_id')) session_regenerate_id(true);

        $_SESSION['user'] = [
          'id'    => (int)$row['id'],
          'name'  => (string)($row['name'] ?? 'Admin'),
          'email' => (string)$row['email'],
          'role'  => (string)($row['role'] ?? 'admin'),
        ];

        // Redirección post-login
        $dest = $_SESSION['redirect_after_login'] ?? ($BASE . '/admin/index.php');
        unset($_SESSION['csrf_login'], $_SESSION['redirect_after_login']);
        header('Location: ' . $dest);
        exit;
      } else {
        $errors[] = 'Credenciales inválidas.';
      }
    } catch (Throwable $e) {
      $errors[] = 'Error al validar el acceso.';
      if (defined('DEBUG') && DEBUG) { $errors[] = $e->getMessage(); }
    }
  }
}

include __DIR__ . '/../templates/header.php';
?>
<main class="container py-5" style="max-width:560px;">
  <div class="card shadow-sm">
    <div class="card-body">
      <h1 class="h4 mb-3">Acceso administrador</h1>

      <?php if ($errors): ?>
        <div class="alert alert-danger">
          <ul class="mb-0"><?php foreach ($errors as $e): ?><li><?= htmlspecialchars($e) ?></li><?php endforeach; ?></ul>
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
</main>
<?php include __DIR__ . '/../templates/footer.php'; ?>
