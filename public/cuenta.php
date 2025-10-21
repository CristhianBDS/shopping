<?php
// public/cuenta.php — Configuración de cuenta (perfil + contraseña)
require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../inc/flash.php';
require_once __DIR__ . '/../inc/auth.php';

$CONTEXT    = 'public';
$PAGE_TITLE = 'Mi cuenta';
$BASE       = defined('BASE_URL') ? BASE_URL : '/shopping';

requireLogin();
$user = currentUser();
$uid  = (int)$user['id'];

$pdo = getConnection();

// Carga actual
$st = $pdo->prepare('SELECT id, name, email FROM users WHERE id = ? LIMIT 1');
$st->execute([$uid]);
$u = $st->fetch(PDO::FETCH_ASSOC);
if (!$u) { http_response_code(404); die('Usuario no encontrado'); }

$csrf = auth_csrf();
$errors = [];
$tab = $_GET['tab'] ?? 'perfil'; // 'perfil' | 'password'

// POST actualizar perfil
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $token = $_POST['csrf'] ?? '';
  if (!verify_csrf($token)) {
    $errors[] = 'CSRF inválido. Recarga la página.';
  } else {
    if (isset($_POST['action']) && $_POST['action'] === 'perfil') {
      $name  = trim((string)($_POST['name'] ?? ''));
      $email = trim((string)($_POST['email'] ?? ''));

      if ($name === '')  $errors[] = 'El nombre no puede estar vacío.';
      if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Email inválido.';

      if (!$errors) {
        // ¿email ocupado por otro?
        $st = $pdo->prepare('SELECT id FROM users WHERE email = ? AND id <> ? LIMIT 1');
        $st->execute([$email, $uid]);
        if ($st->fetch()) {
          $errors[] = 'Ese email ya está en uso por otra cuenta.';
        } else {
          $st = $pdo->prepare('UPDATE users SET name = ?, email = ?, updated_at = NOW() WHERE id = ? LIMIT 1');
          $st->execute([$name, $email, $uid]);

          // Refrescar sesión
          $_SESSION['user']['name']  = $name;
          $_SESSION['user']['email'] = $email;

          flash_success('Perfil actualizado.');
          header('Location: '.$BASE.'/public/cuenta.php?tab=perfil'); exit;
        }
      }
      $tab = 'perfil';
    }

    // Cambio de contraseña
    if (isset($_POST['action']) && $_POST['action'] === 'password') {
      $current = (string)($_POST['current_password'] ?? '');
      $pwd1    = (string)($_POST['new_password'] ?? '');
      $pwd2    = (string)($_POST['new_password2'] ?? '');

      if ($current === '' || $pwd1 === '' || $pwd2 === '') $errors[] = 'Completa todos los campos.';
      if (strlen($pwd1) < 6) $errors[] = 'La nueva contraseña debe tener al menos 6 caracteres.';
      if ($pwd1 !== $pwd2)  $errors[] = 'La nueva contraseña no coincide.';

      if (!$errors) {
        // Verificar contraseña actual
        $st = $pdo->prepare('SELECT password_hash FROM users WHERE id = ? LIMIT 1');
        $st->execute([$uid]);
        $row = $st->fetch(PDO::FETCH_ASSOC);
        if (!$row || !password_verify($current, (string)$row['password_hash'])) {
          $errors[] = 'La contraseña actual es incorrecta.';
        } else {
          $hash = password_hash($pwd1, PASSWORD_DEFAULT);
          $st = $pdo->prepare('UPDATE users SET password_hash = ?, updated_at = NOW() WHERE id = ? LIMIT 1');
          $st->execute([$hash, $uid]);
          flash_success('Contraseña actualizada.');
          header('Location: '.$BASE.'/public/cuenta.php?tab=password'); exit;
        }
      }
      $tab = 'password';
    }
  }
}

include __DIR__ . '/../templates/header.php';
?>
<section class="container py-5">
  <h1 class="mb-4">Mi cuenta</h1>

  <?php if ($errors): ?>
    <div class="alert alert-danger"><ul class="mb-0"><?php foreach ($errors as $e): ?><li><?= htmlspecialchars($e) ?></li><?php endforeach; ?></ul></div>
  <?php endif; ?>

  <ul class="nav nav-tabs mb-3">
    <li class="nav-item">
      <a class="nav-link <?= $tab==='perfil'?'active':'' ?>" href="<?= $BASE ?>/public/cuenta.php?tab=perfil">Perfil</a>
    </li>
    <li class="nav-item">
      <a class="nav-link <?= $tab==='password'?'active':'' ?>" href="<?= $BASE ?>/public/cuenta.php?tab=password">Contraseña</a>
    </li>
  </ul>

  <?php if ($tab === 'password'): ?>
    <form method="post" class="card" style="max-width: 560px;">
      <div class="card-body">
        <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf) ?>">
        <input type="hidden" name="action" value="password">
        <div class="mb-3">
          <label class="form-label">Contraseña actual</label>
          <input type="password" name="current_password" class="form-control" required>
        </div>
        <div class="mb-3">
          <label class="form-label">Nueva contraseña</label>
          <input type="password" name="new_password" class="form-control" required minlength="6">
        </div>
        <div class="mb-3">
          <label class="form-label">Repetir nueva contraseña</label>
          <input type="password" name="new_password2" class="form-control" required minlength="6">
        </div>
        <button class="btn btn-primary">Actualizar contraseña</button>
      </div>
    </form>
  <?php else: ?>
    <form method="post" class="card" style="max-width: 560px;">
      <div class="card-body">
        <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf) ?>">
        <input type="hidden" name="action" value="perfil">
        <div class="mb-3">
          <label class="form-label">Nombre</label>
          <input type="text" name="name" class="form-control" required value="<?= htmlspecialchars($u['name']) ?>">
        </div>
        <div class="mb-3">
          <label class="form-label">Email</label>
          <input type="email" name="email" class="form-control" required value="<?= htmlspecialchars($u['email']) ?>">
        </div>
        <button class="btn btn-primary">Guardar cambios</button>
      </div>
    </form>
  <?php endif; ?>
</section>
<?php include __DIR__ . '/../templates/footer.php'; ?>
