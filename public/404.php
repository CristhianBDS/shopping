<?php
// public/404.php — Página de error 404 (no encontrado)
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/../inc/auth.php';

$CONTEXT    = 'public';
$PAGE_TITLE = 'Página no encontrada';
$BASE       = defined('BASE_URL') ? BASE_URL : '/shopping';

http_response_code(404);

include __DIR__ . '/../templates/header.php';
?>

<main class="page page-404 py-5 py-md-6">
  <section class="container text-center">
    <h1 class="display-4 mb-3">404</h1>
    <p class="lead text-muted mb-4">
      La página que buscas no existe, fue movida o la URL es incorrecta.
    </p>
    <div class="d-flex flex-column flex-sm-row justify-content-center gap-3">
      <a href="<?= htmlspecialchars($BASE) ?>/public/index.php" class="btn btn-primary">
        Volver al inicio
      </a>
      <a href="<?= htmlspecialchars($BASE) ?>/public/catalogo.php" class="btn btn-outline-secondary">
        Ir al catálogo
      </a>
    </div>
  </section>
</main>

<?php include __DIR__ . '/../templates/footer.php'; ?>
