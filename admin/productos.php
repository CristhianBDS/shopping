<?php
// admin/productos.php
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../inc/auth.php'; // asume que aquí validas sesión/rol

$pdo = getConnection();
$stmt = $pdo->query("SELECT id, name, price, image, is_active FROM products ORDER BY id DESC");
$productos = $stmt->fetchAll(PDO::FETCH_ASSOC);

include __DIR__ . '/../templates/header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <h1 class="h3 mb-0">Productos</h1>
  <a href="<?= BASE_URL ?>/admin/producto_form.php" class="btn btn-primary">Nuevo producto</a>
</div>

<div class="card shadow-sm">
  <div class="card-body">
    <div class="table-responsive">
      <table class="table table-striped table-hover align-middle mb-0">
        <thead>
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
          <?php else: ?>
            <?php
              function image_url($row) {
                $fname = trim((string)($row['image'] ?? ''));
                $base  = rtrim(BASE_URL, '/');
                // Preferimos /uploads si existe el archivo; si no, /images; si no, placeholder
                $up = __DIR__ . '/../uploads/' . $fname;
                $im = __DIR__ . '/../images/' . $fname;
                if ($fname && is_file($up)) return $base . '/uploads/' . $fname;
                if ($fname && is_file($im)) return $base . '/images/' . $fname;
                return $base . '/images/placeholder.jpg';
              }
            ?>
            <?php foreach ($productos as $p): ?>
              <tr>
                <td><?= (int)$p['id'] ?></td>
                <td>
                  <img src="<?= image_url($p) ?>" alt="" style="width:48px;height:48px;object-fit:cover;border-radius:8px;">
                </td>
                <td><?= htmlspecialchars($p['name']) ?></td>
                <td class="text-end">€ <?= number_format((float)$p['price'], 2, ',', '.') ?></td>
                <td>
                  <?php if ((int)$p['is_active'] === 1): ?>
                    <span class="badge text-bg-success">Activo</span>
                  <?php else: ?>
                    <span class="badge text-bg-secondary">Inactivo</span>
                  <?php endif; ?>
                </td>
                <td class="text-end">
                  <a class="btn btn-sm btn-outline-primary" href="<?= BASE_URL ?>/admin/producto_form.php?id=<?= (int)$p['id'] ?>">Editar</a>
                  <!-- Próximamente: activar/desactivar y borrar -->
                </td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<?php include __DIR__ . '/../templates/footer.php'; ?>
