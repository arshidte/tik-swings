// UI shell: overlays & drawers (menu, search, cart), scroll lock,
// focus management, escape routes (§9 modal-escape, a11y).
import { $, $$, postJSON, toast } from './util.js';

let scrollY = 0;
let openCount = 0;

function lockScroll() {
  if (openCount === 0) {
    scrollY = window.scrollY;
    document.body.style.position = 'fixed';
    document.body.style.top = `-${scrollY}px`;
    document.body.style.width = '100%';
  }
  openCount++;
}
function unlockScroll() {
  openCount = Math.max(0, openCount - 1);
  if (openCount === 0) {
    document.body.style.position = '';
    document.body.style.top = '';
    document.body.style.width = '';
    window.scrollTo(0, scrollY);
  }
}

/**
 * Wire an overlay/drawer defined by a root element with:
 *  [data-<key>-overlay] backdrop, [data-<key>-panel] sliding panel.
 * Returns an { open, close, root } controller.
 */
export function createPanel(root, key, { onOpen } = {}) {
  if (!root) return null;
  const overlay = $(`[data-${key}-overlay]`, root);
  const panel = $(`[data-${key}-panel]`, root);
  let lastFocused = null;

  const panelHidden = () => {
    // default hidden transforms per panel type are set in markup
    panel.style.transform = '';
    panel.style.opacity = '';
  };

  function open() {
    if (!root.classList.contains('hidden')) return;
    lastFocused = document.activeElement;
    root.classList.remove('hidden');
    lockScroll();
    requestAnimationFrame(() => {
      overlay && (overlay.style.opacity = '1');
      panel.classList.remove(
        '-translate-x-full', 'translate-x-full', '-translate-y-4', 'translate-y-full', 'opacity-0'
      );
      panelHidden();
    });
    const focusable = panel.querySelector('input, button, a, [tabindex]');
    focusable && setTimeout(() => focusable.focus(), 120);
    onOpen && onOpen();
  }

  function close() {
    if (root.classList.contains('hidden')) return;
    overlay && (overlay.style.opacity = '0');
    // re-apply the panel's resting hidden state
    if (panel.dataset.hide) panel.classList.add(...panel.dataset.hide.split(' '));
    panel.style.opacity = '0';
    setTimeout(() => {
      root.classList.add('hidden');
      unlockScroll();
      lastFocused && lastFocused.focus && lastFocused.focus();
    }, 300);
  }

  overlay && overlay.addEventListener('click', close);
  $$(`[data-${key}-close]`, root).forEach((b) => b.addEventListener('click', close));
  root.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') close();
    if (e.key === 'Tab') trapFocus(e, panel);
  });

  return { open, close, root, panel };
}

function trapFocus(e, container) {
  const items = $$('a[href], button:not([disabled]), input, select, textarea, [tabindex]:not([tabindex="-1"])', container)
    .filter((el) => el.offsetParent !== null);
  if (!items.length) return;
  const first = items[0];
  const last = items[items.length - 1];
  if (e.shiftKey && document.activeElement === first) {
    e.preventDefault();
    last.focus();
  } else if (!e.shiftKey && document.activeElement === last) {
    e.preventDefault();
    first.focus();
  }
}

export function initShell() {
  // Record resting hidden classes so close() can restore them.
  const menu = $('[data-menu]');
  if (menu) $('[data-menu-panel]', menu).dataset.hide = '-translate-x-full';
  const cart = $('[data-cart]');
  if (cart) $('[data-cart-panel]', cart).dataset.hide = 'translate-x-full';
  const search = $('[data-search]');
  if (search) $('[data-search-panel]', search).dataset.hide = '-translate-y-4';

  const menuPanel = menu ? createPanel(menu, 'menu') : null;
  const cartPanel = cart ? createPanel(cart, 'cart') : null;
  const searchPanel = search
    ? createPanel(search, 'search', { onOpen: () => setTimeout(() => $('[data-search-input]')?.focus(), 150) })
    : null;

  // Expose the cart panel so cart.js can open it after add-to-cart.
  window.SG.openCart = () => cartPanel && cartPanel.open();
  window.SG.closeCart = () => cartPanel && cartPanel.close();

  $$('[data-menu-open]').forEach((b) => b.addEventListener('click', () => {
    b.setAttribute('aria-expanded', 'true');
    menuPanel && menuPanel.open();
  }));
  $$('[data-cart-open]').forEach((b) => b.addEventListener('click', () => {
    cartPanel && cartPanel.open();
    window.SG.loadCart && window.SG.loadCart();
  }));
  $$('[data-search-open]').forEach((b) => b.addEventListener('click', () => searchPanel && searchPanel.open()));

  // Header elevation on scroll
  const header = $('[data-header]');
  if (header) {
    const onScroll = () => header.classList.toggle('shadow-soft', window.scrollY > 8);
    onScroll();
    window.addEventListener('scroll', onScroll, { passive: true });
  }

  initNewsletter();
}

function initNewsletter() {
  const form = $('[data-newsletter]');
  if (!form) return;
  form.addEventListener('submit', async (e) => {
    e.preventDefault();
    const btn = form.querySelector('button[type="submit"]');
    const email = form.querySelector('input[name="email"]').value.trim();
    if (!email) return;
    btn.disabled = true;
    const original = btn.textContent;
    btn.textContent = 'Subscribing…';
    const res = await postJSON('newsletter/subscribe', { email });
    toast(res.message || (res.success ? 'You are on the list.' : 'Please try again.'), res.success ? 'success' : 'error');
    if (res.success) form.reset();
    btn.disabled = false;
    btn.textContent = original;
  });
}
