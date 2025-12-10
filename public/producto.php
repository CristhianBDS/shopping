<?php
// public/producto.php — Ficha con rating visual + stock
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../inc/auth.php';

$CONTEXT = 'public';
$BASE = defined('BASE_URL') ? BASE_URL : '/shopping';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) { http_response_code(404); include __DIR__ . '/404.php'; exit; }

$pdo = getConnection();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$stmt = $pdo->prepare("SELECT id, name, description, price, image, is_active, stock FROM products WHERE id = ? LIMIT 1");
$stmt->execute([$id]);
$prod = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$prod || (int)$prod['is_active'] !== 1) { http_response_code(404); include __DIR__ . '/404.php'; exit; }

$PAGE_TITLE = $prod['name'] ?: 'Producto';
$user = auth_user();

/* ---------- Helpers imágenes ---------- */
// Ojo: usamos prod_base_url() para no chocar con base_url() de header.php
function prod_base_url(): string {
  return rtrim(defined('BASE_URL') ? BASE_URL : '/shopping', '/');
}

function image_url_if_exists(string $fname): ?string {
  $fname = trim($fname);
  if ($fname === '') return null;

  $base = prod_base_url();

  $candidatos = [
    [__DIR__ . "/../uploads/$fname", "$base/uploads/$fname"],
    [__DIR__ . "/../images/$fname",  "$base/images/$fname"],
  ];

  foreach ($candidatos as [$fs, $url]) {
    if (is_file($fs)) return $url;
  }
  return null;
}

function product_image_url(array $row): string {
  $url = image_url_if_exists((string)($row['image'] ?? ''));
  return $url ?: (prod_base_url() . '/images/placeholder.jpg');
}

/** Galería: DB + variantes + relleno min. 3 */
function product_images(PDO $pdo, array $prod): array {
  $MIN = 3;
  $images = [];

  try {
    $q = $pdo->prepare("SELECT filename FROM product_images WHERE product_id=? ORDER BY sort_order,id");
    $q->execute([(int)$prod['id']]);
    foreach ($q->fetchAll(PDO::FETCH_COLUMN) as $fn) {
      if ($u = image_url_if_exists($fn)) $images[] = $u;
    }
  } catch (Throwable $e) {
    // ignoramos si no existe la tabla
  }

  $main = trim((string)($prod['image'] ?? ''));
  if ($main !== '') {
    if (!$images && ($u = image_url_if_exists($main))) {
      $images[] = $u;
    }
    if (count($images) <= 1) {
      $pi   = pathinfo($main);
      $name = $pi['filename'] ?? '';
      $ext  = isset($pi['extension']) ? ('.' . $pi['extension']) : '';
      $set  = array_flip($images);

      for ($i = 2; $i <= 6; $i++) {
        foreach (["{$name}-{$i}{$ext}", "{$name}_{$i}{$ext}"] as $cand) {
          if ($u = image_url_if_exists($cand)) {
            if (!isset($set[$u])) {
              $images[] = $u;
              $set[$u]  = true;
            }
          }
        }
      }
    }
  }

  if (!$images) {
    $images[] = prod_base_url() . '/images/placeholder.jpg';
  }

  while (count($images) < $MIN) {
    $images[] = $images[0];
  }

  return array_slice($images, 0, 10);
}

/* ---------- Valoraciones (solo visual) ---------- */
function product_rating_summary(PDO $pdo, int $pid): array {
  try {
    $st = $pdo->prepare("SELECT COUNT(*) cnt, AVG(rating) avg_rating FROM product_reviews WHERE product_id=? AND is_approved=1");
    $st->execute([$pid]);
    $r = $st->fetch(PDO::FETCH_ASSOC);
    return [
      'count' => (int)($r['cnt'] ?? 0),
      'avg'   => $r['avg_rating'] ? round((float)$r['avg_rating'], 1) : 0.0
    ];
  } catch (Throwable $e) {
    return ['count' => 0, 'avg' => 0.0];
  }
}

/* ---------- Stock (usa products.stock) ---------- */
function product_stock_summary(int $total): array {
  // $total = stock actual del producto (columna products.stock)
  if ($total <= 0)  return ['label' => 'Agotado',          'variant' => 'danger'];
  if ($total <= 10) return ['label' => 'Últimas unidades', 'variant' => 'warning'];
  return ['label' => 'En stock', 'variant' => 'success'];
}


function related_products(PDO $pdo, int $excludeId, int $limit = 8): array {
  $st = $pdo->prepare("
    SELECT id, name, price, image
    FROM products
    WHERE is_active = 1 AND id <> ?
    ORDER BY RAND()
    LIMIT " . (int)$limit
  );
  $st->execute([$excludeId]);
  return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
}
$gallery = product_images($pdo, $prod);
$summary = product_rating_summary($pdo, (int)$prod['id']);
$stock   = product_stock_summary((int)($prod['stock'] ?? 0));
$related = related_products($pdo, (int)$prod['id'], 8);


include __DIR__ . '/../templates/header.php';
?>

<section class="product-view py-5">
  <div class="container">
    <div class="product-card border border-primary-subtle rounded-4 p-4 shadow-sm">
      <div class="row g-4 align-items-start product-detail">
        <!-- Galería -->
        <div class="col-md-6">
          <div id="prodGallery" class="carousel slide product-gallery border border-2 border-primary-subtle rounded-4" data-bs-ride="false">
            <div class="carousel-inner">
              <?php foreach ($gallery as $idx => $url): ?>
                <div class="carousel-item<?= $idx === 0 ? ' active' : '' ?>">
                  <div class="product-media">
                    <img src="<?= htmlspecialchars($url) ?>" class="d-block w-100 product-img" alt="<?= htmlspecialchars($prod['name']) ?>" loading="lazy">
                  </div>
                </div>
              <?php endforeach; ?>
            </div>
            <?php if (count($gallery) > 1): ?>
              <button class="carousel-control-prev" type="button" data-bs-target="#prodGallery" data-bs-slide="prev" aria-label="Anterior">
                <span class="carousel-control-prev-icon"></span>
              </button>
              <button class="carousel-control-next" type="button" data-bs-target="#prodGallery" data-bs-slide="next" aria-label="Siguiente">
                <span class="carousel-control-next-icon"></span>
              </button>
            <?php endif; ?>
          </div>

          <?php if (count($gallery) > 1): ?>
          <div class="product-thumbs mt-3">
            <div class="row g-2">
              <?php foreach ($gallery as $i => $url): ?>
                <div class="col-3 col-sm-2">
                  <button type="button" class="thumb <?= $i === 0 ? 'active' : '' ?> border border-primary-subtle" data-bs-target="#prodGallery" data-bs-slide-to="<?= $i ?>">
                    <img src="<?= htmlspecialchars($url) ?>" alt="Vista <?= $i + 1 ?>" loading="lazy">
                  </button>
                </div>
              <?php endforeach; ?>
            </div>
          </div>
          <?php endif; ?>
        </div>

        <!-- Columna info -->
        <div class="col-md-6">
          <h1 class="h3 mb-1"><?= htmlspecialchars($prod['name']) ?></h1>

          <div class="h4 mb-2 text-primary fw-bold">€ <?= number_format((float)$prod['price'], 2, ',', '.') ?></div>

          <!-- Rating debajo del precio -->
          <div class="d-flex align-items-center gap-2 mb-2">
            <?php
              $full  = floor($summary['avg']);
              $half  = ($summary['avg'] - $full) >= 0.5 ? 1 : 0;
              $empty = 5 - $full - $half;
            ?>
            <div class="rating-stars" aria-label="Valoración media: <?= $summary['avg'] ?> de 5">
              <?php for ($i = 0; $i < $full; $i++): ?><span class="star star-full">★</span><?php endfor; ?>
              <?php if ($half): ?><span class="star star-half">★</span><?php endif; ?>
              <?php for ($i = 0; $i < $empty; $i++): ?><span class="star star-empty">☆</span><?php endfor; ?>
            </div>
            <small class="text-muted">(<?= $summary['avg'] ?> · <?= (int)$summary['count'] ?> reseñas)</small>
          </div>

          <!-- Stock / disponibilidad -->
          <div class="mb-3">
            <span class="badge text-bg-<?= $stock['variant'] ?> px-3 py-2">
              <?= htmlspecialchars($stock['label']) ?>
            </span>
          </div>

          <?php if (!empty($prod['description'])): ?>
            <p class="text-muted mb-3"><?= nl2br(htmlspecialchars($prod['description'])) ?></p>
          <?php endif; ?>

          <!-- Acciones -->
          <div class="d-flex flex-wrap align-items-center gap-2 mb-3">
            <a class="btn btn-primary"
               href="<?= $BASE ?>/public/carrito.php"
               onclick="addToCart(<?= (int)$prod['id'] ?>,'<?= htmlspecialchars($prod['name'], ENT_QUOTES) ?>',<?= (float)$prod['price'] ?>,'<?= htmlspecialchars((string)$prod['image'], ENT_QUOTES) ?>'); return true;">
              Añadir al carrito
            </a>
            <a class="btn btn-outline-secondary" href="<?= $BASE ?>/public/catalogo.php">Volver al catálogo</a>

            <!-- Favoritos -->
            <button id="favBtn" class="btn btn-outline-primary ms-lg-2" type="button" aria-pressed="false">
              <span class="fav-icon">☆</span> Añadir a favoritos
            </button>

            <!-- Compartir: solo copiar enlace -->
            <button id="share-copy" class="btn btn-outline-secondary" type="button">Copiar enlace</button>
          </div>

          <!-- Detalles -->
          <div class="card border border-2 border-primary-subtle rounded-4">
            <div class="card-body">
              <h5 class="card-title mb-2">Detalles del producto</h5>
              <p class="mb-0 text-muted">
                Lorem ipsum dolor sit amet, consectetur adipiscing elit. Praesent euismod, nulla a pretium cursus,
                est enim ultricies nibh, id convallis lorem mi a lorem. Integer aliquet, massa vel faucibus pulvinar,
                tortor nisl cursus leo, non tempor erat mi nec nisl. Etiam gravida dui id tincidunt fermentum.
              </p>
            </div>
          </div>
        </div>
      </div> <!-- /.row -->
    </div> <!-- /.product-card -->
  </div>
</section>

<?php if (!empty($related)): ?>
<hr class="my-5">
<section aria-labelledby="related-title" class="related-products">
  <div class="container">
    <div class="d-flex align-items-baseline justify-content-between mb-3">
      <h2 id="related-title" class="h4 m-0">También te podría interesar</h2>
      <a class="btn btn-sm btn-outline-secondary" href="<?= $BASE ?>/public/catalogo.php">Ver todo</a>
    </div>
    <div class="row g-3">
      <?php foreach ($related as $r): $img = product_image_url($r); ?>
        <div class="col-6 col-sm-4 col-lg-3">
          <a href="<?= $BASE ?>/public/producto.php?id=<?= (int)$r['id'] ?>" class="card h-100 related-card text-decoration-none">
            <div class="product-media product-media--sm border border-primary-subtle rounded-3">
              <img src="<?= htmlspecialchars($img) ?>" alt="<?= htmlspecialchars($r['name']) ?>" class="related-img" loading="lazy">
            </div>
            <div class="card-body">
              <div class="related-name text-truncate"><?= htmlspecialchars($r['name']) ?></div>
              <div class="fw-semibold">€ <?= number_format((float)$r['price'], 2, ',', '.') ?></div>
            </div>
          </a>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<script>
  // Favoritos (localStorage)
  (function favInit(){
    const btn = document.getElementById('favBtn'); if(!btn) return;
    const key = 'favorites'; const pid = <?= (int)$prod['id'] ?>;
    const favs = new Set(JSON.parse(localStorage.getItem(key) || '[]').map(String));
    function render(){
      const active = favs.has(String(pid));
      btn.classList.toggle('btn-primary', active);
      btn.classList.toggle('btn-outline-primary', !active);
      btn.setAttribute('aria-pressed', active ? 'true' : 'false');
      btn.querySelector('.fav-icon').textContent = active ? '★' : '☆';
      btn.lastChild.nodeValue = (active ? ' Quitar de favoritos' : ' Añadir a favoritos');
    }
    render();
    btn.addEventListener('click', () => {
      if (favs.has(String(pid))) favs.delete(String(pid)); else favs.add(String(pid));
      localStorage.setItem(key, JSON.stringify(Array.from(favs))); render();
    });
  })();

  // Compartir: SOLO copiar enlace
  (function shareCopy(){
    const btn = document.getElementById('share-copy'); if(!btn) return;
    const url = window.location.href;
    btn.addEventListener('click', () => {
      if (navigator.clipboard?.writeText) {
        navigator.clipboard.writeText(url).then(()=>alert('Enlace copiado')).catch(()=>prompt('Copia el enlace:', url));
      } else { prompt('Copia el enlace:', url); }
    });
  })();

  // Miniatura activa
  document.getElementById('prodGallery')?.addEventListener('slid.bs.carousel', function (ev) {
    const idx = ev.to;
    document.querySelectorAll('.product-thumbs .thumb').forEach((b,i)=>b.classList.toggle('active', i===idx));
  });
</script>

<?php include __DIR__ . '/../templates/footer.php'; ?>
