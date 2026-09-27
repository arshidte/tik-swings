// Cart & mini-cart (§27 / §28). AJAX add / update / remove with the drawer
// rendered from a JSON snapshot. No full page reloads for quantity changes.
import { $, $$, postJSON, getJSON, toast, money, setCartCount } from './util.js';

function itemRow(it) {
  return `
  <div class="flex gap-3" data-cart-row data-item-id="${it.id}">
    <a href="${it.url}" class="shrink-0"><img src="${it.image}" alt="" width="80" height="80" class="w-20 h-20 rounded-md object-cover bg-sand"></a>
    <div class="flex-1 min-w-0">
      <a href="${it.url}" class="font-display text-[15px] leading-snug line-clamp-2 hover:text-wood">${it.name}</a>
      ${it.variant ? `<p class="text-xs text-subtle mt-0.5">${it.variant}</p>` : ''}
      <div class="flex items-center justify-between mt-2">
        <div class="inline-flex items-center border border-line rounded-md" data-qty>
          <button type="button" class="w-8 h-8 flex items-center justify-center text-muted hover:text-ink" data-qty-dec aria-label="Decrease quantity">–</button>
          <span class="w-8 text-center text-sm tabular-nums" data-qty-val>${it.quantity}</span>
          <button type="button" class="w-8 h-8 flex items-center justify-center text-muted hover:text-ink" data-qty-inc aria-label="Increase quantity">+</button>
        </div>
        <span class="font-medium tabular-nums text-sm">${money(it.line_total)}</span>
      </div>
    </div>
    <button type="button" class="self-start text-subtle hover:text-danger p-1" data-cart-remove aria-label="Remove item">
      <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24"><path stroke-linecap="round" d="M6 6l12 12M18 6 6 18"/></svg>
    </button>
  </div>`;
}

function render(data) {
  const wrap = $('[data-cart-items]');
  const empty = $('[data-cart-empty]');
  const footer = $('[data-cart-footer]');
  const titleCount = $('[data-cart-title-count]');
  if (!wrap) return;

  setCartCount(data.count);
  if (titleCount) titleCount.textContent = data.count ? `(${data.count})` : '';

  if (!data.items || data.items.length === 0) {
    wrap.innerHTML = '';
    empty && empty.classList.remove('hidden') & empty.classList.add('flex');
    footer && footer.classList.add('hidden');
    return;
  }
  empty && (empty.classList.add('hidden'), empty.classList.remove('flex'));
  footer && footer.classList.remove('hidden');
  wrap.innerHTML = data.items.map(itemRow).join('');
  const sub = $('[data-cart-subtotal]');
  if (sub) sub.textContent = money(data.subtotal);
}

async function load() {
  const wrap = $('[data-cart-items]');
  if (wrap && !wrap.dataset.loaded) {
    wrap.innerHTML = skeleton();
  }
  const res = await getJSON('cart/mini');
  if (res.success) {
    render(res.data);
    if (wrap) wrap.dataset.loaded = '1';
  }
}

function skeleton() {
  return Array.from({ length: 2 }).map(() => `
    <div class="flex gap-3 animate-pulse">
      <div class="w-20 h-20 rounded-md bg-sand"></div>
      <div class="flex-1 space-y-2 py-1"><div class="h-3 bg-sand rounded w-3/4"></div><div class="h-3 bg-sand rounded w-1/3"></div><div class="h-6 bg-sand rounded w-1/2 mt-3"></div></div>
    </div>`).join('');
}

export async function addToCart({ productId, variantId = null, quantity = 1, options = null, button = null }) {
  let original;
  if (button) {
    button.disabled = true;
    const label = button.querySelector('[data-btn-label]');
    original = label ? label.textContent : button.textContent;
    if (label) label.textContent = 'Adding…'; else button.textContent = 'Adding…';
  }
  const res = await postJSON('cart/add', {
    product_id: productId, variant_id: variantId, quantity, options,
  });
  if (button) {
    button.disabled = false;
    const label = button.querySelector('[data-btn-label]');
    if (label) label.textContent = original; else button.textContent = original;
  }
  if (!res.success) {
    toast(res.message || 'Could not add to cart.', 'error');
    return res;
  }
  render(res.data);
  const wrap = $('[data-cart-items]');
  if (wrap) wrap.dataset.loaded = '1';
  window.SG.openCart && window.SG.openCart();
  return res;
}

async function updateQty(itemId, quantity) {
  const res = await postJSON('cart/update', { item_id: itemId, quantity });
  if (res.success) render(res.data);
  else toast(res.message || 'Could not update cart.', 'error');
}

async function removeItem(itemId) {
  const res = await postJSON('cart/remove', { item_id: itemId });
  if (res.success) { render(res.data); toast('Item removed.', 'info'); }
}

export function initCart() {
  window.SG.loadCart = load;

  // Simple add-to-cart buttons (cards).
  document.addEventListener('click', (e) => {
    const btn = e.target.closest('[data-add-to-cart]');
    if (!btn) return;
    e.preventDefault();
    addToCart({ productId: btn.getAttribute('data-product-id'), quantity: 1, button: btn });
  });

  // Drawer quantity / remove (event delegation).
  const drawer = $('[data-cart]');
  if (drawer) {
    drawer.addEventListener('click', (e) => {
      const row = e.target.closest('[data-cart-row]');
      if (!row) return;
      const id = row.getAttribute('data-item-id');
      const valEl = row.querySelector('[data-qty-val]');
      let qty = parseInt(valEl.textContent, 10);
      if (e.target.closest('[data-qty-inc]')) updateQty(id, qty + 1);
      else if (e.target.closest('[data-qty-dec]')) { if (qty > 1) updateQty(id, qty - 1); else removeItem(id); }
      else if (e.target.closest('[data-cart-remove]')) removeItem(id);
    });
  }
}
