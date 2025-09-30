<?php
require_once __DIR__ . '/../config/app.php';
$BASE = defined('BASE_URL') ? BASE_URL : '/shopping';
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) { http_response_code(400); echo "<p>Producto inválido.</p>"; exit; }

include __DIR__ . '/../templates/header.php';
?>
<a class="cart-fab" href="<?= $BASE ?>/public/carrito.php">🛒 <span id="cart-count">0</span></a>
<section id="view"></section>

<script>
(function(){
  const BASE = <?= json_encode($BASE) ?>;
  const ID = <?= (int)$id ?>;
  const IMG_UPLOADS = BASE + "/uploads/";
  const IMG_IMAGES  = BASE + "/images/";
  const PLACEHOLDER = IMG_IMAGES + "placeholder.jpg";
  const API_URL = BASE + "/api/products.php?id=" + ID;

  const $view = document.getElementById("view");

  function money(n){
    try { return new Intl.NumberFormat('es-ES',{style:'currency',currency:'EUR'}).format(n); }
    catch(_) { return "€ " + Number(n).toFixed(2); }
  }

  // Mini carrito
  function getCart(){ try { return JSON.parse(localStorage.getItem("cart") || "[]"); } catch { return []; } }
  function setCart(c){ localStorage.setItem("cart", JSON.stringify(c)); refreshCartBadge(); }
  function cartCount(){ return getCart().reduce((a,i)=>a+(Number(i.qty)||0),0); }
  function refreshCartBadge(){ const c = document.getElementById("cart-count"); if (c) c.textContent = String(cartCount()); }
  window.addEventListener("storage", (ev) => { if (ev.key === "cart") refreshCartBadge(); });

  function render(p){
    if (!p) { $view.innerHTML = "<p>No se encontró el producto.</p>"; return; }

    const firstSrc = (p.image && String(p.image).trim())
      ? (IMG_UPLOADS + p.image)
      : PLACEHOLDER;
    const secondSrc = IMG_IMAGES + (p.image || '');

    $view.innerHTML = `
      <div class="page">
        <a class="back" href="${BASE}/public/catalogo.php">← Volver al catálogo</a>
        <div class="product-wrap">
          <div>
            <img id="prod-img" src="${firstSrc}" alt="${p.name || ''}">
          </div>
          <div>
            <h1 class="product-title">${p.name || ''}</h1>
            <div class="product-price">${money(p.price || 0)}</div>
            <p class="muted">${p.description || ''}</p>
            <div class="row">
              <label for="qty">Cantidad</label>
              <input id="qty" class="qty" type="number" min="1" max="99" value="1" />
            </div>
            <div class="row">
              <button id="add" class="btn">🛒 Añadir al carrito</button>
              <button id="favorite" class="btn secondary">☆ Favorito</button>
            </div>
          </div>
        </div>
      </div>
    `;

    const img = document.getElementById('prod-img');
    let stage = 0; // 0: uploads → 1: images → 2: placeholder
    img.onerror = function(){
      if (stage === 0) { img.src = secondSrc; stage = 1; }
      else if (stage === 1) { img.src = PLACEHOLDER; stage = 2; }
      else { img.onerror = null; }
    };

    const qty = document.getElementById("qty");
    document.getElementById("add").addEventListener("click", () => addToCart(p, Number(qty.value || 1)));
    document.getElementById("favorite").addEventListener("click", () => toggleFav(p));
  }

  function addToCart(p, q){
    const cart = getCart();
    const idx = cart.findIndex(i => i.id === p.id);
    if (idx >= 0) cart[idx].qty += q; else cart.push({ id:p.id, name:p.name, price:p.price, image:p.image, qty:q });
    setCart(cart);
    alert("Agregado al carrito ✔");
    refreshCartBadge();
  }

  function toggleFav(p){
    const key = "favorites";
    let fav = JSON.parse(localStorage.getItem(key) || "[]");
    if (fav.includes(p.id)) { fav = fav.filter(id => id !== p.id); alert("Quitado de favoritos"); }
    else { fav.push(p.id); alert("Añadido a favoritos"); }
    localStorage.setItem(key, JSON.stringify(fav));
  }

  (async function load(){
    try{
      const r = await fetch(API_URL);
      const j = await r.json();
      render(j?.data);
      refreshCartBadge();
    }catch(e){
      console.error(e);
      $view.innerHTML = "<p>Error cargando el producto.</p>";
    }
  })();
})();
</script>
<?php include __DIR__ . '/../templates/footer.php'; ?>
