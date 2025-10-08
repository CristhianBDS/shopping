<?php
// admin/producto_form.php — Crear/editar producto (con CSRF y subida de imagen segura)

require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../inc/auth.php';
require_once __DIR__ . '/../inc/flash.php';

$CONTEXT    = 'admin';
$PAGE_TITLE = 'Producto';

// Antes: requireLogin();
require_admin();

$pdo = getConnection();
$id  = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// CSRF del formulario
if (empty($_SESSION['csrf_admin_form'])) {
  $_SESSION['csrf_admin_form'] = bin2hex(random_bytes(32));
}
$csrf = $_SESSION['csrf_admin_form'];

$producto = [
  'name' => '',
  'description' => '',
  'price' => '',
  'image' => '',
  'is_active' => 1,
];

$oldImage = '';

if ($id > 0) {
  $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
  $stmt->execute([$id]);
  if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $producto = $row;
    $oldImage = trim((string)($row['image'] ?? ''));
  } else {
    http_response_code(404);
    die('Producto no encontrado');
  }
}

// Utilidad para nombre de archivo seguro
function slug_filename($name) {
  $name = preg_replace('~[^\pL\d]+~u', '-', $name);
  $name = trim($name, '-');
  if (function_exists('iconv')) {
    $name = iconv('utf-8', 'us-ascii//TRANSLIT', $name);
  }
  $name = strtolower($name);
  $name = preg_replace('~[^-\w.]+~', '', $name);
  return $name ?: 'file';
}

$errors = [];
$newImageName = '';
$uploadDirPath = __DIR__ . '/../uploads';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

  // Verificación CSRF
  $token = $_POST['csrf'] ?? '';
  if (!hash_equals($_SESSION['csrf_admin_form'] ?? '', $token)) {
    $errors[] = 'Token CSRF inválido. Recarga la página.';
  }

  $name        = trim($_POST['name'] ?? '');
  $description = trim($_POST['description'] ?? '');
  $price       = str_replace(',', '.', trim($_POST['price'] ?? ''));
  $is_active   = isset($_POST['is_active']) ? 1 : 0;

  if ($name === '') $errors[] = 'El nombre es obligatorio.';
  if ($price === '' || !is_numeric($price)) $errors[] = 'Precio inválido.';
  if (strlen($name) > 150) $errors[] = 'Nombre demasiado largo (máx 150).';

  // Validación de subida (si hay archivo)
  if (!empty($_FILES['image']['name'])) {
    $file = $_FILES['image'];
    if ($file['error'] === UPLOAD_ERR_OK) {
      $allowedExt  = ['jpg','jpeg','png','webp'];
      $allowedMime = ['image/jpeg','image/png','image/webp'];
      $maxSize     = 2 * 1024 * 1024; // 2MB

      $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
      if (!in_array($ext, $allowedExt, true)) {
        $errors[] = 'Formato no permitido (JPG, PNG, WebP).';
      }
      if ($file['size'] > $maxSize) {
        $errors[] = 'La imagen supera 2 MB.';
      }

      // Validación MIME real (requiere extensión fileinfo habilitada)
      if (class_exists('finfo')) {
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime  = $finfo->file($file['tmp_name']);
        if (!in_array($mime, $allowedMime, true)) {
          $errors[] = 'El archivo no es una imagen válida.';
        }
      }

      // Preparar nombre final
      if (!$errors) {
        $baseName     = slug_filename(pathinfo($file['name'], PATHINFO_FILENAME));
        $newImageName = $baseName . '-' . substr(sha1(uniqid('', true)), 0, 8) . '.' . $ext;

        if (!is_dir($uploadDirPath)) { @mkdir($uploadDirPath, 0775, true); }
        if (!is_dir($uploadDirPath) || !is_writable($uploadDirPath)) {
          $errors[] = 'La carpeta /uploads no existe o no tiene permisos.';
        }
      }
    } else {
      $errors[] = 'Error al subir la imagen (código '.(int)$file['error'].').';
    }
  }

  if (!$errors) {
    try {
      // Mover imagen si corresponde
      if ($newImageName) {
        $dest = $uploadDirPath . DIRECTORY_SEPARATOR . $newImageName;
        if (!move_uploaded_file($_FILES['image']['tmp_name'], $dest)) {
          $errors[] = 'No se pudo mover la imagen subida.';
        } else {
          // Limpieza opcional: borrar imagen anterior si estaba en uploads
          if ($id > 0 && $oldImage) {
            $oldPath = $uploadDirPath . DIRECTORY_SEPARATOR . $oldImage;
            if (is_file($oldPath)) { @unlink($oldPath); }
          }
        }
      }

      if (!$errors) {
        if ($id > 0) {
          $sql = "UPDATE products
                  SET name = ?, description = ?, price = ?, " . ($newImageName ? "image = ?," : "") . " is_active = ?, updated_at = NOW()
                  WHERE id = ?";
          $params = [$name, $description, $price];
          if ($newImageName) $params[] = $newImageName;
          $params[] = $is_active;
          $params[] = $id;

          $pdo->prepare($sql)->execute($params);
          flash_success('Producto actualizado correctamente.');
        } else {
          $sql = "INSERT INTO products (name, description, price, image, is_active, created_at, updated_at)
                  VALUES (?, ?, ?, ?, ?, NOW(), NOW())";
          $pdo->prepare($sql)->execute([$name, $description, $price, $newImageName ?: null, $is_active]);
          $id = (int)$pdo->lastInsertId();
          flash_success('Producto creado correctamente.');
        }

        // Rotar token para evitar reenvíos
        unset($_SESSION['csrf_admin_form']);
        header('Location: ' . BASE_URL . '/admin/productos.php');
        exit;
      }
    } catch (Throwable $e) {
      $errors[] = 'Error en BD: ' . $e->getMessage();
      if (defined('DEBUG') && DEBUG) {
        // Puedes loguearlo si quieres
        // error_log($e);
      }
    }
  }

  // Repintar si hay errores
  $producto['name']       = $name;
  $producto['description']= $description;
  $producto['price']      = $price;
  $producto['is_active']  = $is_active;
  if ($newImageName) $producto['image'] = $newImageName;
}

function image_url_current($fname) {
  $fname = trim((string)$fname);
  $base  = rtrim(BASE_URL, '/');
  if ($fname === '') return $base . '/images/placeholder.jpg';
  $up = __DIR__ . '/../uploads/' . $fname;
  $im = __DIR__ . '/../images/' . $fname;
  if (is_file($up)) return $base . '/uploads/' . $fname;
  if (is_file($im)) return $base . '/images/' . $fname;
  return $base . '/images/placeholder.jpg';
}

include __DIR__ . '/../templates/header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <h1 class="h3 mb-0"><?= $id ? 'Editar' : 'Nuevo' ?> producto</h1>
  <a href="<?= BASE_URL ?>/admin/productos.php" class="btn btn-outline-secondary">Volver</a>
</div>

<?php if ($errors): ?>
  <div class="alert alert-danger">
    <strong>Revisa:</strong>
    <ul class="mb-0">
      <?php foreach ($errors as $e): ?><li><?= htmlspecialchars($e) ?></li><?php endforeach; ?>
    </ul>
  </div>
<?php endif; ?>

<div class="card shadow-sm">
  <div class="card-body">
    <form action="<?= htmlspecialchars($_SERVER['REQUEST_URI']) ?>" method="post" enctype="multipart/form-data">
      <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf) ?>">
      <div class="row g-3">
        <div class="col-lg-8">
          <div class="mb-3">
            <label class="form-label">Nombre *</label>
            <input type="text" name="name" class="form-control" maxlength="150" required value="<?= htmlspecialchars($producto['name']) ?>">
          </div>
          <div class="mb-3">
            <label class="form-label">Precio *</label>
            <input type="text" name="price" class="form-control" inputmode="decimal" required value="<?= htmlspecialchars($producto['price']) ?>">
          </div>
          <div class="mb-3">
            <label class="form-label">Descripción</label>
            <textarea name="description" rows="6" class="form-control"><?= htmlspecialchars($producto['description']) ?></textarea>
          </div>
          <button class="btn btn-primary"><?= $id ? 'Guardar cambios' : 'Crear producto' ?></button>
        </div>

        <div class="col-lg-4">
          <div class="mb-3">
            <label class="form-label">Imagen <?= $id ? '(opcional para cambiar)' : '' ?></label>
            <input type="file" name="image" class="form-control" accept=".jpg,.jpeg,.png,.webp">
            <div class="form-text">Máx 2MB. Formatos: JPG/PNG/WebP.</div>
          </div>
          <div class="form-check mb-3">
            <input class="form-check-input" type="checkbox" id="active" name="is_active" <?= (int)$producto['is_active']===1?'checked':'' ?>>
            <label class="form-check-label" for="active">Activo</label>
          </div>

          <?php if ($id): ?>
            <div class="mb-3">
              <label class="form-label d-block">Imagen actual</label>
              <img class="thumb-sm" src="<?= image_url_current($producto['image']) ?>" alt="">
            </div>
          <?php endif; ?>
        </div>
      </div>
    </form>
  </div>
</div>

<?php include __DIR__ . '/../templates/footer.php'; ?>
