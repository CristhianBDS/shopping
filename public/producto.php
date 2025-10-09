<?php
// public/producto.php — Ficha de producto
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/../config/db.php';

$CONTEXT = 'public';
$PAGE_TITLE = 'Producto';
$BASE = defined('BASE_URL') ? BASE_URL : '/shopping';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
  http_response_code(404);
  include __DIR__ . '/404.php';
  exit;
}

$pdo = getConnection();
$stmt = $pdo->prepare("SELECT id, name, description, price, image, is_active FROM products WHERE id = ? LIMIT 1");
$stmt->execute([$id]);
$prod = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$prod || (int)$prod['is_active'] !== 1) {
  http_response_code(404);
  include __DIR__ . '/404.php';
  exit;
}

// Título de la página = nombre del producto
$PAGE_TITLE = $prod['name'] ?: 'Producto';

function product_image_url(array $row): string {
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
<div class="row g-4">
  <div class="col-md-6">
    <div class="card shadow-sm">
      <img class="card-img-top" src="<?= product_image_url($prod) ?>" alt="<?= htmlspecialchars($prod['name']) ?>">
    </div>
  </div>
  <div class="col-md-6">
    <h1 class="h3 mb-2"><?= htmlspecialchars($prod['name']) ?></h1>
    <div class="h4 mb-3">€ <?= number_format((float)$prod['price'], 2, ',', '.') ?></div>

    <?php if (!empty($prod['description'])): ?>
      <p class="text-muted"><?= nl2br(htmlspecialchars($prod['description'])) ?></p>
    <?php endif; ?>

    <div class="d-flex gap-2 mt-3">
      <a class="btn btn-primary"
         href="<?= $BASE ?>/public/carrito.php"
         onclick="addToCart(<?= (int)$prod['id'] ?>,'<?= htmlspecialchars($prod['name'], ENT_QUOTES) ?>',<?= (float)$prod['price'] ?>,'<?= htmlspecialchars((string)$prod['image'], ENT_QUOTES) ?>'); return true;">
        Añadir al carrito
      </a>
      <a class="btn btn-outline-secondary" href="<?= $BASE ?>/public/catalogo.php">Volver al catálogo</a>
    </div>
  </div>
</div>

<script>
  function addToCart(id, name, price, image){
    const item = { id, name, price, image, qty: 1 };
    try {
      const cart = JSON.parse(localStorage.getItem('cart') || '[]');
      const idx = cart.findIndex(p => String(p.id) === String(id));
      if (idx >= 0) cart[idx].qty = Math.min(99, (Number(cart[idx].qty)||0) + 1);
      else cart.push(item);
      localStorage.setItem('cart', JSON.stringify(cart));
      alert('Producto añadido al carrito');
    } catch(e) {
      alert('No se pudo añadir al carrito');
    }
  }
</script>

<?php include __DIR__ . '/../templates/footer.php'; ?>
