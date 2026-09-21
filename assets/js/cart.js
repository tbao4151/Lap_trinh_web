(function () {
  var STORAGE_KEY = 'cas-hoa-static-cart';

  function readCart() {
    try {
      var parsed = JSON.parse(localStorage.getItem(STORAGE_KEY) || '[]');
      if (!Array.isArray(parsed)) return [];
      return parsed.filter(function (line) { return line && line.productId && Number(line.quantity) > 0; });
    } catch (error) {
      return [];
    }
  }

  function writeCart(cart) {
    localStorage.setItem(STORAGE_KEY, JSON.stringify(cart));
    window.dispatchEvent(new CustomEvent('cas-cart-updated', { detail: cart }));
    return cart;
  }

  function clampQuantity(quantity) {
    return Math.max(1, Math.min(20, Number(quantity) || 1));
  }

  function add(productId, quantity) {
    var cart = readCart();
    var line = cart.find(function (item) { return item.productId === productId; });
    if (line) line.quantity = clampQuantity(line.quantity + clampQuantity(quantity));
    else cart.push({ productId: productId, quantity: clampQuantity(quantity) });
    return writeCart(cart);
  }

  function update(productId, quantity) {
    var cart = readCart();
    var line = cart.find(function (item) { return item.productId === productId; });
    if (!line) return cart;
    line.quantity = clampQuantity(quantity);
    return writeCart(cart);
  }

  function remove(productId) {
    return writeCart(readCart().filter(function (item) { return item.productId !== productId; }));
  }

  function clear() { return writeCart([]); }

  function count() {
    return readCart().reduce(function (sum, line) { return sum + clampQuantity(line.quantity); }, 0);
  }

  function detailed() {
    return readCart().map(function (line) {
      var product = window.CAS_FIND_PRODUCT(line.productId);
      if (!product) return null;
      return { product: product, quantity: clampQuantity(line.quantity), lineTotal: product.price * clampQuantity(line.quantity) };
    }).filter(Boolean);
  }

  function subtotal() {
    return detailed().reduce(function (sum, line) { return sum + line.lineTotal; }, 0);
  }

  window.CASCart = { read: readCart, add: add, update: update, remove: remove, clear: clear, count: count, detailed: detailed, subtotal: subtotal };
})();
