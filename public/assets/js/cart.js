// Cart & mini-cart (§27 / §28). AJAX add / update / remove with the drawer
// rendered from a JSON snapshot. No full page reloads for quantity changes.
import { $, $$, postJSON, getJSON, toast, money, setCartCount } from './util.js';

const esc = (s) => String(s ?? '').replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));

function itemRow(it) {
  const isAddon = !!it.addon_of;
  return `
  <div class="flex gap-3 ${isAddon ? 'ml-6 -mt-1 pl-3 border-l-2 border-line' : ''}" data-cart-row data-item-id="${it.id}">
    <a href="${it.url}" class="shrink-0"><img src="${it.image}" alt="" width="80" height="80" class="${isAddon ? 'w-14 h-14' : 'w-20 h-20'} rounded-md object-cover bg-sand"></a>
    <div class="flex-1 min-w-0">
      ${isAddon ? '<p class="text-[11px] uppercase tracking-wide text-subtle leading-none mb-1">Add-on</p>' : ''}
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

/**
 * Markup for one add-on suggestion (shared by the drawer and the cart page).
 * Add-ons with variants get a compact <select>; sold-out variants are disabled.
 */
export function suggestionRow(s) {
  const variantPicker = s.variants && s.variants.length > 1
    ? `<select class="mt-1.5 w-full rounded-md border border-line bg-bg px-2 py-1.5 text-xs focus:border-ink focus:outline-none" data-suggestion-variant aria-label="Choose an option for ${esc(s.name)}">
        ${s.variants.map((v) => `<option value="${v.id}" data-price="${v.price}" ${v.id === s.variant_id ? 'selected' : ''} ${v.in_stock ? '' : 'disabled'}>${esc(v.label)} — ${money(v.price)}${v.in_stock ? '' : ' (sold out)'}</option>`).join('')}
      </select>`
    : '';
  return `
  <div class="flex gap-3 items-start" data-suggestion data-product-id="${s.product_id}" data-parent-id="${s.parent_id}" data-parent-qty="${s.parent_qty || 1}" data-variant-id="${s.variant_id || ''}">
    <a href="${esc(s.url)}" class="shrink-0"><img src="${esc(s.image)}" alt="" width="56" height="56" loading="lazy" class="w-14 h-14 rounded-md object-cover bg-sand"></a>
    <div class="flex-1 min-w-0">
      <div class="flex items-start justify-between gap-2">
        <a href="${esc(s.url)}" class="text-sm font-medium leading-snug line-clamp-2 hover:text-wood">${esc(s.name)}</a>
        <span class="text-sm tabular-nums whitespace-nowrap" data-suggestion-price>${money(s.price)}</span>
      </div>
      ${variantPicker}
    </div>
    <button type="button" class="shrink-0 self-center min-h-[36px] px-3 rounded-md border border-ink text-xs font-medium hover:bg-ink hover:text-bg transition disabled:opacity-50" data-suggestion-add>+ Add</button>
  </div>`;
}

function renderSuggestions(list) {
  const box = $('[data-cart-suggestions]');
  if (!box) return;
  const items = list || [];
  box.classList.toggle('hidden', items.length === 0);
  $('[data-cart-suggestion-list]', box).innerHTML = items.map(suggestionRow).join('');
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
    renderSuggestions([]);
    empty && empty.classList.remove('hidden') & empty.classList.add('flex');
    footer && footer.classList.add('hidden');
    return;
  }
  empty && (empty.classList.add('hidden'), empty.classList.remove('flex'));
  footer && footer.classList.remove('hidden');
  wrap.innerHTML = data.items.map(itemRow).join('');
  renderSuggestions(data.suggestions);
  const sub = $('[data-cart-subtotal]');
  if (sub) sub.textContent = money(data.subtotal);
}

function showAdded(message) {
  const banner = $('[data-cart-added]');
  if (!banner) return;
  const text = $('[data-cart-added-text]', banner);
  if (text) text.textContent = message || 'Added to your cart';
  banner.classList.remove('hidden');
  banner.classList.add('flex');
}
function hideAdded() {
  const banner = $('[data-cart-added]');
  if (banner) { banner.classList.add('hidden'); banner.classList.remove('flex'); }
}

async function load() {
  const wrap = $('[data-cart-items]');
  hideAdded();
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

/**
 * Add a product (optionally with add-ons: [{product_id, variant_id}]) and
 * open the drawer. Set openDrawer=false to stay put (e.g. on the cart page).
 */
export async function addToCart({ productId, variantId = null, quantity = 1, options = null, addons = [], button = null, openDrawer = true }) {
  let original;
  if (button) {
    button.disabled = true;
    const label = button.querySelector('[data-btn-label]');
    original = label ? label.textContent : button.textContent;
    if (label) label.textContent = 'Adding…'; else button.textContent = 'Adding…';
  }
  const res = await postJSON('cart/add', {
    product_id: productId, variant_id: variantId, quantity, options, addons,
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
  if (openDrawer) {
    showAdded(res.message);
    window.SG.openCart && window.SG.openCart();
  }
  return res;
}

/**
 * Wire "+ Add" buttons and variant selects inside a suggestion container.
 * onAdded(res) runs after a successful add.
 */
export function bindSuggestions(container, { openDrawer = true, onAdded = null } = {}) {
  container.addEventListener('change', (e) => {
    const sel = e.target.closest('[data-suggestion-variant]');
    if (!sel) return;
    const row = sel.closest('[data-suggestion]');
    const opt = sel.selectedOptions[0];
    row.dataset.variantId = sel.value;
    const price = row.querySelector('[data-suggestion-price]');
    if (price && opt) price.textContent = money(opt.dataset.price);
  });
  container.addEventListener('click', async (e) => {
    const btn = e.target.closest('[data-suggestion-add]');
    if (!btn) return;
    const row = btn.closest('[data-suggestion]');
    // One add-on per parent item: match the parent's quantity in the cart.
    const res = await addToCart({
      productId: row.dataset.productId,
      variantId: parseInt(row.dataset.variantId, 10) || null,
      quantity: parseInt(row.dataset.parentQty, 10) || 1,
      button: btn,
      openDrawer,
    });
    if (res.success) {
      hideAdded();
      toast('Added to your cart.');
      onAdded && onAdded(res);
    }
  });
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

    const suggestions = $('[data-cart-suggestions]', drawer);
    if (suggestions) bindSuggestions(suggestions);
  }
}
