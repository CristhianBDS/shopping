<?php
// public/catalogo.php
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/bootstrap.php';

$CONTEXT = 'public';
$PAGE_TITLE = 'Catálogo';

$BASE = defined('BASE_URL') ? BASE_URL : '/shopping';

include __DIR__ . '/../templates/header.php';
?>
<div class="page page--catalogo">
  <header class="catalogo-header">
    <h1>Catálogo</h1>
    <div class="toolbar">
      <input id="q" class="search" type="search" placeholder="Buscar producto..." />
      <span id="count" class="badge">0 items</span>
      <a id="cart-link" class="badge" href="<?= $BASE ?>/public/carrito.php">🛒 <span id="cart-count">0</span></a>
    </div>
  </header>

  <section id="grid" class="catalogo-grid" aria-live="polite"></section>
  <div id="empty" class="empty hidden">No hay productos para mostrar.</div>
</div>

<script>
(function () {
  const BASE = <?= json_encode($BASE) ?>;
  const API_URL = BASE + "/api/products.php?active=1";
  const IMG_UPLOADS = BASE + "/uploads/";
  const IMG_IMAGES  = BASE + "/images/";
  const PLACEHOLDER = IMG_IMAGES + "placeholder.jpg";

  const grid = document.getElementById("grid");
  const empty = document.getElementById("empty");
  const count = document.getElementById("count");
  const q = document.getElementById("q");

  let all = [];
  let filtered = [];

  function fmtPrice(n) {
    try { return new Intl.NumberFormat('es-ES', { style:'currency', currency:'EUR' }).format(n); }
    catch (_) { return "€ " + Number(n).toFixed(2); }
  }

  function setEmpty(show, msg) {
    empty.classList.toggle('hidden', !show);
    if (msg) empty.textContent = msg;
  }

  function makeImg(fname, altText){
    const img = document.createElement('img');
    img.alt = altText || '';
    img.dataset.fname = (fname || '').trim();
    if (!img.dataset.fname) { img.src = PLACEHOLDER; return img; }
    // 1) uploads → 2) images → 3) placeholder
    img.src = IMG_UPLOADS + img.dataset.fname;
    img.onerror = function onFirst() {
      img.onerror = function onSecond() {
        img.onerror = null;
        img.src = PLACEHOLDER;
      };
      img.src = IMG_IMAGES + img.dataset.fname;
    };
    return img;
  }

  function render(items) {
    grid.innerHTML = "";
    if (!items || items.length === 0) {
      setEmpty(true, "No hay productos para mostrar.");
      count.textContent = "0 items";
      return;
    }
    setEmpty(false);
    count.textContent = items.length + (items.length === 1 ? " item" : " items");

    const frag = document.createDocumentFragment();

    items.forEach(p => {
      const card = document.createElement("article");
      card.className = "card";
      const href = `${BASE}/public/producto.php?id=${p.id}`;

      const cover = document.createElement('a');
      cover.className = 'cover';
      cover.href = href;
      cover.setAttribute('aria-label', `Ver ${p.name || 'producto'}`);

      const img = makeImg(p.image, p.name || '');
      cover.appendChild(img);

      card.appendChild(cover);

      const name = document.createElement('div');
      name.className = 'name';
      name.title = p.name || '';
      name.innerHTML = `<a href="${href}">${p.name || ''}</a>`;
      card.appendChild(name);

      const desc = document.createElement('div');
      desc.className = 'muted';
      desc.textContent = p.description ? p.description : '';
      card.appendChild(desc);

      const price = document.createElement('div');
      price.className = 'price';
      price.textContent = fmtPrice(p.price || 0);
      card.appendChild(price);

      frag.appendChild(card);
    });

    grid.appendChild(frag);
  }

  function applyFilter() {
    const term = (q.value || "").toLowerCase().trim();
    if (!term) { filtered = all.slice(); render(filtered); return; }
    filtered = all.filter(p =>
      (p.name || "").toLowerCase().includes(term) ||
      (p.description || "").toLowerCase().includes(term)
    );
    render(filtered);
  }

  async function load() {
    try {
      const res = await fetch(API_URL);
      const json = await res.json();

      let rows = Array.isArray(json?.data) ? json.data : [];
      rows = rows.map(p => ({
        ...p,
        is_active: p.hasOwnProperty('is_active') ? p.is_active : (p.hasOwnProperty('active') ? p.active : 1)
      }));
      all = rows.filter(p => String(p.is_active) === "1" || p.is_active === 1 || p.is_active === true);

      applyFilter();
    } catch (err) {
      console.error(err);
      setEmpty(true, "Error cargando productos.");
    }
  }

  q.addEventListener("input", applyFilter);
  load();
  // el contador del carrito lo gestiona js/cart.js
})();
</script>

<?php include __DIR__ . '/../templates/footer.php'; ?>
