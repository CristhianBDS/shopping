<?php
// public/404.php — Página de error 404 (no encontrado)
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/bootstrap.php';

$CONTEXT = 'public';
$PAGE_TITLE = 'Página no encontrada';

http_response_code(404);

include __DIR__ . '/../templates/header.php';
?>
<section class="py-5 text-center">
  <div class="container">
    <h1 class="display-4 mb-3">404</h1>
    <p class="lead text-muted mb-4">La página que buscas no existe o fue movida.</p>
    <a href="<?= BASE_URL ?>/public/index.php" class="btn btn-primary">Volver al inicio</a>
    <a href="<?= BASE_URL ?>/public/catalogo.php" class="btn btn-outline-secondary">Ir al catálogo</a>
  </div>
</section>
<?php include __DIR__ . '/../templates/footer.php'; ?>
