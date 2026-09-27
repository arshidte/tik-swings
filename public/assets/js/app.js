// Storefront entry point. Wires the shell and feature modules once the DOM
// is ready. Kept intentionally small — heavy work lives in the modules.
import { initShell } from './ui.js';
import { initAnimations } from './animations.js';
import { initWishlist } from './wishlist.js';
import { initCart } from './cart.js';
import { initSearch } from './search.js';

function boot() {
  initShell();
  initAnimations();
  initWishlist();
  initCart();
  initSearch();
}

if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', boot);
} else {
  boot();
}
