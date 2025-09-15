<?php
require_once __DIR__ . '/../config/app.php';
$BASE = defined('BASE_URL') ? BASE_URL : '/shopping';
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Checkout</title>
  <!-- CSS global + tienda -->
  <link rel="stylesheet" href="<?= $BASE ?>/assets/base.css">
  <link rel="stylesheet" href="<?= $BASE ?>/assets/tienda.css">
</head>
<body>
  <main class="page">
    <div class="checkout-top">
      <a class="btn secondary" href="<?= $BASE ?>/public/carrito.php">← Volver al carrito</a>
      <a class="btn" href="<?= $BASE ?>/public/carrito.php">🛒 <span id="cart-count">0</span></a>
    </div>

    <div id="ck-empty" class="empty" style="display:none;">Tu carrito está vacío.</div>

    <div id="ck-grid" class="checkout-grid" style="display:none;">
      <!-- Formulario -->
      <section class="card-box">
        <h2>Datos de envío</h2>
        <form id="ck-form" class="ck-form" novalidate>
          <div class="row-2">
            <label>Nombre y Apellidos
              <input type="text" name="fullname" required />
            </label>
            <label>Email
              <input type="email" name="email" required />
            </label>
          </div>

          <div class="row-2">
            <label>Teléfono
              <input type="tel" name="phone" required />
            </label>
            <label>Documento (opcional)
              <input type="text" name="doc" />
            </label>
          </div>

          <label>Dirección
            <input type="text" name="address" required placeholder="Calle, número, piso" />
          </label>

          <div class="row-2">
            <label>Ciudad
              <input type="text" name="city" required />
            </label>
            <label>Código Postal
              <input type="text" name="zip" required />
            </label>
          </div>

          <label>Notas para el repartidor (opcional)
            <textarea name="notes" rows="3"></textarea>
          </label>

          <h3>Método de pago</h3>
          <div class="pay-methods">
            <label><input type="radio" name="pay" value="tarjeta" checked /> Tarjeta</label>
            <label><input type="radio" name="pay" value="transferencia" /> Transferencia</label>
            <label><input type="radio" name="pay" value="contraentrega" /> Contraentrega</label>
          </div>

          <button class="btn" type="submit">Confirmar pedido</button>
        </form>
      </section>

      <!-- Resumen -->
      <aside class="card-box">
        <h2>Resumen</h2>
        <ul id="ck-list" class="ck-list"></ul>
        <hr />
        <div class="ck-totals">
          <div><span class="muted">Subtotal</span><span id="ck-subtotal">€ 0,00</span></div>
          <div><span class="muted">Envío</span><span id="ck-shipping">Gratis</span></div>
          <div class="total"><span>Total</span><span id="ck-total">€ 0,00</span></div>
        </div>
      </aside>
    </div>
  </main>

  <script>
  (function(){
    const BASE = <?= json_encode($BASE) ?>;
    const IMG_DIR = BASE + "/img/";
    const PLACEHOLDER = IMG_DIR + "placeholder.jpg";

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

      // Lista resumen
      $list.innerHTML = "";
      let subtotal = 0;
      cart.forEach(p => {
        const price = Number(p.price) || 0;
        const qty = Number(p.qty) || 1;
        subtotal += price * qty;

        const li = document.createElement("li");
        li.className = "ck-item";
        li.innerHTML = `
          <img src="${IMG_DIR + (p.image || 'placeholder.jpg')}" onerror="this.src='${PLACEHOLDER}'" alt="${p.name || ''}">
          <div class="info">
            <div class="name">${p.name || ''}</div>
            <div class="muted">x${qty}</div>
          </div>
          <div class="price">${money(price * qty)}</div>
        `;
        $list.appendChild(li);
      });

      $subtotal.textContent = money(subtotal);
      $total.textContent = money(subtotal); // sin envío/impuestos por ahora
    }

    // Enviar (simulado)
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

      // Simulación de pedido confirmado
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

    window.addEventListener("storage", (ev) => {
      if (ev.key === "cart") render();
    });

    render();
  })();
  </script>
</body>
</html>
