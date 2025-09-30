<?php
// admin/productos.php
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../inc/auth.php';

$pdo = getConnection();

// Acciones POST (activar/desactivar/borrar suave)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $action = $_POST['action'] ?? '';
  $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;

  try {
    if ($action === 'toggle' && $id > 0) {
      $pdo->prepare("UPDATE products SET is_active = 1 - is_active, updated_at = NOW() WHERE id = ?")->execute([$id]);
      header('Location: ' . BASE_URL . '/admin/productos.php?ok=toggle'); exit;
    }
    if ($action === 'delete' && $id > 0) {
      $pdo->prepare("UPDATE products SET is_active = 0, updated_at = NOW() WHERE id = ?")->execute([$id]);
      header('Location: ' . BASE_URL . '/admin/productos.php?ok=delete'); exit;
    }
  } catch (Throwable $e) {
    header('Location: ' . BASE_URL . '/admin/productos.php?error=1'); exit;
  }
}

// Listado
$stmt = $pdo->query("SELECT id, name, price, image, is_active FROM products ORDER BY id DESC");
$productos = $stmt->fetchAll(PDO::FETCH_ASSOC);

function image_url($row) {
  $fname = trim((string)($row['image'] ?? ''));
  $base  = rtrim(BASE_URL, '/');
  $up = __DIR__ . '/../uploads/' . $fname;
  $im = __DIR__ . '/../images/' . $fname;
  if ($fname && is_file($up)) return $base . '/uploads/' . $fname;
  if ($fname && is_file($im)) return $base . '/images/' . $fname;
  return $base . '/images/placeholder.jpg';
}

include __DIR__ . '/../templates/header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <h1 class="h3 mb-0">Productos</h1>
  <a href="<?= BASE_URL ?>/admin/producto_form.php" class="btn btn-primary">Nuevo producto</a>
</div>

<?php if (isset($_GET['ok'])): ?>
  <div class="alert alert-success">Acción realizada correctamente.</div>
<?php elseif (isset($_GET['error'])): ?>
  <div class="alert alert-danger">Ocurrió un error. Inténtalo de nuevo.</div>
<?php endif; ?>

<div class="card shadow-sm">
  <div class="card-body">
    <div class="table-responsive">
      <table class="table table-striped table-hover align-middle mb-0">
        <thead class="table-light">
          <tr>
            <th>ID</th>
            <th>Imagen</th>
            <th>Nombre</th>
            <th class="text-end">Precio</th>
            <th>Estado</th>
            <th class="text-end">Acciones</th>
          </tr>
        </thead>
        <tbody>
          <?php if (!$productos): ?>
            <tr><td colspan="6" class="text-center text-muted py-4">No hay productos</td></tr>
          <?php else: foreach ($productos as $p): ?>
            <tr>
              <td><?= (int)$p['id'] ?></td>
              <td><img class="thumb-sm" src="<?= image_url($p) ?>" alt=""></td>
              <td><?= htmlspecialchars($p['name']) ?></td>
              <td class="text-end">€ <?= number_format((float)$p['price'], 2, ',', '.') ?></td>
              <td>
                <?php if ((int)$p['is_active'] === 1): ?>
                  <span class="badge bg-success-subtle border border-success-subtle text-success-emphasis rounded-pill">Activo</span>
                <?php else: ?>
                  <span class="badge bg-secondary-subtle border border-secondary-subtle text-secondary-emphasis rounded-pill">Inactivo</span>
                <?php endif; ?>
              </td>
              <td class="text-end">
                <a class="btn btn-sm btn-outline-primary" href="<?= BASE_URL ?>/admin/producto_form.php?id=<?= (int)$p['id'] ?>">Editar</a>

                <form action="" method="post" class="d-inline">
                  <input type="hidden" name="action" value="toggle">
                  <input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
                  <button class="btn btn-sm btn-outline-warning" type="submit">
                    <?= (int)$p['is_active']===1 ? 'Desactivar' : 'Activar' ?>
                  </button>
                </form>

                <form action="" method="post" class="d-inline" onsubmit="return confirm('¿Inactivar este producto?');">
                  <input type="hidden" name="action" value="delete">
                  <input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
                  <button class="btn btn-sm btn-outline-danger" type="submit">Borrar</button>
                </form>
              </td>
            </tr>
          <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
<?php include __DIR__ . '/../templates/footer.php'; ?>
