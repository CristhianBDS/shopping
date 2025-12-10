<?php
// admin/usuario_form.php — Crear / editar usuario (adaptado a nombre real de columna de contraseña)
declare(strict_types=1);

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../inc/flash.php';
require_once __DIR__ . '/../inc/auth.php';

$CONTEXT    = 'admin';
$PAGE_TITLE = 'Usuario';
$BASE       = defined('BASE_URL') ? rtrim(BASE_URL, '/') : '/shopping';

requireAdmin();

$pdo   = getConnection();
$csrf  = auth_csrf();

/**
 * Detecta el nombre real de la columna de contraseña en la tabla users.
 * Busca entre varios candidatos y devuelve el nombre EXACTO (con su case).
 * Si no encuentra ninguno, devuelve null.
 */
function user_password_column(PDO $pdo): ?string {
    static $cached = null;
    if ($cached !== null) {
        return $cached;
    }

    try {
        $st = $pdo->query('SHOW COLUMNS FROM users');
        $cols = $st->fetchAll(PDO::FETCH_ASSOC);
    } catch (Throwable $e) {
        return null;
    }

    $candidates = ['password', 'pass', 'passwd', 'password_hash', 'hash'];

    foreach ($cols as $col) {
        $field = $col['Field'] ?? '';
        $low   = strtolower((string)$field);
        foreach ($candidates as $cand) {
            if ($low === strtolower($cand)) {
                $cached = $field; // nombre tal cual en la tabla
                return $cached;
            }
        }
    }

    return null;
}

$PWD_COL = user_password_column($pdo);

// ============================
//  Detectar modo (nuevo / edit)
// ============================
$id   = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$mode = $id > 0 ? 'edit' : 'create';

// Valores por defecto
$name      = '';
$email     = '';
$role      = 'member'; // internamente 'member' = "user"
$is_active = 1;

// Si es edición, cargar registro
if ($mode === 'edit') {
    $st = $pdo->prepare('SELECT id, name, email, role, is_active FROM users WHERE id = ? LIMIT 1');
    $st->execute([$id]);
    $row = $st->fetch(PDO::FETCH_ASSOC);

    if (!$row) {
        flash_error('Usuario no encontrado.');
        header('Location: ' . $BASE . '/admin/usuarios.php');
        exit;
    }

    $name      = (string)$row['name'];
    $email     = (string)$row['email'];
    $role      = (string)$row['role'];
    $is_active = (int)$row['is_active'];
}

// ============================
//  Procesar POST
// ============================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf'] ?? '';
    if (!verify_csrf($token)) {
        flash_error('Token CSRF inválido. Recarga la página e inténtalo de nuevo.');
        header('Location: ' . $BASE . '/admin/usuario_form.php' . ($mode === 'edit' ? ('?id=' . $id) : ''));
        exit;
    }

    // Recolectar datos
    $name      = trim((string)($_POST['name'] ?? ''));
    $email     = trim((string)($_POST['email'] ?? ''));
    $role      = trim((string)($_POST['role'] ?? 'member'));
    $is_active = !empty($_POST['is_active']) ? 1 : 0;

    $password        = (string)($_POST['password'] ?? '');
    $password_repeat = (string)($_POST['password_repeat'] ?? '');

    // Normalizar rol: mostramos "user" pero almacenamos "member"
    if ($role === 'user') {
        $role = 'member';
    }

    $errors = [];

    // Validaciones básicas
    if ($name === '') {
        $errors[] = 'El nombre es obligatorio.';
    }
    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'El email no es válido.';
    }
    if (!in_array($role, ['admin', 'member'], true)) {
        $errors[] = 'Rol inválido.';
    }

    // Si no tenemos columna de contraseña, avisamos (no intentamos guardar nada)
    if (!$PWD_COL) {
        $errors[] = 'No se encontró ninguna columna de contraseña en la tabla "users" '
                  . '(se esperaba alguna de: password, pass, passwd, password_hash, hash). '
                  . 'Revisa la estructura de la base de datos.';
    }

    // Contraseña:
    // - Crear: obligatoria si existe columna
    // - Editar: opcional (solo si quieres cambiarla)
    $changePassword = false;
    if ($PWD_COL) {
        if ($mode === 'create') {
            if ($password === '' || strlen($password) < 6) {
                $errors[] = 'La contraseña es obligatoria y debe tener al menos 6 caracteres.';
            }
            if ($password !== $password_repeat) {
                $errors[] = 'Las contraseñas no coinciden.';
            } else {
                $changePassword = true;
            }
        } else { // edit
            if ($password !== '' || $password_repeat !== '') {
                if ($password === '' || strlen($password) < 6) {
                    $errors[] = 'Si cambias la contraseña, debe tener al menos 6 caracteres.';
                }
                if ($password !== $password_repeat) {
                    $errors[] = 'Las contraseñas no coinciden.';
                } else {
                    $changePassword = true;
                }
            }
        }
    }

    // Comprobar email duplicado
    if (!$errors) {
        if ($mode === 'create') {
            $st = $pdo->prepare('SELECT COUNT(*) FROM users WHERE email = ?');
            $st->execute([$email]);
        } else {
            $st = $pdo->prepare('SELECT COUNT(*) FROM users WHERE email = ? AND id <> ?');
            $st->execute([$email, $id]);
        }
        $exists = (int)$st->fetchColumn() > 0;

        if ($exists) {
            $errors[] = 'Ya existe un usuario con ese email.';
        }
    }

    // Si hay errores, volver al formulario
    if ($errors) {
        foreach ($errors as $e) {
            flash_error($e);
        }
        header('Location: ' . $BASE . '/admin/usuario_form.php' . ($mode === 'edit' ? ('?id=' . $id) : ''));
        exit;
    }

    // ============================
    //  Guardar en BD
    // ============================
    try {
        if ($mode === 'create') {
            $hash = password_hash($password, PASSWORD_DEFAULT);

            // INSERT usando el nombre real de la columna de contraseña
            $sql = "
                INSERT INTO users (name, email, `{$PWD_COL}`, role, is_active, created_at, updated_at)
                VALUES (?, ?, ?, ?, ?, NOW(), NOW())
            ";
            $st = $pdo->prepare($sql);
            $st->execute([$name, $email, $hash, $role, $is_active]);

            flash_success('Usuario creado correctamente.');
        } else {
            if ($changePassword) {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $sql = "
                    UPDATE users
                    SET name = ?, email = ?, `{$PWD_COL}` = ?, role = ?, is_active = ?, updated_at = NOW()
                    WHERE id = ?
                    LIMIT 1
                ";
                $st = $pdo->prepare($sql);
                $st->execute([$name, $email, $hash, $role, $is_active, $id]);
            } else {
                $sql = "
                    UPDATE users
                    SET name = ?, email = ?, role = ?, is_active = ?, updated_at = NOW()
                    WHERE id = ?
                    LIMIT 1
                ";
                $st = $pdo->prepare($sql);
                $st->execute([$name, $email, $role, $is_active, $id]);
            }

            flash_success('Usuario actualizado correctamente.');
        }

        header('Location: ' . $BASE . '/admin/usuarios.php');
        exit;

    } catch (Throwable $e) {
        flash_error('Ocurrió un error al guardar el usuario.');
        if (defined('DEBUG') && DEBUG) {
            flash_error('DB: ' . $e->getMessage());
        }
        header('Location: ' . $BASE . '/admin/usuario_form.php' . ($mode === 'edit' ? ('?id=' . $id) : ''));
        exit;
    }
}

// ============================
//  Render formulario
// ============================
include __DIR__ . '/../templates/header.php';
?>
<main class="container py-4">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h4 mb-0">
      <?= $mode === 'edit' ? 'Editar usuario' : 'Nuevo usuario' ?>
    </h1>
    <a class="btn btn-outline-secondary" href="<?= $BASE ?>/admin/usuarios.php">← Volver al listado</a>
  </div>

  <div class="card shadow-sm">
    <div class="card-body">
      <form method="post" novalidate>
        <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf) ?>">

        <div class="row g-3">
          <div class="col-md-6">
            <label class="form-label">Nombre completo</label>
            <input
              type="text"
              name="name"
              class="form-control"
              required
              value="<?= htmlspecialchars($name) ?>">
          </div>

          <div class="col-md-6">
            <label class="form-label">Email</label>
            <input
              type="email"
              name="email"
              class="form-control"
              required
              value="<?= htmlspecialchars($email) ?>">
          </div>

          <div class="col-md-4">
            <label class="form-label">Rol</label>
            <select name="role" class="form-select">
              <option value="admin"  <?= $role === 'admin'  ? 'selected' : '' ?>>admin</option>
              <option value="member" <?= $role === 'member' ? 'selected' : '' ?>>user</option>
            </select>
            <div class="form-text">
              "user" se guarda internamente como <code>member</code>.
            </div>
          </div>

          <div class="col-md-4 d-flex align-items-center mt-4">
            <div class="form-check">
              <input
                class="form-check-input"
                type="checkbox"
                name="is_active"
                id="is_active"
                value="1"
                <?= $is_active ? 'checked' : '' ?>>
              <label class="form-check-label" for="is_active">
                Usuario activo
              </label>
            </div>
          </div>
        </div>

        <hr class="my-4">

        <div class="row g-3">
          <div class="col-md-6">
            <label class="form-label">
              Contraseña <?= $mode === 'edit' ? '(opcional)' : '*' ?>
            </label>
            <input
              type="password"
              name="password"
              class="form-control"
              <?= ($mode === 'create' && $PWD_COL) ? 'required' : '' ?>>
          </div>

          <div class="col-md-6">
            <label class="form-label">
              Repetir contraseña <?= $mode === 'edit' ? '(opcional)' : '*' ?>
            </label>
            <input
              type="password"
              name="password_repeat"
              class="form-control"
              <?= ($mode === 'create' && $PWD_COL) ? 'required' : '' ?>>
          </div>

          <div class="col-12">
            <div class="form-text">
              <?php if (!$PWD_COL): ?>
                Atención: no se detectó un campo de contraseña en la tabla <code>users</code>.
                Revisa la estructura de la base de datos.
              <?php elseif ($mode === 'create'): ?>
                La contraseña debe tener al menos 6 caracteres.
              <?php else: ?>
                Si dejas la contraseña en blanco, se mantendrá la actual.
              <?php endif; ?>
            </div>
          </div>
        </div>

        <div class="mt-4 d-flex justify-content-end gap-2">
          <a class="btn btn-outline-secondary" href="<?= $BASE ?>/admin/usuarios.php">Cancelar</a>
          <button type="submit" class="btn btn-primary">
            <?= $mode === 'edit' ? 'Guardar cambios' : 'Crear usuario' ?>
          </button>
        </div>
      </form>
    </div>
  </div>
</main>

<?php include __DIR__ . '/../templates/footer.php'; ?>
