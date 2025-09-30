<?php
// admin/login.php (robusto a distintos esquemas de users)
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/db.php';
if (session_status() !== PHP_SESSION_ACTIVE) session_start();

$pdo = getConnection();

// Si ya está logueado, fuera
if (!empty($_SESSION['user'])) {
  header('Location: ' . BASE_URL . '/admin/index.php');
  exit;
}

// CSRF
if (empty($_SESSION['csrf_login'])) {
  $_SESSION['csrf_login'] = bin2hex(random_bytes(32));
}
$csrf = $_SESSION['csrf_login'];

$errors = [];
$notice = '';
if (isset($_GET['bye'])) $notice = 'Sesión cerrada correctamente.';

// Redirección post-login
$redirect = isset($_GET['r']) ? trim($_GET['r']) : (BASE_URL . '/admin/index.php');

// Procesar login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $token = $_POST['csrf'] ?? '';
  if (!hash_equals($_SESSION['csrf_login'] ?? '', $token)) {
    $errors[] = 'Token inválido. Recarga la página.';
  } else {
    $email = strtolower(trim($_POST['email'] ?? ''));
    $pass  = (string)($_POST['password'] ?? '');

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Email inválido.';
    if ($pass === '') $errors[] = 'La contraseña es obligatoria.';

    // Rate limit simple: 5 intentos / 15min
    $_SESSION['login_attempts'] = $_SESSION['login_attempts'] ?? [];
    $_SESSION['login_attempts'] = array_filter(
      $_SESSION['login_attempts'],
      fn($t) => $t > time() - 900
    );
    if (count($_SESSION['login_attempts']) >= 5) {
      $errors[] = 'Demasiados intentos. Inténtalo en unos minutos.';
    }

    if (!$errors) {
      // ¡Clave! Pedimos *todas* las columnas para no fallar por nombres distintos.
      $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? LIMIT 1");
      $stmt->execute([$email]);
      $u = $stmt->fetch(PDO::FETCH_ASSOC);

      if (!$u || (int)($u['is_active'] ?? 1) !== 1) {
        $errors[] = 'Usuario no encontrado o inactivo.';
      } else {
        // Detectar la columna de contraseña disponible
        $hashCols = ['password_hash', 'password', 'contrasena', 'contraseña'];
        $hash = '';
        foreach ($hashCols as $col) {
          if (array_key_exists($col, $u) && trim((string)$u[$col]) !== '') {
            $hash = (string)$u[$col];
            break;
          }
        }

        if ($hash === '') {
          $errors[] = 'No hay contraseña configurada para este usuario.';
        } else {
          $ok = false;
          $info = password_get_info($hash);

          if (($info['algo'] ?? 0) !== 0) {
            // Es un hash soportado por password_verify
            $ok = password_verify($pass, $hash);

            // Rehash si el coste o el algoritmo quedó viejo
            if ($ok && password_needs_rehash($hash, PASSWORD_DEFAULT)) {
              $new = password_hash($pass, PASSWORD_DEFAULT);
              $pdo->prepare("UPDATE users SET password_hash = ?, updated_at = NOW() WHERE id = ?")
                  ->execute([$new, (int)$u['id']]);
            }
          } else {
            // Parece texto plano u otro algoritmo no soportado: migramos si coincide exactamente
            if (hash_equals($hash, $pass)) {
              $ok = true;
              $new = password_hash($pass, PASSWORD_DEFAULT);
              // Guardamos en password_hash y mantenemos el viejo campo por compatibilidad
              $pdo->prepare("UPDATE users SET password_hash = ?, updated_at = NOW() WHERE id = ?")
                  ->execute([$new, (int)$u['id']]);
            }
          }

          if ($ok) {
            session_regenerate_id(true);
            $_SESSION['user'] = [
              'id'    => (int)$u['id'],
              'name'  => $u['name'] ?? '',
              'email' => $u['email'] ?? $email,
              'role'  => $u['role'] ?? 'admin', // por si tu tabla no tiene 'role'
            ];
            $_SESSION['login_attempts'] = [];
            unset($_SESSION['csrf_login']);
            header('Location: ' . $redirect);
            exit;
          } else {
            $_SESSION['login_attempts'][] = time();
            $errors[] = 'Credenciales inválidas.';
          }
        }
      }
    }
  }
}

include __DIR__ . '/../templates/header.php';
?>
<div class="row justify-content-center">
  <div class="col-md-6 col-lg-5">
    <h1 class="h3 mb-3">Acceso al panel</h1>

    <?php if ($notice): ?>
      <div class="alert alert-success"><?= htmlspecialchars($notice) ?></div>
    <?php endif; ?>

    <?php if ($errors): ?>
      <div class="alert alert-danger">
        <ul class="mb-0">
          <?php foreach ($errors as $e): ?>
            <li><?= htmlspecialchars($e) ?></li>
          <?php endforeach; ?>
        </ul>
      </div>
    <?php endif; ?>

    <div class="card shadow-sm">
      <div class="card-body">
        <form method="post" action="">
          <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf) ?>">
          <div class="mb-3">
            <label class="form-label">Email</label>
            <input name="email" type="email" class="form-control" required autocomplete="username">
          </div>
          <div class="mb-3">
            <label class="form-label">Contraseña</label>
            <input name="password" type="password" class="form-control" required autocomplete="current-password">
          </div>
          <button class="btn btn-primary w-100">Entrar</button>
        </form>
      </div>
    </div>

    <p class="text-muted small mt-3 mb-0">
      ¿Olvidaste tu contraseña? (Pendiente de implementar recuperación)
    </p>
  </div>
</div>
<?php include __DIR__ . '/../templates/footer.php'; ?>
