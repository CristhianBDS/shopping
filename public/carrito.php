<?php
// public/carrito.php — Carrito de compra (público) — versión sin referencias a pago
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/bootstrap.php';

$CONTEXT = 'public';
$PAGE_TITLE = 'Tu carrito';

$BASE = defined('BASE_URL') ? BASE_URL : '/shopping';

include __DIR__ . '/../templates/header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <a class="btn btn-outline-secondary" href="<?= $BASE ?>/public/catalogo.php">← Seguir comprando</a>
  <a class="btn btn-outline-dark" href="<?= $BASE ?>/public/carrito.php">🛒 <span id="cart-count">0</span></a>
</div>

<div id="empty" class="empty" style="display:none;">Tu carrito está vacío.</div>

<div class="row g-3" id="grid" style="display:none;">
  <section class="col-lg-8">
    <div class="card shadow-sm">
      <div class="card-body">
        <div class="table-responsive">
          <table class="table align-middle">
            <thead>
              <tr>
                <th>Producto</th>
                <th class="text-end">Precio</th>
                <th class="text-end">Cantidad</th>
                <th class="text-end">Subtotal</th>
                <th class="text-end"></th>
              </tr>
            </thead>
            <tbody id="tbody"></tbody>
          </table>
        </div>
      </div>
    </div>
  </section>

  <aside class="col-lg-4">
    <div class="card shadow-sm">
      <div class="card-body">
        <h2 class="h5 mb-3">Resumen</h2>
        <div class="d-flex justify-content-between mb-2">
          <span class="text-muted">Subtotal</span>
          <span id="subtotal">€ 0,00</span>
        </div>
        <div class="d-flex justify-content-between mb-2">
          <span class="text-muted">Envío</span>
          <!-- Nota: sin cálculo ni método de pago -->
          <span id="shipping">Se coordina al confirmar</span>
        </div>
        <hr>
        <div class="d-flex justify-content-between align-items-center">
          <strong>Total</strong>
          <strong id="total">€ 0,00</strong>
        </div>
        <div class="d-flex gap-2 mt-3">
          <button id="clear" class="btn btn-outline-secondary">Vaciar</button>
          <!-- Mantener solo el flujo de finalizar compra (sin método de pago) -->
          <a class="btn btn-primary" href="<?= $BASE ?>/public/checkout.php">Finalizar compra</a>
        </div>
      </div>
    </div>
  </aside>
</div>

<script>
(function(){
  const BASE = <?= json_encode($BASE) ?>;
  const IMG_UPLOADS = BASE + "/uploads/";
  const IMG_IMAGES  = BASE + "/images/";
  const PLACEHOLDER = IMG_IMAGES + "placeholder.jpg";

  const $empty = document.getElementById("empty");
  const $grid  = document.getElementById("grid");
  const $tbody = document.getElementById("tbody");
  const $subtotal = document.getElementById("subtotal");
  const $total = document.getElementById("total");
  const $cartCount = document.getElementById("cart-count");
  const $btnClear = document.getElementById("clear");

  function getCart(){ try { return JSON.parse(localStorage.getItem("cart") || "[]"); } catch { return []; } }
  function setCart(c){ localStorage.setItem("cart", JSON.stringify(c)); refreshCartBadge(); }
  function cartCount(){ return getCart().reduce((a,i)=>a+(Number(i.qty)||0),0); }
  function money(n){ try { return new Intl.NumberFormat('es-ES',{style:'currency',currency:'EUR'}).format(n); } catch { return "€ " + Number(n).toFixed(2); } }
  function refreshCartBadge(){ if ($cartCount) $cartCount.textContent = String(cartCount()); }

  function createImg(fname, altText){
    const img = document.createElement('img');
    img.alt = altText || '';
    const clean = (fname||'').trim();
    if (!clean) { img.src = PLACEHOLDER; return img; }
    // 1) uploads → 2) images → 3) placeholder
    img.src = IMG_UPLOADS + clean;
    img.onerror = function step1(){
      img.onerror = function step2(){
        img.onerror = null;
        img.src = PLACEHOLDER;
      };
      img.src = IMG_IMAGES + clean;
    };
    img.className = "thumb";
    img.style.width = "48px";
    img.style.height = "48px";
    img.style.objectFit = "cover";
    img.style.borderRadius = "8px";
    return img;
  }

  function render(){
    const cart = getCart();
    if (!cart.length){
      $empty.style.display = "block";
      $grid.style.display = "none";
      refreshCartBadge();
      return;
    }
    $empty.style.display = "none";
    $grid.style.display = "flex";
    refreshCartBadge();

    $tbody.innerHTML = "";
    let subtotal = 0;

    cart.forEach((p, idx) => {
      const price = Number(p.price) || 0;
      const qty = Math.max(1, Math.min(99, Number(p.qty) || 1));
      const sub = price * qty;
      subtotal += sub;

      const tr = document.createElement("tr");

      const tdProd = document.createElement("td");
      const wrap = document.createElement("div");
      wrap.className = "d-flex align-items-center gap-2";

      const img = createImg(p.image, p.name || '');
      wrap.appendChild(img);

      const name = document.createElement("div");
      name.textContent = p.name || '';
      wrap.appendChild(name);

      tdProd.appendChild(wrap);

      const tdPrice = document.createElement("td");
      tdPrice.className = "text-end";
      tdPrice.textContent = money(price);

      const tdQty = document.createElement("td");
      tdQty.className = "text-end";
      const input = document.createElement("input");
      input.type = "number";
      input.min = "1";
      input.max = "99";
      input.value = String(qty);
      input.className = "form-control d-inline-block";
      input.style.width = "90px";
      input.dataset.idx = String(idx);
      tdQty.appendChild(input);

      const tdSub = document.createElement("td");
      tdSub.className = "text-end";
      tdSub.textContent = money(sub);

      const tdDel = document.createElement("td");
      tdDel.className = "text-end";
      const btn = document.createElement("button");
      btn.className = "btn btn-outline-secondary btn-sm";
      btn.textContent = "✕";
      btn.dataset.remove = String(idx);
      tdDel.appendChild(btn);

      tr.appendChild(tdProd);
      tr.appendChild(tdPrice);
      tr.appendChild(tdQty);
      tr.appendChild(tdSub);
      tr.appendChild(tdDel);

      $tbody.appendChild(tr);
    });

    $subtotal.textContent = money(subtotal);
    $total.textContent = money(subtotal);
  }

  // Delegación de eventos para qty y eliminar
  $tbody.addEventListener("input", (e) => {
    const el = e.target;
    if (el.tagName === 'INPUT' && el.type === 'number' && el.dataset.idx !== undefined) {
      const idx = Number(el.dataset.idx);
      const val = Math.max(1, Math.min(99, Number(el.value) || 1));
      const cart = getCart();
      cart[idx].qty = val;
      setCart(cart);
      render();
    }
  });

  $tbody.addEventListener("click", (e) => {
    const btn = e.target.closest("button[data-remove]");
    if (!btn) return;
    const idx = Number(btn.dataset.remove);
    const cart = getCart();
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

<?php include __DIR__ . '/../templates/footer.php'; ?>
