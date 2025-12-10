<?php
// admin/producto_form.php — Crear / editar producto con imagen y validaciones
declare(strict_types=1);

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../inc/auth.php';
require_once __DIR__ . '/../inc/flash.php';

$CONTEXT    = 'admin';
$PAGE_TITLE = 'Producto';
$BASE       = defined('BASE_URL') ? rtrim(BASE_URL, '/') : '/shopping';

requireAdmin();
$pdo = getConnection();

// =========================
//  Helpers
// =========================
function product_image_url(?string $fname): ?string {
    if (!$fname) return null;
    $fname = trim($fname);
    if ($fname === '') return null;

    $base = defined('BASE_URL') ? rtrim(BASE_URL, '/') : '';
    $uploadsFs = __DIR__ . '/../uploads/' . $fname;
    $imagesFs  = __DIR__ . '/../images/'  . $fname;

    if (is_file($uploadsFs)) return $base . '/uploads/' . $fname;
    if (is_file($imagesFs))  return $base . '/images/'  . $fname;
    return null;
}

function slug_filename(string $name): string {
    $name = preg_replace('~[^\pL\d]+~u', '-', $name);
    $name = trim($name, '-');
    if (function_exists('iconv')) {
        $name = iconv('utf-8', 'us-ascii//TRANSLIT', $name);
    }
    $name = strtolower($name);
    $name = preg_replace('~[^-\w.]+~', '', $name);
    return $name ?: 'file';
}

// =========================
//  Cargar producto (si edita)
// =========================
$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$isEdit = $id > 0;

$product = [
    'id'          => null,
    'name'        => '',
    'description' => '',
    'price'       => '',
    'image'       => '',
    'is_active'   => 1,
];

if ($isEdit) {
    $st = $pdo->prepare('SELECT id, name, description, price, image, is_active FROM products WHERE id = ?');
    $st->execute([$id]);
    $row = $st->fetch(PDO::FETCH_ASSOC);
    if (!$row) {
        flash_error('Producto no encontrado.');
        header('Location: ' . $BASE . '/admin/productos.php');
        exit;
    }
    $product = $row;
}

$errors = [];
$csrf = auth_csrf();

// =========================
//  POST: guardar
// =========================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf'] ?? '')) {
        flash_error('Token CSRF inválido. Recarga la página e inténtalo de nuevo.');
        header('Location: ' . $BASE . '/admin/producto_form.php' . ($isEdit ? ('?id=' . $id) : ''));
        exit;
    }

    $name        = trim((string)($_POST['name'] ?? ''));
    $description = trim((string)($_POST['description'] ?? ''));
    $price       = str_replace(',', '.', (string)($_POST['price'] ?? ''));
    $price       = (float)$price;
    $active      = isset($_POST['is_active']) ? 1 : 0;
    $deleteImg   = !empty($_POST['delete_image']);

    if ($name === '') {
        $errors[] = 'El nombre del producto es obligatorio.';
    }
    if ($price <= 0) {
        $errors[] = 'El precio debe ser mayor que 0.';
    }

    // Imagen actual (si ya existía)
    $currentImage = $product['image'] ?? '';

    // Manejo de subida de imagen
    $uploadDirFs = __DIR__ . '/../uploads';
    $newImageName = '';

    if (!empty($_FILES['image']['name'])) {
        $file = $_FILES['image'];

        if ($file['error'] === UPLOAD_ERR_OK) {
            $allowedExt = ['jpg', 'jpeg', 'png', 'webp'];
            $maxSize    = 2 * 1024 * 1024; // 2MB

            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            if (!in_array($ext, $allowedExt, true)) {
                $errors[] = 'Formato de imagen no permitido (usa JPG, PNG o WebP).';
            }
            if ($file['size'] > $maxSize) {
                $errors[] = 'La imagen supera los 2 MB.';
            }

            if (!$errors) {
                if (!is_dir($uploadDirFs)) {
                    @mkdir($uploadDirFs, 0775, true);
                }
                if (!is_dir($uploadDirFs) || !is_writable($uploadDirFs)) {
                    $errors[] = 'La carpeta /uploads no existe o no tiene permisos de escritura.';
                } else {
                    $baseName    = slug_filename(pathinfo($file['name'], PATHINFO_FILENAME));
                    $newImageName = 'prod-' . $baseName . '-' . substr(sha1(uniqid('', true)), 0, 8) . '.' . $ext;
                    $dest        = $uploadDirFs . DIRECTORY_SEPARATOR . $newImageName;

                    if (!move_uploaded_file($file['tmp_name'], $dest)) {
                        $errors[] = 'No se pudo guardar la imagen en el servidor.';
                    }
                }
            }
        } elseif ($file['error'] !== UPLOAD_ERR_NO_FILE) {
            $errors[] = 'Error al subir la imagen (código ' . (int)$file['error'] . ').';
        }
    }

    // Si no hay errores, guardamos en BD
    if (!$errors) {
        $pdo->beginTransaction();
        try {
            $imageToSave = $currentImage;

            // Borrar imagen si marcó eliminar
            if ($deleteImg && $currentImage) {
                $oldFs = $uploadDirFs . DIRECTORY_SEPARATOR . $currentImage;
                if (is_file($oldFs)) { @unlink($oldFs); }
                $imageToSave = '';
            }

            // Si hay nueva imagen, sustituye a la anterior
            if ($newImageName) {
                if ($currentImage) {
                    $oldFs = $uploadDirFs . DIRECTORY_SEPARATOR . $currentImage;
                    if (is_file($oldFs)) { @unlink($oldFs); }
                }
                $imageToSave = $newImageName;
            }

            if ($isEdit) {
                $sql = "
                    UPDATE products
                    SET name = :name,
                        description = :description,
                        price = :price,
                        image = :image,
                        is_active = :active,
                        updated_at = NOW()
                    WHERE id = :id
                    LIMIT 1
                ";
                $st = $pdo->prepare($sql);
                $st->execute([
                    ':name'        => $name,
                    ':description' => $description,
                    ':price'       => $price,
                    ':image'       => $imageToSave,
                    ':active'      => $active,
                    ':id'          => $id,
                ]);
                flash_success('Producto actualizado correctamente.');
            } else {
                $sql = "
                    INSERT INTO products (name, description, price, image, is_active, created_at, updated_at)
                    VALUES (:name, :description, :price, :image, :active, NOW(), NOW())
                ";
                $st = $pdo->prepare($sql);
                $st->execute([
                    ':name'        => $name,
                    ':description' => $description,
                    ':price'       => $price,
                    ':image'       => $imageToSave,
                    ':active'      => $active,
                ]);
                $id = (int)$pdo->lastInsertId();
                $isEdit = true;
                flash_success('Producto creado correctamente.');
            }

            $pdo->commit();
            header('Location: ' . $BASE . '/admin/productos.php');
            exit;

        } catch (Throwable $e) {
            $pdo->rollBack();
            flash_error('Ocurrió un error al guardar el producto.');
            if (defined('DEBUG') && DEBUG) {
                flash_error('DB: ' . $e->getMessage());
            }
        }
    }

    // Si llega aquí con errores, mantenemos valores del formulario
    $product['name']        = $name;
    $product['description'] = $description;
    $product['price']       = $price;
    $product['is_active']   = $active;
}

$imageUrl = product_image_url($product['image'] ?? null);

include __DIR__ . '/../templates/header.php';
?>

<main class="container py-4">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h4 mb-0">
      <?= $isEdit ? 'Editar producto' : 'Nuevo producto' ?>
    </h1>
    <a class="btn btn-outline-secondary" href="<?= $BASE ?>/admin/productos.php">← Volver al listado</a>
  </div>

  <?php if ($errors): ?>
    <div class="alert alert-danger">
      <ul class="mb-0">
        <?php foreach ($errors as $e): ?>
          <li><?= htmlspecialchars($e) ?></li>
        <?php endforeach; ?>
      </ul>
    </div>
  <?php endif; ?>

  <form method="post" enctype="multipart/form-data" class="card shadow-sm">
    <div class="card-body">
      <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf) ?>">

      <div class="row g-3">
        <div class="col-md-6">
          <label class="form-label">Nombre *</label>
          <input
            type="text"
            name="name"
            class="form-control"
            required
            maxlength="160"
            value="<?= htmlspecialchars((string)$product['name']) ?>">
        </div>

        <div class="col-md-3">
          <label class="form-label">Precio *</label>
          <div class="input-group">
            <span class="input-group-text">€</span>
            <input
              type="number"
              step="0.01"
              min="0"
              name="price"
              class="form-control"
              required
              value="<?= htmlspecialchars((string)$product['price']) ?>">
          </div>
        </div>

        <div class="col-md-3 d-flex align-items-end">
          <div class="form-check">
            <input
              class="form-check-input"
              type="checkbox"
              id="is_active"
              name="is_active"
              value="1"
              <?= (int)($product['is_active'] ?? 1) === 1 ? 'checked' : '' ?>>
            <label class="form-check-label" for="is_active">
              Producto activo
            </label>
          </div>
        </div>
      </div>

      <div class="mt-3">
        <label class="form-label">Descripción</label>
        <textarea
          name="description"
          rows="4"
          class="form-control"
          placeholder="Detalles, materiales, cuidados, etc."><?= htmlspecialchars((string)$product['description']) ?></textarea>
      </div>

      <hr class="my-4">

      <div class="row g-3 align-items-center">
        <div class="col-md-6">
          <label class="form-label d-flex justify-content-between align-items-center">
            <span>Imagen principal</span>
            <small class="text-muted">JPG / PNG / WebP • máx 2MB</small>
          </label>
          <input
            type="file"
            name="image"
            class="form-control"
            accept=".jpg,.jpeg,.png,.webp">
        </div>

        <div class="col-md-6">
          <?php if ($imageUrl): ?>
            <div class="d-flex align-items-center gap-3">
              <div>
                <span class="d-block small text-muted mb-1">Imagen actual:</span>
                <img
                  src="<?= htmlspecialchars($imageUrl) ?>"
                  alt="Imagen actual"
                  style="max-width:120px;max-height:120px;object-fit:cover;border-radius:8px;border:1px solid #e9ecef;">
              </div>
              <div class="form-check mt-3">
                <input class="form-check-input" type="checkbox" name="delete_image" id="delete_image">
                <label class="form-check-label" for="delete_image">
                  Eliminar imagen actual
                </label>
              </div>
            </div>
          <?php else: ?>
            <div class="form-text">
              Si no subes imagen, se usará un placeholder en el catálogo.
            </div>
          <?php endif; ?>
        </div>
      </div>

      <div class="mt-4 d-flex justify-content-end gap-2">
        <a href="<?= $BASE ?>/admin/productos.php" class="btn btn-outline-secondary">Cancelar</a>
        <button type="submit" class="btn btn-primary">
          <?= $isEdit ? 'Guardar cambios' : 'Crear producto' ?>
        </button>
      </div>
    </div>
  </form>
</main>

<?php include __DIR__ . '/../templates/footer.php'; ?>
