<?php
// shopping/public/carrito.php
require_once __DIR__ . '/../config/app.php';
$BASE = defined('BASE_URL') ? BASE_URL : '/shopping';
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Tu carrito</title>
  <link rel="stylesheet" href="<?= $BASE ?>/assets/base.css">
  <link rel="stylesheet" href="<?= $BASE ?>/assets/tienda.css">
</head>
<body>
  <main class="page">
    <div class="cart-top">
      <a class="btn secondary" href="<?= $BASE ?>/public/catalogo.php">← Seguir comprando</a>
      <a class="btn" href="<?= $BASE ?>/public/carrito.php">🛒 <span id="cart-count">0</span></a>
    </div>

    <div id="empty" class="empty" style="display:none;">Tu carrito está vacío.</div>

    <div class="cart-grid" id="grid" style="display:none;">
      <section class="card-box">
        <table id="table">
          <thead>
            <tr>
              <th>Producto</th>
              <th class="right">Precio</th>
              <th class="right">Cantidad</th>
              <th class="right">Subtotal</th>
              <th></th>
            </tr>
          </thead>
          <tbody id="tbody"></tbody>
        </table>
      </section>

      <aside class="card-box">
        <h2>Resumen</h2>
        <div style="display:flex;justify-content:space-between;margin:.4rem 0;">
          <span class="muted">Subtotal</span>
          <span id="subtotal">€ 0,00</span>
        </div>
        <div style="display:flex;justify-content:space-between;margin:.4rem 0;">
          <span class="muted">Envío</span>
          <span id="shipping">A calcular</span>
        </div>
        <hr style="border:none;border-top:1px solid #eee;margin:.6rem 0;">
        <div style="display:flex;justify-content:space-between;align-items:center;">
          <span class="total">Total</span>
          <span class="total" id="total">€ 0,00</span>
        </div>
        <div style="display:flex;gap:.6rem;margin-top:1rem;">
          <button id="clear" class="btn secondary">Vaciar</button>
          <!-- IMPORTANTE: ahora es un ENLACE directo al checkout -->
          <a class="btn" href="<?= $BASE ?>/public/checkout.php">Finalizar compra</a>
        </div>
      </aside>
    </div>
  </main>

  <script>
  (function(){
    const BASE = <?= json_encode($BASE) ?>;
    const IMG_DIR = BASE + "/images/";
    const PLACEHOLDER = IMG_DIR + "placeholder.jpg";

    const $empty = document.getElementById("empty");
    const $grid = document.getElementById("grid");
    const $tbody = document.getElementById("tbody");
    const $subtotal = document.getElementById("subtotal");
    const $total = document.getElementById("total");
    const $cartCount = document.getElementById("cart-count");
    const $btnClear = document.getElementById("clear");
    // 👇 OJO: ya NO existe $btnCheckout ni listeners de click para checkout

    function getCart(){ try { return JSON.parse(localStorage.getItem("cart") || "[]"); } catch { return []; } }
    function setCart(c){ localStorage.setItem("cart", JSON.stringify(c)); refreshCartBadge(); }
    function cartCount(){ return getCart().reduce((a,i)=>a+(Number(i.qty)||0),0); }
    function money(n){ try { return new Intl.NumberFormat('es-ES',{style:'currency',currency:'EUR'}).format(n); } catch { return "€ " + Number(n).toFixed(2); } }
    function refreshCartBadge(){ if ($cartCount) $cartCount.textContent = String(cartCount()); }

    function render(){
      const cart = getCart();
      if (!cart.length){
        $empty.style.display = "block";
        $grid.style.display = "none";
        refreshCartBadge();
        return;
      }
      $empty.style.display = "none";
      $grid.style.display = "grid";
      refreshCartBadge();

      $tbody.innerHTML = "";
      let subtotal = 0;
      cart.forEach((p, idx) => {
        const price = Number(p.price) || 0;
        const qty = Number(p.qty) || 1;
        const sub = price * qty;
        subtotal += sub;

        const tr = document.createElement("tr");
        tr.innerHTML = `
          <td>
            <div class="item">
              <img class="thumb" src="${IMG_DIR + (p.image || 'placeholder.jpg')}" onerror="this.src='${PLACEHOLDER}'" alt="${p.name || ''}">
              <div>${p.name || ''}</div>
            </div>
          </td>
          <td class="right">${money(price)}</td>
          <td class="right">
            <input class="qty" type="number" min="1" max="99" value="${qty}" data-idx="${idx}"/>
          </td>
          <td class="right">${money(sub)}</td>
          <td class="right">
            <button class="btn secondary" data-remove="${idx}">✕</button>
          </td>
        `;
        $tbody.appendChild(tr);
      });

      $subtotal.textContent = money(subtotal);
      $total.textContent = money(subtotal);
    }

    // Delegación de eventos para qty y eliminar
    $tbody.addEventListener("input", (e) => {
      const el = e.target;
      if (el.classList.contains("qty")) {
        const idx = Number(el.dataset.idx);
        let cart = getCart();
        const val = Math.max(1, Math.min(99, Number(el.value) || 1));
        cart[idx].qty = val;
        setCart(cart);
        render();
      }
    });

    $tbody.addEventListener("click", (e) => {
      const btn = e.target.closest("button[data-remove]");
      if (!btn) return;
      const idx = Number(btn.dataset.remove);
      let cart = getCart();
      cart.splice(idx, 1);
      setCart(cart);
      render();
    });

    // Vaciar carrito
    $btnClear.addEventListener("click", () => {
      if (confirm("¿Vaciar carrito?")) {
        setCart([]);
        render();
      }
    });

    // Sincroniza si se modifica en otra pestaña
    window.addEventListener("storage", (ev) => {
      if (ev.key === "cart") render();
    });

    // Init
    refreshCartBadge();
    render();
  })();
  </script>
</body>
</html>
