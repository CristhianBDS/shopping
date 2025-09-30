<?php
// public/checkout.php
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/bootstrap.php';

$CONTEXT = 'public';
$PAGE_TITLE = 'Checkout';

$BASE = defined('BASE_URL') ? BASE_URL : '/shopping';

include __DIR__ . '/../templates/header.php';
?>
<div class="checkout-top d-flex justify-content-between align-items-center mb-3">
  <a class="btn btn-outline-secondary" href="<?= $BASE ?>/public/carrito.php">← Volver al carrito</a>
  <a class="btn btn-outline-dark" href="<?= $BASE ?>/public/carrito.php">🛒 <span id="cart-count">0</span></a>
</div>

<div id="ck-empty" class="empty" style="display:none;">Tu carrito está vacío.</div>

<div id="ck-grid" class="row g-3" style="display:none;">
  <!-- Formulario -->
  <section class="col-lg-8">
    <div class="card shadow-sm">
      <div class="card-body">
        <h2 class="h5 mb-3">Datos de envío</h2>
        <form id="ck-form" class="ck-form" novalidate>
          <div class="row">
            <div class="col-md-6 mb-3">
              <label class="form-label">Nombre y Apellidos</label>
              <input type="text" name="fullname" class="form-control" required />
            </div>
            <div class="col-md-6 mb-3">
              <label class="form-label">Email</label>
              <input type="email" name="email" class="form-control" required />
            </div>
          </div>

          <div class="row">
            <div class="col-md-6 mb-3">
              <label class="form-label">Teléfono</label>
              <input type="tel" name="phone" class="form-control" required />
            </div>
            <div class="col-md-6 mb-3">
              <label class="form-label">Documento (opcional)</label>
              <input type="text" name="doc" class="form-control" />
            </div>
          </div>

          <div class="mb-3">
            <label class="form-label">Dirección</label>
            <input type="text" name="address" class="form-control" required placeholder="Calle, número, piso" />
          </div>

          <div class="row">
            <div class="col-md-6 mb-3">
              <label class="form-label">Ciudad</label>
              <input type="text" name="city" class="form-control" required />
            </div>
            <div class="col-md-6 mb-3">
              <label class="form-label">Código Postal</label>
              <input type="text" name="zip" class="form-control" required />
            </div>
          </div>

          <div class="mb-3">
            <label class="form-label">Notas para el repartidor (opcional)</label>
            <textarea name="notes" rows="3" class="form-control"></textarea>
          </div>

          <h3 class="h6 mt-4">Método de pago</h3>
          <div class="d-flex gap-3 mb-3">
            <label class="form-check"><input class="form-check-input" type="radio" name="pay" value="tarjeta" checked> Tarjeta</label>
            <label class="form-check"><input class="form-check-input" type="radio" name="pay" value="transferencia"> Transferencia</label>
            <label class="form-check"><input class="form-check-input" type="radio" name="pay" value="contraentrega"> Contraentrega</label>
          </div>

          <button class="btn btn-primary" type="submit">Confirmar pedido</button>
        </form>
      </div>
    </div>
  </section>

  <!-- Resumen -->
  <aside class="col-lg-4">
    <div class="card shadow-sm">
      <div class="card-body">
        <h2 class="h5 mb-3">Resumen</h2>
        <ul id="ck-list" class="list-unstyled"></ul>
        <hr />
        <div class="ck-totals">
          <div class="d-flex justify-content-between">
            <span class="text-muted">Subtotal</span><span id="ck-subtotal">€ 0,00</span>
          </div>
          <div class="d-flex justify-content-between">
            <span class="text-muted">Envío</span><span id="ck-shipping">Gratis</span>
          </div>
          <div class="d-flex justify-content-between fs-5 mt-2">
            <strong>Total</strong><strong id="ck-total">€ 0,00</strong>
          </div>
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

  const $empty = document.getElementById("ck-empty");
  const $grid  = document.getElementById("ck-grid");
  const $list  = document.getElementById("ck-list");
  const $subtotal = document.getElementById("ck-subtotal");
  const $total = document.getElementById("ck-total");
  const $cartCount = document.getElementById("cart-count");
  const $form = document.getElementById("ck-form");

  // Utils carrito
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
    img.src = IMG_UPLOADS + clean;               // 1) uploads
    img.onerror = function step1(){
      img.onerror = function step2(){
        img.onerror = null;
        img.src = PLACEHOLDER;                   // 3) placeholder
      };
      img.src = IMG_IMAGES + clean;              // 2) images
    };
    img.className = "me-2";
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

    // Lista resumen
    $list.innerHTML = "";
    let subtotal = 0;
    cart.forEach(p => {
      const price = Number(p.price) || 0;
      const qty = Number(p.qty) || 1;
      subtotal += price * qty;

      const li = document.createElement("li");
      li.className = "d-flex align-items-center justify-content-between mb-2";

      const left = document.createElement("div");
      left.className = "d-flex align-items-center";

      const img = createImg(p.image, p.name || '');
      left.appendChild(img);

      const info = document.createElement("div");
      info.innerHTML = `<div class="fw-semibold">${p.name || ''}</div><div class="text-muted small">x${qty}</div>`;
      left.appendChild(info);

      const right = document.createElement("div");
      right.textContent = money(price * qty);

      li.appendChild(left);
      li.appendChild(right);
      $list.appendChild(li);
    });

    $subtotal.textContent = money(subtotal);
    $total.textContent = money(subtotal); // sin envío/impuestos por ahora
  }

  // Enviar (simulado)
  if ($form) {
    $form.addEventListener("submit", (e) => {
      e.preventDefault();

      const data = Object.fromEntries(new FormData($form).entries());
      const required = ["fullname", "email", "phone", "address", "city", "zip"];
      for (const k of required) {
        if (!String(data[k]||"").trim()) {
          alert("Completa todos los campos obligatorios.");
          return;
        }
      }

      const order = {
        customer: data,
        items: getCart(),
        total: document.getElementById("ck-total").textContent,
        date: new Date().toISOString()
      };

      localStorage.setItem("last_order", JSON.stringify(order));
      setCart([]); // vaciar carrito

      alert("¡Pedido confirmado! Gracias por tu compra.");
      window.location.href = BASE + "/public/catalogo.php";
    });
  }

  window.addEventListener("storage", (ev) => {
    if (ev.key === "cart") render();
  });

  render();
})();
</script>

<?php include __DIR__ . '/../templates/footer.php'; ?>
