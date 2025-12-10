<?php
// admin/productos.php — listado con búsqueda, filtro y paginación + stock
declare(strict_types=1);

require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../inc/auth.php';
require_once __DIR__ . '/../inc/flash.php';

$CONTEXT    = 'admin';
$PAGE_TITLE = 'Productos';

requireAdmin(); // solo admin

$pdo  = getConnection();
$BASE = defined('BASE_URL') ? BASE_URL : '/shopping';

// CSRF unificado
$csrf = auth_csrf();

/* ==============================
   Acciones POST (activar / desactivar / borrar)
   ============================== */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (!can('productos:state') && !can('productos:delete')) {
    http_response_code(403);
    die('Acción no autorizada');
  }

  $token = $_POST['csrf'] ?? '';
  if (!verify_csrf($token)) {
    flash_error('CSRF inválido. Recarga la página e inténtalo de nuevo.');
    header('Location: ' . $BASE . '/admin/productos.php');
    exit;
  }

  $action = $_POST['action'] ?? '';
  $id     = isset($_POST['id']) ? (int)$_POST['id'] : 0;

  try {
    if ($action === 'toggle' && $id > 0) {
      $pdo->prepare("
        UPDATE products
        SET is_active = 1 - is_active, updated_at = NOW()
        WHERE id = ?
      ")->execute([$id]);

      flash_success('Estado del producto actualizado.');
      header('Location: ' . $BASE . '/admin/productos.php');
      exit;
    }

    if ($action === 'delete' && $id > 0) {
      // borrado suave → marcar como inactivo
      $pdo->prepare("
        UPDATE products
        SET is_active = 0, updated_at = NOW()
        WHERE id = ?
      ")->execute([$id]);

      flash_success('Producto inactivado.');
      header('Location: ' . $BASE . '/admin/productos.php');
      exit;
    }

    flash_info('Acción no reconocida.');
    header('Location: ' . $BASE . '/admin/productos.php');
    exit;

  } catch (Throwable $e) {
    flash_error('Ocurrió un error al procesar la acción.');
    if (defined('DEBUG') && DEBUG) {
      flash_error('DB: ' . $e->getMessage());
    }
    header('Location: ' . $BASE . '/admin/productos.php');
    exit;
  }
}

/* ==============================
   Filtros y paginación
   ============================== */
$q       = trim((string)($_GET['q'] ?? ''));
$estado  = isset($_GET['estado']) ? (string)$_GET['estado'] : 'all'; // all | 1 | 0
$page    = max(1, (int)($_GET['page'] ?? 1));
$per     = 10;
$offset  = ($page - 1) * $per;

$whereParts = [];
$params = [];

if ($q !== '') {
  $whereParts[] = '(name LIKE ? OR description LIKE ?)';
  $like = '%' . $q . '%';
  $params[] = $like;
  $params[] = $like;
}
if ($estado === '1' || $estado === '0') {
  $whereParts[] = 'is_active = ?';
  $params[] = (int)$estado;
}
$whereSql = $whereParts ? implode(' AND ', $whereParts) : '1=1';

$stmtTotal = $pdo->prepare("SELECT COUNT(*) FROM products WHERE $whereSql");
$stmtTotal->execute($params);
$totalRows  = (int)$stmtTotal->fetchColumn();
$totalPages = max(1, (int)ceil($totalRows / $per));

$sql = "
  SELECT id, name, price, image, is_active, stock
  FROM products
  WHERE $whereSql
  ORDER BY id DESC
  LIMIT $per OFFSET $offset
";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$productos = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* Helpers */
function image_url($row) {
  $fname = trim((string)($row['image'] ?? ''));
  $base  = rtrim(BASE_URL, '/');
  $up = __DIR__ . '/../uploads/' . $fname;
  $im = __DIR__ . '/../images/' . $fname;
  if ($fname && is_file($up)) return $base . '/uploads/' . $fname;
  if ($fname && is_file($im)) return $base . '/images/' . $fname;
  return $base . '/images/placeholder.jpg';
}
function build_qs(array $overrides = []) {
  $base = [
    'q'      => $_GET['q']      ?? '',
    'estado' => $_GET['estado'] ?? 'all',
  ];
  $merged = array_merge($base, $overrides);
  if (isset($merged['q']) && trim($merged['q']) === '') unset($merged['q']);
  if (isset($merged['estado']) && $merged['estado'] === 'all') unset($merged['estado']);
  return http_build_query($merged);
}

include __DIR__ . '/../templates/header.php';
?>

<div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
  <h1 class="h3 mb-0">Productos</h1>
  <a href="<?= $BASE ?>/admin/producto_form.php" class="btn btn-primary">Nuevo producto</a>
</div>

<form class="row g-2 align-items-end mb-3" method="get" action="">
  <div class="col-sm-6 col-md-4">
    <label class="form-label">Buscar</label>
    <input type="text" class="form-control" name="q" value="<?= htmlspecialchars($q) ?>" placeholder="Nombre o descripción…">
  </div>
  <div class="col-sm-4 col-md-3">
    <label class="form-label">Estado</label>
    <select name="estado" class="form-select">
      <option value="all" <?= $estado==='all'?'selected':'' ?>>Todos</option>
      <option value="1"   <?= $estado==='1'  ?'selected':'' ?>>Activos</option>
      <option value="0"   <?= $estado==='0'  ?'selected':'' ?>>Inactivos</option>
    </select>
  </div>
  <div class="col-auto">
    <button class="btn btn-outline-secondary">Filtrar</button>
  </div>
  <?php if ($q !== '' || ($estado === '1' || $estado === '0')): ?>
    <div class="col-auto">
      <a class="btn btn-outline-dark" href="<?= $BASE ?>/admin/productos.php">Limpiar</a>
    </div>
  <?php endif; ?>
</form>

<?php
$from = $totalRows ? ($offset + 1) : 0;
$to   = $offset + count($productos);
?>
<p class="text-muted small mb-2">
  Mostrando <?= $from ?>–<?= $to ?> de <?= $totalRows ?> resultado<?= $totalRows===1?'':'s' ?>.
</p>

<div class="card shadow-sm">
  <div class="card-body">
    <div class="table-responsive">
      <table class="table table-striped table-hover align-middle mb-0">
        <thead class="table-light">
          <tr>
            <th style="width:80px">ID</th>
            <th style="width:64px">Imagen</th>
            <th>Nombre</th>
            <th class="text-end" style="width:140px">Precio</th>
            <th class="text-end" style="width:100px">Stock</th>
            <th style="width:120px">Estado</th>
            <th class="text-end" style="width:260px">Acciones</th>
          </tr>
        </thead>
        <tbody>
        <?php if (!$productos): ?>
          <tr><td colspan="7" class="text-center text-muted py-4">No hay productos</td></tr>
        <?php else: foreach ($productos as $p): ?>
          <tr>
            <td><?= (int)$p['id'] ?></td>
            <td><img class="thumb-sm" src="<?= image_url($p) ?>" alt=""></td>
            <td><?= htmlspecialchars($p['name']) ?></td>
            <td class="text-end">€ <?= number_format((float)$p['price'], 2, ',', '.') ?></td>
            <td class="text-end">
              <?= (int)($p['stock'] ?? 0) ?>
            </td>
            <td>
              <?php if ((int)$p['is_active'] === 1): ?>
                <span class="badge bg-success-subtle border border-success-subtle text-success-emphasis rounded-pill">Activo</span>
              <?php else: ?>
                <span class="badge bg-secondary-subtle border border-secondary-subtle text-secondary-emphasis rounded-pill">Inactivo</span>
              <?php endif; ?>
            </td>
            <td class="text-end">
              <a class="btn btn-sm btn-outline-primary" href="<?= $BASE ?>/admin/producto_form.php?id=<?= (int)$p['id'] ?>">Editar</a>

              <form action="" method="post" class="d-inline" onsubmit="return confirm('¿Cambiar estado de este producto?');">
                <input type="hidden" name="csrf"   value="<?= htmlspecialchars($csrf) ?>">
                <input type="hidden" name="action" value="toggle">
                <input type="hidden" name="id"     value="<?= (int)$p['id'] ?>">
                <button class="btn btn-sm btn-outline-warning" type="submit">
                  <?= (int)$p['is_active']===1 ? 'Desactivar' : 'Activar' ?>
                </button>
              </form>

              <form action="" method="post" class="d-inline" onsubmit="return confirm('¿Inactivar este producto?');">
                <input type="hidden" name="csrf"   value="<?= htmlspecialchars($csrf) ?>">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id"     value="<?= (int)$p['id'] ?>">
                <button class="btn btn-sm btn-outline-danger" type="submit">Borrar</button>
              </form>
            </td>
          </tr>
        <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>

    <?php if ($totalPages > 1): ?>
      <nav class="mt-3">
        <ul class="pagination pagination-sm mb-0">
          <?php
            $prevDisabled = $page <= 1 ? ' disabled' : '';
            $nextDisabled = $page >= $totalPages ? ' disabled' : '';
          ?>
          <li class="page-item<?= $prevDisabled ?>">
            <a class="page-link" href="?<?= htmlspecialchars(build_qs(['page' => $page - 1])) ?>">«</a>
          </li>

          <?php for ($i = 1; $i <= $totalPages; $i++): ?>
            <?php $active = ($i === $page) ? ' active' : ''; ?>
            <li class="page-item<?= $active ?>">
              <a class="page-link" href="?<?= htmlspecialchars(build_qs(['page' => $i])) ?>"><?= $i ?></a>
            </li>
          <?php endfor; ?>

          <li class="page-item<?= $nextDisabled ?>">
            <a class="page-link" href="?<?= htmlspecialchars(build_qs(['page' => $page + 1])) ?>">»</a>
          </li>
        </ul>
      </nav>
    <?php endif; ?>
  </div>
</div>

<?php include __DIR__ . '/../templates/footer.php'; ?>
