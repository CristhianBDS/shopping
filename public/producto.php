<?php
// public/producto.php — Detalle de producto público
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/bootstrap.php';

$CONTEXT = 'public';

$pdo  = getConnection();
$BASE = defined('BASE_URL') ? BASE_URL : '/shopping';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
  http_response_code(400);
  die('Producto no válido');
}

// Traer producto activo
$stmt = $pdo->prepare("SELECT id, name, description, price, image, is_active FROM products WHERE id = ? AND is_active = 1");
$stmt->execute([$id]);
$p = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$p) {
  http_response_code(404);
  die('Producto no encontrado');
}

$PAGE_TITLE = $p['name'] ?? 'Producto';

// Helper imagen con fallback
function image_url($fname) {
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
<div class="row g-4">
  <div class="col-md-6">
    <div class="card border-0 shadow-sm">
      <img id="prod-img" class="img-fluid rounded" src="<?= htmlspecialchars(image_url($p['image'])) ?>" alt="<?= htmlspecialchars($p['name'] ?? 'Producto') ?>">
    </div>
  </div>
  <div class="col-md-6">
    <h1 class="h3 mb-2"><?= htmlspecialchars($p['name']) ?></h1>
    <div class="text-muted mb-3"><?= nl2br(htmlspecialchars($p['description'] ?? '')) ?></div>
    <div class="h4 mb-4">€ <?= number_format((float)$p['price'], 2, ',', '.') ?></div>

    <div class="d-flex gap-2 align-items-center">
      <label class="form-label m-0">Cantidad</label>
      <input id="qty" type="number" min="1" value="1" class="form-control" style="width: 110px;">
      <button id="addCart" class="btn btn-primary">Añadir al carrito</button>
      <a class="btn btn-outline-secondary" href="<?= $BASE ?>/public/carrito.php">Ir al carrito</a>
    </div>

    <div class="mt-3">
      <a class="btn btn-link p-0" href="<?= $BASE ?>/public/catalogo.php">← Volver al catálogo</a>
    </div>
  </div>
</div>

<script>
(function(){
  const BASE = <?= json_encode($BASE) ?>;
  const product = {
    id: <?= (int)$p['id'] ?>,
    name: <?= json_encode($p['name']) ?>,
    price: <?= json_encode((float)$p['price']) ?>,
    image: <?= json_encode($p['image']) ?>
  };

  function getCart(){ try { return JSON.parse(localStorage.getItem('cart') || '[]'); } catch { return []; } }
  function setCart(c){ localStorage.setItem('cart', JSON.stringify(c)); }
  function addToCart(prod, qty){
    qty = Math.max(1, parseInt(qty||1,10));
    const cart = getCart();
    const idx = cart.findIndex(i => String(i.id) === String(prod.id));
    if (idx >= 0) {
      cart[idx].qty = (parseInt(cart[idx].qty||0,10) + qty);
    } else {
      cart.push({ id: prod.id, name: prod.name, price: prod.price, qty, image: prod.image });
    }
    setCart(cart);
  }

  document.getElementById('addCart').addEventListener('click', function(){
    const q = document.getElementById('qty').value;
    addToCart(product, q);
    alert('Producto añadido al carrito');
  });
})();
</script>

<?php include __DIR__ . '/../templates/footer.php'; ?>
