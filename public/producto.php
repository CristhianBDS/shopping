<?php
require_once __DIR__ . '/../config/app.php';
$BASE = defined('BASE_URL') ? BASE_URL : '/shopping';
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) { http_response_code(400); echo "<p>Producto inválido.</p>"; exit; }
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Detalle del producto</title>
  <link rel="stylesheet" href="<?= $BASE ?>/assets/base.css">
  <link rel="stylesheet" href="<?= $BASE ?>/assets/tienda.css">
</head>
<body>
  <a class="cart-fab" href="<?= $BASE ?>/public/carrito.php">🛒 <span id="cart-count">0</span></a>
  <main class="page">
    <a class="back" href="<?= $BASE ?>/public/catalogo.php">← Volver al catálogo</a>
    <section id="view"></section>
  </main>

  <script>
  (function(){
    const BASE = <?= json_encode($BASE) ?>;
    const ID = <?= (int)$id ?>;
    const IMG_DIR = BASE + "/images/";
    const PLACEHOLDER = IMG_DIR + "placeholder.jpg";
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
      $view.innerHTML = `
        <div class="product-wrap">
          <div>
            <img src="${IMG_DIR + (p.image || 'placeholder.jpg')}" alt="${p.name || ''}" onerror="this.src='${PLACEHOLDER}'" />
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
      `;
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
</body>
</html>
