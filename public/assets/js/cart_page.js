// Full cart page interactions (§27). Quantity + remove + coupon without a
// full page reload; totals re-render from the server snapshot.
import { $, $$, postJSON, toast, money, setCartCount } from './util.js';

const page = $('[data-cart-page]');
if (page) {
  function applySnapshot(data) {
    setCartCount(data.count);
    if (!data.items || data.items.length === 0) { window.location.reload(); return; }

    const byId = Object.fromEntries(data.items.map((i) => [i.id, i]));
    $$('[data-cart-row]', page).forEach((row) => {
      const it = byId[row.dataset.itemId];
      if (!it) { row.remove(); return; }
      row.querySelector('[data-qty-val]').textContent = it.quantity;
      row.querySelector('[data-line-total]').textContent = money(it.line_total);
    });

    $('[data-sum-subtotal]').textContent = money(data.subtotal);
    $('[data-sum-shipping]').textContent = data.shipping > 0 ? money(data.shipping) : 'Free';
    $('[data-sum-total]').textContent = money(data.total);
    const discRow = $('[data-sum-discount-row]');
    if (data.discount > 0) {
      discRow.classList.remove('hidden');
      $('[data-sum-discount]').textContent = '−' + money(data.discount);
    } else {
      discRow.classList.add('hidden');
    }
  }

  page.addEventListener('click', async (e) => {
    const row = e.target.closest('[data-cart-row]');
    if (!row) return;
    const id = row.dataset.itemId;
    const qty = parseInt(row.querySelector('[data-qty-val]').textContent, 10);
    if (e.target.closest('[data-qty-inc]')) {
      const res = await postJSON('cart/update', { item_id: id, quantity: qty + 1 });
      if (res.success) applySnapshot(res.data);
    } else if (e.target.closest('[data-qty-dec]')) {
      const res = await postJSON('cart/update', { item_id: id, quantity: qty - 1 });
      if (res.success) applySnapshot(res.data);
    } else if (e.target.closest('[data-cart-remove]')) {
      const res = await postJSON('cart/remove', { item_id: id });
      if (res.success) { toast('Item removed.', 'info'); applySnapshot(res.data); }
    }
  });

  const couponForm = $('[data-coupon-form]', page);
  couponForm?.addEventListener('submit', async (e) => {
    e.preventDefault();
    const code = couponForm.code.value.trim();
    if (!code) return;
    const res = await postJSON('cart/coupon', { code });
    toast(res.message, res.success ? 'success' : 'error');
    if (res.success) applySnapshot(res.data);
  });
  $('[data-coupon-suggest]')?.addEventListener('click', () => {
    couponForm.code.value = 'WELCOME10';
    couponForm.requestSubmit();
  });
}
