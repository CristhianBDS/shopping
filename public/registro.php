<?php
// public/registro.php — Registro de clientes/socios
require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../inc/flash.php';

$CONTEXT    = 'public';
$PAGE_TITLE = 'Hazte socio';
$BASE       = BASE_URL;

if (session_status() !== PHP_SESSION_ACTIVE) session_start();

if (!empty($_SESSION['user']) && (($_SESSION['user']['role'] ?? 'user') === 'user')) {
  header('Location: '.$BASE.'/public/cuenta.php'); exit;
}

// CSRF
if (empty($_SESSION['csrf_signup'])) {
  $_SESSION['csrf_signup'] = bin2hex(random_bytes(32));
}
$csrf = $_SESSION['csrf_signup'];

$name = $email = '';
$accept = 0;
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $token = $_POST['csrf'] ?? '';
  if (!hash_equals($_SESSION['csrf_signup'] ?? '', $token)) {
    $errors[] = 'CSRF inválido. Recarga la página.';
  }

  $name   = trim((string)($_POST['name'] ?? ''));
  $email  = trim((string)($_POST['email'] ?? ''));
  $pwd    = (string)($_POST['password'] ?? '');
  $pwd2   = (string)($_POST['password2'] ?? '');
  $accept = isset($_POST['accept']) ? 1 : 0;

  if ($name === '' || $email === '' || $pwd === '' || $pwd2 === '') {
    $errors[] = 'Todos los campos son obligatorios.';
  }
  if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'Email inválido.';
  }
  if ($pwd !== $pwd2) {
    $errors[] = 'Las contraseñas no coinciden.';
  }
  if (!$accept) {
    $errors[] = 'Debes aceptar los términos y condiciones.';
  }

  if (!$errors) {
    try {
      $pdo = getConnection();
      $chk = $pdo->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
      $chk->execute([$email]);
      if ($chk->fetch()) {
        $errors[] = 'Ya existe una cuenta con ese email.';
      } else {
        $hash = password_hash($pwd, PASSWORD_DEFAULT);
        $st = $pdo->prepare('INSERT INTO users (name,email,role,is_active,password_hash,created_at,updated_at) VALUES (?,?,?,?,?,NOW(),NOW())');
        $st->execute([$name,$email,'user',1,$hash]);

        $uid = (int)$pdo->lastInsertId();
        $_SESSION['user'] = ['id'=>$uid,'name'=>$name,'email'=>$email,'role'=>'user'];

        unset($_SESSION['csrf_signup']);
        flash_success('¡Bienvenido! Ya eres socio.');
        header('Location: '.$BASE.'/public/index.php'); exit;
      }
    } catch (Throwable $e) {
      $errors[] = 'No se pudo completar el registro.';
      if (defined('DEBUG') && DEBUG) { $errors[] = $e->getMessage(); }
    }
  }
}

include __DIR__ . '/../templates/header.php';
?>
<h1 class="h4 mb-2">Hazte socio</h1>
<p class="text-muted">Accede a <strong>mejores precios</strong>, <strong>promos</strong> y <strong>sorteos</strong>.</p>

<?php if ($errors): ?>
  <div class="alert alert-danger"><ul class="mb-0"><?php foreach ($errors as $e): ?><li><?= htmlspecialchars($e) ?></li><?php endforeach; ?></ul></div>
<?php endif; ?>

<form method="post" class="form-container" autocomplete="off" novalidate>
  <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf) ?>">

  <div class="mb-3">
    <label class="form-label">Nombre completo</label>
    <input type="text" name="name" class="form-control" required value="<?= htmlspecialchars($name) ?>">
  </div>

  <div class="mb-3">
    <label class="form-label">Email</label>
    <input type="email" name="email" class="form-control" required value="<?= htmlspecialchars($email) ?>">
  </div>

  <div class="row">
    <div class="col-md-6 mb-3">
      <label class="form-label">Contraseña</label>
      <input type="password" name="password" class="form-control" required>
    </div>
    <div class="col-md-6 mb-3">
      <label class="form-label">Repetir contraseña</label>
      <input type="password" name="password2" class="form-control" required>
    </div>
  </div>

  <div class="form-check mb-3">
    <input class="form-check-input" type="checkbox" name="accept" id="accept" <?= $accept ? 'checked':'' ?>>
    <label class="form-check-label" for="accept">
      Acepto los <a href="#" target="_blank">términos y condiciones</a>.
    </label>
  </div>

  <button class="btn btn-primary">Crear cuenta</button>
  <a class="btn btn-outline-secondary ms-2" href="<?= $BASE ?>/public/login.php">Ya tengo cuenta</a>
</form>
<?php include __DIR__ . '/../templates/footer.php'; ?>
