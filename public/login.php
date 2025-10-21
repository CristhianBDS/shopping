<?php
// public/login.php — Login clientes (con CSRF unificado)
require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../inc/flash.php';
require_once __DIR__ . '/../inc/auth.php';

$CONTEXT    = 'public';
$PAGE_TITLE = 'Ingresar';
$BASE       = defined('BASE_URL') ? BASE_URL : '/shopping';

if (session_status() !== PHP_SESSION_ACTIVE) session_start();

// Si ya está logueado, fuera
if (!empty($_SESSION['user']) && (($_SESSION['user']['role'] ?? '') !== '')) {
  header('Location: '.$BASE.'/public/index.php');
  exit;
}

// CSRF unificado (mismo helper que en logout/otros formularios)
$csrf = auth_csrf();

// Manejo de "next"
$next = $_GET['next'] ?? $_POST['next'] ?? ($BASE.'/public/index.php');

$email  = '';
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  // Valida CSRF
  $token = $_POST['csrf'] ?? '';
  if (!verify_csrf($token)) {
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
          'role'  => (string)$u['role'], // 'admin' o 'member'
        ];
        flash_success('¡Bienvenido!');
        header('Location: '.$next);
        exit;
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
<section class="container py-5">
  <h1 class="h3 text-center mb-4">Ingresar</h1>

  <?php if ($errors): ?>
    <div class="alert alert-danger">
      <ul class="mb-0">
        <?php foreach ($errors as $e): ?><li><?= htmlspecialchars($e) ?></li><?php endforeach; ?>
      </ul>
    </div>
  <?php endif; ?>

  <form method="post" class="mx-auto card shadow-sm" style="max-width: 480px;" autocomplete="off" novalidate>
    <div class="card-body">
      <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf) ?>">
      <input type="hidden" name="next" value="<?= htmlspecialchars($next) ?>">

      <div class="mb-3">
        <label class="form-label">Email</label>
        <input type="email" name="email" class="form-control" required value="<?= htmlspecialchars($email) ?>">
      </div>

      <div class="mb-3">
        <label class="form-label">Contraseña</label>
        <input type="password" name="password" class="form-control" required>
      </div>

      <button class="btn btn-primary w-100">Entrar</button>
      <a class="btn btn-outline-secondary w-100 mt-2" href="<?= $BASE ?>/public/registro.php">Crear cuenta</a>
    </div>
  </form>
</section>
<?php include __DIR__ . '/../templates/footer.php'; ?>
