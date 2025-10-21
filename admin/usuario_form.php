<?php
// admin/usuario_form.php — Alta/Edición de usuario
require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../inc/flash.php';
require_once __DIR__ . '/../inc/auth.php';

$CONTEXT    = 'admin';
$PAGE_TITLE = 'Usuario';
$BASE       = BASE_URL;

requireAdmin('admin'); // solo admin

$pdo = getConnection();

// CSRF
if (empty($_SESSION['csrf_user_form'])) {
  $_SESSION['csrf_user_form'] = bin2hex(random_bytes(32));
}
$csrf = $_SESSION['csrf_user_form'];

$id   = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$edit = $id > 0;

$name = $email = $role = '';
$is_active = 1;

if ($edit) {
  $st = $pdo->prepare('SELECT id, name, email, role, is_active FROM users WHERE id = ? LIMIT 1');
  $st->execute([$id]);
  $row = $st->fetch(PDO::FETCH_ASSOC);
  if (!$row) { flash_error('Usuario no encontrado.'); header('Location: '.$BASE.'/admin/usuarios.php'); exit; }
  $name = $row['name']; $email = $row['email']; $role = $row['role']; $is_active = (int)$row['is_active'];
}

// POST
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $token = $_POST['csrf'] ?? '';
  if (!hash_equals($_SESSION['csrf_user_form'] ?? '', $token)) {
    $errors[] = 'CSRF inválido. Recarga la página.';
  }

  $name      = trim((string)($_POST['name'] ?? ''));
  $email     = trim((string)($_POST['email'] ?? ''));
  $role      = ($_POST['role'] ?? 'user') === 'admin' ? 'admin' : 'user';
  $is_active = isset($_POST['is_active']) ? 1 : 0;
  $pwd       = (string)($_POST['password'] ?? '');
  $pwd2      = (string)($_POST['password2'] ?? '');

  if ($name === '' || $email === '') {
    $errors[] = 'Nombre y email son obligatorios.';
  }
  if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'Email inválido.';
  }
  if (!$edit && $pwd === '') {
    $errors[] = 'La contraseña es obligatoria en el alta.';
  }
  if ($pwd !== '' && $pwd !== $pwd2) {
    $errors[] = 'Las contraseñas no coinciden.';
  }

  $chk = $pdo->prepare('SELECT id FROM users WHERE email = ? AND id <> ? LIMIT 1');
  $chk->execute([$email, $id]);
  if ($chk->fetch()) {
    $errors[] = 'Ya existe un usuario con ese email.';
  }

  if (!$errors) {
    try {
      if ($edit) {
        if ($pwd !== '') {
          $hash = password_hash($pwd, PASSWORD_DEFAULT);
          $st = $pdo->prepare('UPDATE users SET name=?, email=?, role=?, is_active=?, password_hash=?, updated_at=NOW() WHERE id=? LIMIT 1');
          $st->execute([$name, $email, $role, $is_active, $hash, $id]);
        } else {
          $st = $pdo->prepare('UPDATE users SET name=?, email=?, role=?, is_active=?, updated_at=NOW() WHERE id=? LIMIT 1');
          $st->execute([$name, $email, $role, $is_active, $id]);
        }
        flash_success('Usuario actualizado.');
      } else {
        $hash = password_hash($pwd, PASSWORD_DEFAULT);
        $st = $pdo->prepare('INSERT INTO users (name,email,role,is_active,password_hash,created_at,updated_at) VALUES (?,?,?,?,?,NOW(),NOW())');
        $st->execute([$name,$email,$role,$is_active,$hash]);
        flash_success('Usuario creado.');
      }
      unset($_SESSION['csrf_user_form']);
      header('Location: '.$BASE.'/admin/usuarios.php'); exit;

    } catch (Throwable $e) {
      flash_error('Error al guardar el usuario.');
      if (defined('DEBUG') && DEBUG) { flash_error($e->getMessage()); }
    }
  }
}

include __DIR__ . '/../templates/header.php';

?>

<h1 class="h4 mb-3"><?= $edit ? 'Editar usuario' : 'Nuevo usuario' ?></h1>

<?php if ($errors): ?>
  <div class="alert alert-danger">
    <ul class="mb-0"><?php foreach ($errors as $e): ?><li><?= htmlspecialchars($e) ?></li><?php endforeach; ?></ul>
  </div>
<?php endif; ?>

<form method="post" class="form-container" autocomplete="off" novalidate>
  <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf) ?>">

  <div class="mb-3">
    <label class="form-label">Nombre</label>
    <input type="text" name="name" class="form-control" required value="<?= htmlspecialchars($name) ?>">
  </div>

  <div class="mb-3">
    <label class="form-label">Email</label>
    <input type="email" name="email" class="form-control" required value="<?= htmlspecialchars($email) ?>">
  </div>

  <div class="row">
    <div class="col-md-4 mb-3">
      <label class="form-label">Rol</label>
      <select name="role" class="form-select">
        <option value="user"  <?= $role==='user'?'selected':'' ?>>user</option>
        <option value="admin" <?= $role==='admin'?'selected':'' ?>>admin</option>
      </select>
    </div>
    <div class="col-md-4 mb-3 d-flex align-items-end">
      <div class="form-check">
        <input class="form-check-input" type="checkbox" name="is_active" id="is_active" <?= $is_active? 'checked':'' ?>>
        <label class="form-check-label" for="is_active">Activo</label>
      </div>
    </div>
  </div>

  <hr>

  <div class="mb-2">
    <strong>Contraseña</strong>
    <small class="text-muted d-block">
      <?= $edit ? 'Déjala vacía para no cambiarla.' : 'Obligatoria para crear.' ?>
    </small>
  </div>

  <div class="row">
    <div class="col-md-6 mb-3">
      <input type="password" name="password" class="form-control" placeholder="Nueva contraseña">
    </div>
    <div class="col-md-6 mb-3">
      <input type="password" name="password2" class="form-control" placeholder="Repetir contraseña">
    </div>
  </div>

  <div class="d-flex gap-2">
    <a class="btn btn-outline-secondary" href="<?= $BASE ?>/admin/usuarios.php">Volver</a>
    <button class="btn btn-primary">Guardar</button>
  </div>
</form>

<?php include __DIR__ . '/../templates/footer.php'; ?>
