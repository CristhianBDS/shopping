// js/cart.js
(function () {
  const KEY = "cart";

  function getCart() {
    try {
      return JSON.parse(localStorage.getItem(KEY) || "[]");
    } catch {
      return [];
    }
  }

  function saveCart(cart) {
    localStorage.setItem(KEY, JSON.stringify(cart));
  }

  function cartCount() {
    return getCart().reduce((acc, it) => acc + (Number(it.qty) || 0), 0);
  }

  function refreshCartBadge() {
    const c = document.getElementById("cart-count");
    if (c) c.textContent = String(cartCount());
  }

  function addToCart(id, name, price, image) {
    const item = { id, name, price, image, qty: 1 };
    try {
      const cart = getCart();
      const idx = cart.findIndex((p) => String(p.id) === String(id));
      if (idx >= 0) {
        cart[idx].qty = Math.min(99, (Number(cart[idx].qty) || 0) + 1);
      } else {
        cart.push(item);
      }
      saveCart(cart);
      refreshCartBadge();
      alert("Producto añadido al carrito");
    } catch (e) {
      alert("No se pudo añadir al carrito");
    }
  }

  // Exponer lo que necesitamos en global
  window.addToCart = addToCart;
  window.refreshCartBadge = refreshCartBadge;

  // Actualizar badge al cargar y cuando cambie el storage (otras pestañas)
  document.addEventListener("DOMContentLoaded", refreshCartBadge);
  window.addEventListener("storage", function (ev) {
    if (ev.key === KEY) refreshCartBadge();
  });
})();
