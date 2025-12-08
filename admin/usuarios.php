<?php
// admin/usuarios.php — Listado y acciones (activar/desactivar)
declare(strict_types=1);

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../inc/flash.php';
require_once __DIR__ . '/../inc/auth.php';

$CONTEXT    = 'admin';
$PAGE_TITLE = 'Usuarios';
// BASE genérica
$BASE       = defined('BASE_URL') ? rtrim(BASE_URL, '/') : '/shopping';

// Debe estar logueado como admin (luego refinamos permisos con can())
requireAdmin();

// Lectura permitida a admin y member/user
if (!can('usuarios:list')) {
  http_response_code(403);
  die('Acceso no autorizado');
}

// CSRF para acciones POST
if (empty($_SESSION['csrf_users'])) {
  $_SESSION['csrf_users'] = bin2hex(random_bytes(32));
}
$csrf = $_SESSION['csrf_users'];

$pdo = getConnection();

// ---- Acciones POST: activar/desactivar (solo admin por defecto) ----
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (!can('usuarios:state')) {
    http_response_code(403);
    die('Acción no autorizada');
  }

  $token = $_POST['csrf'] ?? '';
  if (!hash_equals($_SESSION['csrf_users'] ?? '', $token)) {
    flash_error('CSRF inválido.');
    header('Location: ' . $BASE . '/admin/usuarios.php');
    exit;
  }

  $id    = (int)($_POST['id'] ?? 0);
  $state = (int)($_POST['state'] ?? 0); // 1 activar, 0 desactivar

  if ($id > 0) {
    // Evitar que un admin se desactive a sí mismo
    if ($id === (int)($_SESSION['user']['id'] ?? 0) && $state === 0) {
      flash_error('No puedes desactivar tu propio usuario.');
    } else {
      $st = $pdo->prepare('UPDATE users SET is_active = ?, updated_at = NOW() WHERE id = ? LIMIT 1');
      $st->execute([$state, $id]);
      flash_success($state ? 'Usuario activado.' : 'Usuario desactivado.');
    }
  } else {
    flash_error('Solicitud inválida.');
  }

  header('Location: ' . $BASE . '/admin/usuarios.php');
  exit;
}

// ---- Filtros GET ----
$q      = trim((string)($_GET['q'] ?? ''));
$role   = trim((string)($_GET['role'] ?? '')); // 'admin' | 'member' | 'user' | ''
$status = $_GET['status'] ?? '';              // '1' | '0' | ''

// Normaliza 'user' -> 'member' para filtro
if ($role === 'user') {
  $role = 'member';
}

$where  = [];
$params = [];

if ($q !== '') {
  $where[] = '(name LIKE ? OR email LIKE ?)';
  $params[] = '%' . $q . '%';
  $params[] = '%' . $q . '%';
}
if ($role !== '' && in_array($role, ['admin', 'member'], true)) {
  $where[] = 'role = ?';
  $params[] = $role;
}
if ($status !== '' && ($status === '1' || $status === '0')) {
  $where[] = 'is_active = ?';
  $params[] = (int)$status;
}

$sql = 'SELECT id, name, email, role, is_active, created_at FROM users';
if ($where) {
  $sql .= ' WHERE ' . implode(' AND ', $where);
}
$sql .= ' ORDER BY created_at DESC';

$st = $pdo->prepare($sql);
$st->execute($params);
$rows = $st->fetchAll(PDO::FETCH_ASSOC);

include __DIR__ . '/../templates/header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <h1 class="h4 mb-0">Usuarios</h1>
  <?php if (can('usuarios:create')): ?>
    <a class="btn btn-primary" href="<?= $BASE ?>/admin/usuario_form.php">Nuevo usuario</a>
  <?php endif; ?>
</div>

<form class="row gy-2 gx-2 mb-3" method="get">
  <div class="col-md-4">
    <input
      class="form-control"
      type="text"
      name="q"
      value="<?= htmlspecialchars($q) ?>"
      placeholder="Buscar por nombre o email">
  </div>
  <div class="col-md-3">
    <select name="role" class="form-select">
      <option value="">Rol (todos)</option>
      <option value="admin"  <?= $role === 'admin'  ? 'selected' : '' ?>>admin</option>
      <option value="member" <?= $role === 'member' ? 'selected' : '' ?>>member</option>
      <option value="user"   <?= ($role === 'user' ? 'selected' : '') /* compat visual */ ?>>user</option>
    </select>
  </div>
  <div class="col-md-3">
    <select name="status" class="form-select">
      <option value="">Estado (todos)</option>
      <option value="1" <?= $status === '1' ? 'selected' : '' ?>>Activo</option>
      <option value="0" <?= $status === '0' ? 'selected' : '' ?>>Inactivo</option>
    </select>
  </div>
  <div class="col-md-2 d-grid">
    <button class="btn btn-outline-secondary">Filtrar</button>
  </div>
</form>

<div class="table-responsive">
  <table class="table table-sm align-middle">
    <thead>
      <tr>
        <th>ID</th>
        <th>Nombre</th>
        <th>Email</th>
        <th>Rol</th>
        <th>Estado</th>
        <th>Creado</th>
        <th class="text-end">Acciones</th>
      </tr>
    </thead>
    <tbody>
    <?php if (!$rows): ?>
      <tr>
        <td colspan="7" class="text-center text-muted">Sin resultados</td>
      </tr>
    <?php else: foreach ($rows as $u): ?>
      <tr>
        <td><?= (int)$u['id'] ?></td>
        <td><?= htmlspecialchars($u['name']) ?></td>
        <td><?= htmlspecialchars($u['email']) ?></td>
        <td>
          <?php
            // Mostrar "user" si internamente usas "member"
            $roleLabel = $u['role'] === 'member' ? 'user' : $u['role'];
            $badge     = ($roleLabel === 'admin') ? 'primary' : 'secondary';
          ?>
          <span class="badge bg-<?= $badge ?>"><?= htmlspecialchars($roleLabel) ?></span>
        </td>
        <td>
          <?php if ((int)$u['is_active'] === 1): ?>
            <span class="badge bg-success">Activo</span>
          <?php else: ?>
            <span class="badge bg-danger">Inactivo</span>
          <?php endif; ?>
        </td>
        <td><?= htmlspecialchars($u['created_at']) ?></td>
        <td class="text-end">
          <div class="btn-group btn-group-sm">
            <a
              class="btn btn-outline-secondary"
              href="<?= $BASE ?>/admin/usuario_form.php?id=<?= (int)$u['id'] ?>">
              Editar
            </a>

            <?php if (can('usuarios:state')): ?>
              <form method="post" class="d-inline">
                <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf) ?>">
                <input type="hidden" name="id"   value="<?= (int)$u['id'] ?>">
                <?php if ((int)$u['is_active'] === 1): ?>
                  <input type="hidden" name="state" value="0">
                  <button
                    class="btn btn-outline-danger"
                    onclick="return confirm('¿Desactivar este usuario?')">
                    Desactivar
                  </button>
                <?php else: ?>
                  <input type="hidden" name="state" value="1">
                  <button class="btn btn-outline-success">
                    Activar
                  </button>
                <?php endif; ?>
              </form>
            <?php endif; ?>
          </div>
        </td>
      </tr>
    <?php endforeach; endif; ?>
    </tbody>
  </table>
</div>

<?php include __DIR__ . '/../templates/footer.php'; ?>
