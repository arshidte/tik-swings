// Wishlist toggling (§29). DB-backed for logged-in users; server also keeps
// a session wishlist for guests. Heart animates on add.
import { $$, postJSON, toast, setWishlistCount } from './util.js';

export function initWishlist() {
  document.addEventListener('click', async (e) => {
    const btn = e.target.closest('[data-wishlist-toggle]');
    if (!btn) return;
    e.preventDefault();
    const id = btn.getAttribute('data-wishlist-toggle');
    btn.disabled = true;

    const res = await postJSON('wishlist/toggle', { product_id: id });
    btn.disabled = false;
    if (!res.success) {
      toast(res.message || 'Could not update wishlist.', 'error');
      return;
    }

    const added = res.data.added;
    // Reflect on every button for this product across the page.
    $$(`[data-wishlist-toggle="${id}"]`).forEach((b) => {
      const heart = b.querySelector('[data-heart]');
      b.setAttribute('aria-pressed', added ? 'true' : 'false');
      b.setAttribute('aria-label', added ? 'Remove from wishlist' : 'Add to wishlist');
      b.classList.toggle('text-terracotta', added);
      b.classList.toggle('text-ink', !added);
      if (heart) {
        heart.setAttribute('fill', added ? 'currentColor' : 'none');
        if (added) {
          heart.classList.remove('animate-heart-pop');
          void heart.offsetWidth;
          heart.classList.add('animate-heart-pop');
        }
      }
    });

    setWishlistCount(res.data.count);
    toast(added ? 'Saved to your wishlist.' : 'Removed from wishlist.', added ? 'success' : 'info');
  });
}
