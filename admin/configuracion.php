<?php
// admin/configuracion.php — Config avanzada de tienda (branding, contacto, colores, tema)
require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../inc/auth.php';
require_once __DIR__ . '/../inc/flash.php';
require_once __DIR__ . '/../inc/settings.php';

$CONTEXT     = 'admin';
$PAGE_TITLE  = 'Configuración';
$BREADCRUMB  = 'Dashboard / Configuración';

requireAdmin();

$BASE = defined('BASE_URL') ? BASE_URL : '/shopping';

/* =====================================
   Helper para nombre de archivo seguro
   ===================================== */
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

/* =====================================
   PROCESAR POST (guardar configuración)
   ===================================== */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!verify_csrf($_POST['csrf'] ?? '')) {
        flash_error('CSRF inválido. Recarga la página.');
        header('Location: ' . $BASE . '/admin/configuracion.php');
        exit;
    }

    // Datos generales
    $shop  = trim((string)($_POST['shop_name'] ?? ''));
    $slogan = trim((string)($_POST['shop_slogan'] ?? ''));
    $wa    = trim((string)($_POST['whatsapp_number'] ?? ''));
    $email = trim((string)($_POST['contact_email'] ?? ''));

    // Dirección / info de tienda
    $address = trim((string)($_POST['shop_address'] ?? ''));
    $city    = trim((string)($_POST['shop_city'] ?? ''));
    $zip     = trim((string)($_POST['shop_zip'] ?? ''));
    $footer  = trim((string)($_POST['footer_text'] ?? ''));

    // Redes sociales
    $instagram = trim((string)($_POST['social_instagram'] ?? ''));
    $facebook  = trim((string)($_POST['social_facebook'] ?? ''));
    $tiktok    = trim((string)($_POST['social_tiktok'] ?? ''));

    // Colores / tema
    $primaryColor   = trim((string)($_POST['primary_color'] ?? '#0066FF'));
    $accentColor    = trim((string)($_POST['accent_color'] ?? '#111827'));
    $themeMode      = trim((string)($_POST['theme_mode'] ?? 'light')); // light | dark | auto

    // Normalizaciones simples
    $wa    = preg_replace('/\s+/', '', $wa);
    $email = trim($email);

    // VALIDACIONES BÁSICAS
    $errors = [];

    if ($shop === '') {
        $errors[] = 'El nombre de la tienda es obligatorio.';
    }
    if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'El email de contacto no tiene un formato válido.';
    }

    // === Logo ===
    $currentLogo = setting_get('shop_logo', '');
    $deleteLogo  = !empty($_POST['delete_logo']);

    $uploadDirFs = __DIR__ . '/../uploads';
    $newLogoName = '';

    if (!empty($_FILES['shop_logo_file']['name'])) {
        $file = $_FILES['shop_logo_file'];

        if ($file['error'] === UPLOAD_ERR_OK) {
            $allowedExt  = ['jpg','jpeg','png','webp','svg'];
            $maxSize     = 2 * 1024 * 1024; // 2MB

            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            if (!in_array($ext, $allowedExt, true)) {
                $errors[] = 'Formato de logo no permitido (usa JPG, PNG, WebP o SVG).';
            }
            if ($file['size'] > $maxSize) {
                $errors[] = 'El logo supera los 2 MB.';
            }

            if (!$errors) {
                if (!is_dir($uploadDirFs)) {
                    @mkdir($uploadDirFs, 0775, true);
                }
                if (!is_dir($uploadDirFs) || !is_writable($uploadDirFs)) {
                    $errors[] = 'La carpeta /uploads no existe o no tiene permisos de escritura.';
                } else {
                    $baseName   = slug_filename(pathinfo($file['name'], PATHINFO_FILENAME));
                    $newLogoName = 'logo-' . $baseName . '-' . substr(sha1(uniqid('', true)), 0, 8) . '.' . $ext;
                    $dest       = $uploadDirFs . DIRECTORY_SEPARATOR . $newLogoName;

                    if (!move_uploaded_file($file['tmp_name'], $dest)) {
                        $errors[] = 'No se pudo guardar el logo en el servidor.';
                    }
                }
            }
        } else {
            $errors[] = 'Error al subir el logo (código ' . (int)$file['error'] . ').';
        }
    }

    // Si no hay errores, guardamos settings
    if (!$errors) {

        // Nombre, slogan, contacto
        setting_set('shop_name', $shop);
        setting_set('shop_slogan', $slogan);
        setting_set('whatsapp_number', $wa);
        setting_set('contact_email', $email);

        // Datos de tienda
        setting_set('shop_address', $address);
        setting_set('shop_city', $city);
        setting_set('shop_zip', $zip);
        setting_set('footer_text', $footer);

        // Redes
        setting_set('social_instagram', $instagram);
        setting_set('social_facebook',  $facebook);
        setting_set('social_tiktok',    $tiktok);

        // Estilos / tema
        setting_set('primary_color',  $primaryColor);
        setting_set('accent_color',   $accentColor);
        setting_set('theme_mode',     in_array($themeMode, ['light','dark','auto'], true) ? $themeMode : 'light');

        // Logo: prioridades → borrar / nuevo / mantener actual
        if ($deleteLogo) {
            // Borramos archivo si está en /uploads
            if ($currentLogo) {
                $oldFs = $uploadDirFs . DIRECTORY_SEPARATOR . $currentLogo;
                if (is_file($oldFs)) { @unlink($oldFs); }
            }
            setting_set('shop_logo', '');
        } elseif ($newLogoName) {
            // Sustituye logo anterior
            if ($currentLogo) {
                $oldFs = $uploadDirFs . DIRECTORY_SEPARATOR . $currentLogo;
                if (is_file($oldFs)) { @unlink($oldFs); }
            }
            setting_set('shop_logo', $newLogoName);
        }

        flash_success('Configuración guardada correctamente.');
        header('Location: ' . $BASE . '/admin/configuracion.php');
        exit;
    } else {
        // Guardamos errores en flash
        foreach ($errors as $e) {
            flash_error($e);
        }
        header('Location: ' . $BASE . '/admin/configuracion.php');
        exit;
    }
}

/* =====================================
   Cargar valores actuales para el formulario
   ===================================== */
$shop   = setting_get('shop_name', 'Mi Tienda');
$slogan = setting_get('shop_slogan', '');
$wa     = setting_get('whatsapp_number', '');
$email  = setting_get('contact_email', '');

$address = setting_get('shop_address', '');
$city    = setting_get('shop_city', '');
$zip     = setting_get('shop_zip', '');
$footer  = setting_get('footer_text', '© ' . date('Y') . ' Mi Tienda. Todos los derechos reservados.');

$instagram = setting_get('social_instagram', '');
$facebook  = setting_get('social_facebook', '');
$tiktok    = setting_get('social_tiktok', '');

$primaryColor = setting_get('primary_color', '#0066FF');
$accentColor  = setting_get('accent_color', '#111827');
$themeMode    = setting_get('theme_mode', 'light');

$currentLogo  = setting_get('shop_logo', '');

include __DIR__ . '/../templates/header.php';
?>
<div class="py-4">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h4 mb-0">Configuración de la tienda</h1>
    <a class="btn btn-outline-secondary" href="<?= $BASE ?>/admin/index.php">Volver al panel</a>
  </div>

  <form method="post" class="card shadow-sm" enctype="multipart/form-data">
    <div class="card-body">
      <input type="hidden" name="csrf" value="<?= htmlspecialchars(auth_csrf()) ?>">

      <!-- ================== Datos generales ================== -->
      <h5 class="mb-3">Datos generales</h5>

      <div class="row g-3 mb-3">
        <div class="col-md-6">
          <label class="form-label">Nombre de la tienda *</label>
          <input type="text" name="shop_name" class="form-control" maxlength="120"
                 value="<?= htmlspecialchars($shop) ?>" required>
        </div>
        <div class="col-md-6">
          <label class="form-label">Slogan</label>
          <input type="text" name="shop_slogan" class="form-control" maxlength="160"
                 value="<?= htmlspecialchars($slogan) ?>" placeholder="Ej: Moda urbana todos los días">
        </div>
      </div>

      <div class="row g-3 mb-4">
        <div class="col-md-4">
          <label class="form-label">WhatsApp (E.164)</label>
          <input type="text" name="whatsapp_number" class="form-control"
                 placeholder="+34123456789"
                 value="<?= htmlspecialchars($wa) ?>">
          <div class="form-text">Formato recomendado internacional, ej: +34123456789</div>
        </div>
        <div class="col-md-4">
          <label class="form-label">Email de contacto</label>
          <input type="email" name="contact_email" class="form-control"
                 value="<?= htmlspecialchars($email) ?>">
        </div>
      </div>

      <hr class="my-4">

      <!-- ================== Branding (Logo & colores) ================== -->
      <h5 class="mb-3">Branding y estilo</h5>

      <div class="row g-3 mb-3">
        <div class="col-md-6">
          <label class="form-label d-flex justify-content-between align-items-center">
            <span>Logo de la tienda</span>
            <small class="text-muted">Opcional • JPG/PNG/WebP/SVG • máx 2MB</small>
          </label>
          <input type="file" name="shop_logo_file" class="form-control"
                 accept=".jpg,.jpeg,.png,.webp,.svg">

          <?php if ($currentLogo): ?>
            <div class="mt-2 d-flex align-items-center gap-3">
              <div>
                <span class="d-block small text-muted mb-1">Logo actual:</span>
                <img src="<?= htmlspecialchars($BASE . '/uploads/' . $currentLogo) ?>"
                     alt="Logo actual" style="max-width:96px;max-height:96px;object-fit:contain;border-radius:8px;border:1px solid #e9ecef;">
              </div>
              <div class="form-check mt-3">
                <input class="form-check-input" type="checkbox" name="delete_logo" id="delete_logo">
                <label class="form-check-label" for="delete_logo">Eliminar logo y usar solo texto</label>
              </div>
            </div>
          <?php else: ?>
            <div class="form-text">Si no subes logo, se mostrará solo el nombre de la tienda.</div>
          <?php endif; ?>
        </div>

        <div class="col-md-3">
          <label class="form-label">Color primario (botones, acentos)</label>
          <div class="d-flex align-items-center gap-2">
            <input type="color" name="primary_color" class="form-control form-control-color"
                   value="<?= htmlspecialchars($primaryColor) ?>" title="Color primario">
            <input type="text" class="form-control" name="primary_color_text"
                   value="<?= htmlspecialchars($primaryColor) ?>"
                   oninput="this.previousElementSibling.value=this.value">
          </div>
        </div>

        <div class="col-md-3">
          <label class="form-label">Color secundario / texto fuerte</label>
          <div class="d-flex align-items-center gap-2">
            <input type="color" name="accent_color" class="form-control form-control-color"
                   value="<?= htmlspecialchars($accentColor) ?>" title="Color secundario">
            <input type="text" class="form-control" name="accent_color_text"
                   value="<?= htmlspecialchars($accentColor) ?>"
                   oninput="this.previousElementSibling.value=this.value">
          </div>
        </div>
      </div>

      <div class="row g-3 mb-4">
        <div class="col-md-4">
          <label class="form-label">Tema</label>
          <select name="theme_mode" class="form-select">
            <option value="light" <?= $themeMode === 'light' ? 'selected' : '' ?>>Claro</option>
            <option value="dark"  <?= $themeMode === 'dark'  ? 'selected' : '' ?>>Oscuro</option>
            <option value="auto"  <?= $themeMode === 'auto'  ? 'selected' : '' ?>>Automático (sistema)</option>
          </select>
          <div class="form-text">Luego podemos hacer que se aplique a toda la web (modo claro/oscuro).</div>
        </div>
      </div>

      <hr class="my-4">

      <!-- ================== Dirección y contacto visible ================== -->
      <h5 class="mb-3">Datos de contacto visibles</h5>

      <div class="mb-3">
        <label class="form-label">Dirección (línea principal)</label>
        <input type="text" name="shop_address" class="form-control"
               placeholder="Calle, número, piso..." value="<?= htmlspecialchars($address) ?>">
      </div>

      <div class="row g-3 mb-3">
        <div class="col-md-6">
          <label class="form-label">Ciudad</label>
          <input type="text" name="shop_city" class="form-control"
                 value="<?= htmlspecialchars($city) ?>">
        </div>
        <div class="col-md-3">
          <label class="form-label">Código postal</label>
          <input type="text" name="shop_zip" class="form-control"
                 value="<?= htmlspecialchars($zip) ?>">
        </div>
      </div>

      <hr class="my-4">

      <!-- ================== Redes sociales ================== -->
      <h5 class="mb-3">Redes sociales</h5>
      <div class="row g-3 mb-3">
        <div class="col-md-4">
          <label class="form-label">Instagram</label>
          <input type="url" name="social_instagram" class="form-control"
                 placeholder="https://instagram.com/tu_tienda"
                 value="<?= htmlspecialchars($instagram) ?>">
        </div>
        <div class="col-md-4">
          <label class="form-label">Facebook</label>
          <input type="url" name="social_facebook" class="form-control"
                 placeholder="https://facebook.com/tu_tienda"
                 value="<?= htmlspecialchars($facebook) ?>">
        </div>
        <div class="col-md-4">
          <label class="form-label">TikTok</label>
          <input type="url" name="social_tiktok" class="form-control"
                 placeholder="https://www.tiktok.com/@tu_tienda"
                 value="<?= htmlspecialchars($tiktok) ?>">
        </div>
      </div>

      <hr class="my-4">

      <!-- ================== Pie de página ================== -->
      <h5 class="mb-3">Pie de página</h5>
      <div class="mb-3">
        <label class="form-label">Texto legal / firma</label>
        <textarea name="footer_text" rows="3" class="form-control"
                  placeholder="© <?= date('Y') ?> Mi Tienda. Todos los derechos reservados."><?= htmlspecialchars($footer) ?></textarea>
        <div class="form-text">
          Este texto puede mostrarse en el footer (copyright, nombre de la tienda, etc.).
        </div>
      </div>

      <div class="mt-4 d-flex justify-content-end gap-2">
        <a class="btn btn-outline-secondary" href="<?= $BASE ?>/admin/index.php">Cancelar</a>
        <button class="btn btn-primary" type="submit">Guardar configuración</button>
      </div>
    </div>
  </form>
</div>
<?php include __DIR__ . '/../templates/footer.php'; ?>
