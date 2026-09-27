// Shared utilities: CSRF-aware fetch, toasts, small DOM helpers.
// Imported by the feature modules (cart, wishlist, search, ...).

export const $  = (sel, ctx = document) => ctx.querySelector(sel);
export const $$ = (sel, ctx = document) => Array.from(ctx.querySelectorAll(sel));

const cfg = () => window.SG || { baseUrl: '/', csrf: {} };

export const url = (path = '') => cfg().baseUrl + String(path).replace(/^\//, '');

// Keep the rotating CSRF token fresh across requests.
let csrfHash = cfg().csrf.hash;
export function currentCsrf() {
  return { name: cfg().csrf.name, hash: csrfHash, header: cfg().csrf.header };
}
function updateCsrf(newHash) {
  if (!newHash) return;
  csrfHash = newHash;
  const meta = document.querySelector('meta[name="csrf-token"]');
  if (meta) meta.setAttribute('content', newHash);
}

/**
 * POST JSON to a store endpoint with CSRF handling.
 * Returns the parsed { success, message, data } envelope.
 */
export async function postJSON(path, body = {}) {
  const { name, hash, header } = currentCsrf();
  const payload = { ...body, [name]: hash };
  const res = await fetch(url(path), {
    method: 'POST',
    headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest', [header]: hash },
    body: JSON.stringify(payload),
  });
  const json = await res.json().catch(() => ({ success: false, message: 'Something went wrong. Please try again.' }));
  if (json && json.data && json.data.csrf) updateCsrf(json.data.csrf);
  if (!res.ok && json.success === undefined) {
    return { success: false, message: 'Something went wrong. Please try again.' };
  }
  return json;
}

export async function getJSON(path) {
  const res = await fetch(url(path), { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
  return res.json();
}

export async function getHTML(path) {
  const res = await fetch(url(path), { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
  return res.text();
}

// --- Toasts (§43 / aria-live in layout) ------------------------------------
const ICONS = {
  success: '<path stroke-linecap="round" stroke-linejoin="round" d="m5 13 4 4L19 7"/>',
  error: '<path stroke-linecap="round" d="M12 8v5m0 3h.01M12 3a9 9 0 1 0 0 18 9 9 0 0 0 0-18Z"/>',
  info: '<path stroke-linecap="round" d="M12 9h.01M11 12h1v4h1M12 3a9 9 0 1 0 0 18 9 9 0 0 0 0-18Z"/>',
};
export function toast(message, type = 'success', timeout = 3500) {
  const region = document.getElementById('toast-region');
  if (!region) return;
  const el = document.createElement('div');
  const tone = type === 'error' ? 'bg-danger text-white' : type === 'info' ? 'bg-ink text-bg' : 'bg-ink text-bg';
  el.className = `pointer-events-auto w-full flex items-center gap-3 ${tone} rounded-md shadow-lift px-4 py-3 text-sm opacity-0 -translate-y-2 transition-all duration-300`;
  el.setAttribute('role', 'status');
  el.innerHTML = `<svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true">${ICONS[type] || ICONS.success}</svg><span>${message}</span>`;
  region.appendChild(el);
  requestAnimationFrame(() => { el.classList.remove('opacity-0', '-translate-y-2'); });
  const close = () => {
    el.classList.add('opacity-0', '-translate-y-2');
    setTimeout(() => el.remove(), 300);
  };
  setTimeout(close, timeout);
  el.addEventListener('click', close);
}

export const money = (n) =>
  '₹' + Number(n || 0).toLocaleString('en-IN', { maximumFractionDigits: 0 });

// Reflect the cart item count in both header badges.
export function setCartCount(count) {
  $$('[data-cart-count]').forEach((b) => {
    b.textContent = count;
    b.classList.toggle('hidden', !count);
  });
}
export function setWishlistCount(count) {
  $$('[data-wishlist-count]').forEach((b) => {
    b.textContent = count;
    b.classList.toggle('hidden', !count);
  });
}

export function debounce(fn, wait = 300) {
  let t;
  return (...args) => {
    clearTimeout(t);
    t = setTimeout(() => fn(...args), wait);
  };
}
