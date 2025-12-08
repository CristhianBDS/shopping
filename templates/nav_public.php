<?php
if (session_status() === PHP_SESSION_NONE) session_start();

require_once __DIR__ . '/../inc/auth.php';

// Cargar settings para nombre y logo de la tienda
$settingsPath = __DIR__ . '/../inc/settings.php';
$SHOP_NAME = 'Mi Tienda';
$shopLogo  = '';
$shopSlogan = '';

if (file_exists($settingsPath)) {
    require_once $settingsPath;
    // Nombre de la tienda
    $SHOP_NAME  = setting_get('shop_name', 'Mi Tienda');
    // Logo (puede ser URL absoluta, ruta /images/... o solo nombre de archivo)
    $shopLogo   = setting_get('shop_logo', '');
    // Opcional: slogan si lo añadimos más adelante en configuración
    $shopSlogan = setting_get('shop_slogan', '');
}

$BASE = defined('BASE_URL') ? BASE_URL : '/shopping';
$path = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?: '';

$active = function(string $file) use ($path): string {
  return str_ends_with($path, "/public/$file") || str_ends_with($path, "/$file") ? ' active' : '';
};

$user     = auth_user();
$isUser   = $user !== null;
$isAdmin  = isAdmin();
$isMember = !$isAdmin && isMember(); // member verdadero sólo si NO es admin
$userName = $user['name'] ?? $user['username'] ?? 'Cliente';

$csrf = htmlspecialchars(auth_csrf() ?? '', ENT_QUOTES, 'UTF-8');

// Helper para resolver la URL del logo
function shop_logo_url(string $value, string $base): ?string {
    $value = trim($value);
    if ($value === '') return null;

    // Si ya es URL absoluta o ruta absoluta, la devolvemos tal cual
    if (str_starts_with($value, 'http://') || str_starts_with($value, 'https://') || str_starts_with($value, '/')) {
        return $value;
    }

    // Si es solo un nombre de archivo, lo buscamos en /uploads o /images
    $base = rtrim($base, '/');
    $uploadPathFs = __DIR__ . '/../uploads/' . $value;
    $imagesPathFs = __DIR__ . '/../images/' . $value;

    if (is_file($uploadPathFs)) {
        return $base . '/uploads/' . $value;
    }
    if (is_file($imagesPathFs)) {
        return $base . '/images/' . $value;
    }

    // Fallback: asumimos que el valor es relativo a /images
    return $base . '/images/' . $value;
}

$logoUrl = $shopLogo ? shop_logo_url($shopLogo, $BASE) : null;
?>
<nav class="navbar navbar-expand-lg navbar-light bg-light border-bottom fixed-top" id="mainNav">
  <div class="container">
    <a class="navbar-brand fw-bold d-flex align-items-center gap-2" href="<?= $BASE ?>/public/index.php">
      <?php if ($logoUrl): ?>
        <img src="<?= htmlspecialchars($logoUrl) ?>"
             alt="<?= htmlspecialchars($SHOP_NAME) ?>"
             width="32" height="32"
             style="object-fit:contain;border-radius:8px;">
      <?php endif; ?>
      <span><?= htmlspecialchars($SHOP_NAME) ?></span>
      <?php if ($shopSlogan): ?>
        <span class="d-none d-md-inline text-muted small ms-1"><?= htmlspecialchars($shopSlogan) ?></span>
      <?php endif; ?>
    </a>

    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navPublic"
            aria-controls="navPublic" aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>

    <div class="collapse navbar-collapse" id="navPublic">
      <ul class="navbar-nav me-auto mb-2 mb-lg-0">
        <li class="nav-item">
          <a class="nav-link<?= $active('index.php') ?>" href="<?= $BASE ?>/public/index.php">Inicio</a>
        </li>
        <li class="nav-item">
          <a class="nav-link<?= $active('catalogo.php') ?>" href="<?= $BASE ?>/public/catalogo.php">Catálogo</a>
        </li>

        <?php if ($isUser && $isMember): ?>
          <li class="nav-item">
            <a class="nav-link<?= $active('beneficios.php') ?>" href="<?= $BASE ?>/public/beneficios.php">
              Beneficios de socio
            </a>
          </li>
        <?php else: ?>
          <li class="nav-item">
            <a class="nav-link<?= $active('contacto.php') ?>" href="<?= $BASE ?>/public/contacto.php">Contacto</a>
          </li>
        <?php endif; ?>

        <?php if ($isAdmin): ?>
          <li class="nav-item">
            <a class="nav-link" href="<?= $BASE ?>/admin/">Admin</a>
          </li>
        <?php endif; ?>
      </ul>

      <a class="btn btn-outline-primary btn-account" href="<?= $BASE ?>/public/carrito.php" title="Carrito">
      🛒 <span class="d-none d-sm-inline">Carrito</span>
      </a>


        <?php if ($isUser): ?>
          <div class="dropdown">
            <button class="btn btn-outline-primary dropdown-toggle btn-account" type="button"
                    data-bs-toggle="dropdown" aria-expanded="false" title="Cuenta">
              <span class="icon-account">👤</span>
              <span class="d-none d-sm-inline"><?= htmlspecialchars($userName) ?></span>
            </button>
            <ul class="dropdown-menu dropdown-menu-end">
              <li><a class="dropdown-item" href="<?= $BASE ?>/public/cuenta.php">Mi cuenta</a></li>
              <li><a class="dropdown-item" href="<?= $BASE ?>/public/pedidos.php">Mis pedidos</a></li>
              <li><hr class="dropdown-divider"></li>

              <!-- a) Botón POST (nativo) -->
              <li>
                <form action="<?= $BASE ?>/public/logout.php" method="post" class="dropdown-logout-form">
                  <input type="hidden" name="csrf" value="<?= $csrf ?>">
                  <button type="submit" class="dropdown-item dropdown-item-danger">Salir</button>
                </form>
              </li>

              <!-- b) Link fallback (JS lo convierte en POST) -->
              <li>
                <a class="dropdown-item text-danger d-lg-none"
                   href="<?= $BASE ?>/public/logout.php"
                   data-logout="1">
                  Salir (fallback)
                </a>
              </li>
            </ul>
          </div>
        <?php else: ?>
          <a class="btn btn-outline-primary btn-account"
             href="<?= $BASE ?>/public/registro.php"
             title="Hazte socio">
            <span class="icon-account">👤</span>
            <span class="d-none d-sm-inline">Registrarse</span>
          </a>
        <?php endif; ?>
      </div>
    </div>
  </div>
</nav>
