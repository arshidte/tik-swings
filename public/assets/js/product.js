// Product detail interactions (§15, §22–§26). Vanilla, no gallery library.
import { $, $$, postJSON, getHTML, money } from './util.js';
import { addToCart } from './cart.js';

const root = $('[data-product-root]');
if (root) {
  const productId = root.dataset.productId;
  const hasVariants = root.dataset.hasVariants === '1';
  let currentVariant = parseInt(root.dataset.defaultVariant, 10) || null;

  // ---------- Gallery ----------
  const mainImg = $('[data-gallery-image]');
  $$('[data-gallery-thumb]').forEach((thumb) => {
    thumb.addEventListener('click', () => {
      mainImg.src = thumb.dataset.image;
      mainImg.alt = thumb.dataset.alt || '';
      $$('[data-gallery-thumb]').forEach((t) => {
        t.classList.toggle('ring-wood', t === thumb);
        t.classList.toggle('ring-transparent', t !== thumb);
        t.setAttribute('aria-selected', t === thumb ? 'true' : 'false');
      });
    });
  });

  // Lightbox / fullscreen — supports product images, and customer photos + videos.
  const lb = $('[data-lightbox]');
  const lbImg = $('[data-lightbox-image]');
  const lbVideo = $('[data-lightbox-video]');
  const lbCaption = $('[data-lightbox-caption]');

  const showLb = () => { lb.classList.remove('hidden'); lb.classList.add('flex'); document.body.style.overflow = 'hidden'; };
  const openImageLb = (src, alt, caption = '') => {
    if (lbVideo) { lbVideo.pause(); lbVideo.removeAttribute('src'); lbVideo.load(); lbVideo.classList.add('hidden'); }
    lbImg.src = src; lbImg.alt = alt || ''; lbImg.classList.remove('hidden');
    if (lbCaption) { lbCaption.textContent = caption; lbCaption.classList.toggle('hidden', !caption); }
    showLb();
  };
  const openVideoLb = (src, poster, caption = '') => {
    lbImg.classList.add('hidden'); lbImg.removeAttribute('src');
    if (lbVideo) { lbVideo.src = src; if (poster) lbVideo.poster = poster; lbVideo.classList.remove('hidden'); lbVideo.currentTime = 0; lbVideo.play().catch(() => {}); }
    if (lbCaption) { lbCaption.textContent = caption; lbCaption.classList.toggle('hidden', !caption); }
    showLb();
  };
  const openLb = () => openImageLb(mainImg.src, mainImg.alt);
  const closeLb = () => {
    lb.classList.add('hidden'); lb.classList.remove('flex'); document.body.style.overflow = '';
    if (lbVideo) { lbVideo.pause(); }
  };
  $('[data-gallery-zoom]')?.addEventListener('click', openLb);
  mainImg?.addEventListener('click', openLb);
  $('[data-lightbox-close]')?.addEventListener('click', closeLb);
  lb?.addEventListener('click', (e) => { if (e.target === lb) closeLb(); });
  document.addEventListener('keydown', (e) => { if (e.key === 'Escape' && !lb.classList.contains('hidden')) closeLb(); });

  // Customer gallery (user-uploaded media) — click via delegation so lazy-loaded
  // tiles work too, plus horizontal lazy-loading past the first 10 items.
  const strip = $('[data-gallery-strip]');
  if (strip) {
    strip.addEventListener('click', (e) => {
      const item = e.target.closest('[data-gallery-media]');
      if (!item || !strip.contains(item)) return;
      const { type, src, poster, caption } = item.dataset;
      if (type === 'video') openVideoLb(src, poster, caption);
      else openImageLb(src, caption || '', caption || '');
    });

    const sentinel = $('[data-gallery-sentinel]', strip);
    if (sentinel && 'IntersectionObserver' in window) {
      const productId = strip.dataset.productId;
      let loading = false;
      let done = false;
      const loadMore = async () => {
        if (loading || done) return;
        loading = true;
        const offset = parseInt(sentinel.dataset.offset, 10) || 0;
        try {
          const html = (await getHTML(`product/media/${productId}?offset=${offset}`)).trim();
          if (!html) { done = true; }
          else {
            sentinel.insertAdjacentHTML('beforebegin', html);
            const added = strip.querySelectorAll('[data-gallery-media]').length;
            sentinel.dataset.offset = String(offset + 10);
            if (html.match(/data-gallery-media/g)?.length < 10) done = true;
          }
        } catch (_) { done = true; }
        loading = false;
        if (done) { io.disconnect(); sentinel.remove(); }
      };
      const io = new IntersectionObserver((entries) => {
        if (entries.some((en) => en.isIntersecting)) loadMore();
      }, { root: strip, rootMargin: '0px 400px 0px 0px' });
      io.observe(sentinel);
    }
  }

  // ---------- Reviews: "Show more" ----------
  const moreBtn = $('[data-reviews-more]');
  if (moreBtn) {
    const grid = $('[data-reviews-grid]');
    moreBtn.addEventListener('click', async () => {
      const offset = parseInt(moreBtn.dataset.offset, 10) || 0;
      const total = parseInt(moreBtn.dataset.total, 10) || 0;
      moreBtn.disabled = true;
      try {
        const html = (await getHTML(`product/reviews/${moreBtn.dataset.productId}?offset=${offset}`)).trim();
        if (html) {
          const before = grid.children.length;
          grid.insertAdjacentHTML('beforeend', html);
          const next = offset + (grid.children.length - before);
          moreBtn.dataset.offset = String(next);
          const left = total - next;
          if (left <= 0) { moreBtn.parentElement.remove(); return; }
          $('[data-reviews-remaining]', moreBtn).textContent = `(${left} more)`;
        } else {
          moreBtn.parentElement.remove();
          return;
        }
      } catch (_) {
        moreBtn.textContent = 'Could not load reviews — try again';
      }
      moreBtn.disabled = false;
    });
  }

  // ---------- Quantity ----------
  const qtyInput = $('[data-qty-input]');
  const getQty = () => Math.max(1, parseInt(qtyInput.value, 10) || 1);
  $('[data-qty-inc]')?.addEventListener('click', () => { qtyInput.value = getQty() + 1; refreshAddons(); });
  $('[data-qty-dec]')?.addEventListener('click', () => { qtyInput.value = Math.max(1, getQty() - 1); refreshAddons(); });

  // ---------- Add-ons ("Complete your swing") ----------
  // Each add-on card owns its checkbox, optional variant chips and a JSON list
  // of its variants, so variant resolution happens locally (no round-trip).
  let mainPrice = parseFloat(root.dataset.basePrice) || 0;
  let mainInStock = !$('[data-pdp-add]')?.disabled;
  let unavailableText = mainInStock ? 'Out of stock' : ($('[data-pdp-add] [data-btn-label]')?.textContent.trim() || 'Currently Unavailable');
  const addonsBox = $('[data-addons]');
  const addonCards = addonsBox ? $$('[data-addon]', addonsBox) : [];

  function checkedAddons() {
    return addonCards.filter((c) => $('[data-addon-check]', c).checked);
  }

  function refreshCta() {
    const n = checkedAddons().length;
    $$('[data-pdp-add]').forEach((b) => {
      b.disabled = !mainInStock;
      const l = b.querySelector('[data-btn-label]');
      if (l) l.textContent = !mainInStock ? unavailableText : n ? `Add ${n + 1} items to Cart` : 'Add to Cart';
      else b.textContent = !mainInStock ? unavailableText : n ? `Add ${n + 1} items` : 'Add to Cart';
    });
    $$('[data-pdp-buy]').forEach((b) => { b.disabled = !mainInStock; });
  }

  function refreshAddons() {
    if (!addonsBox) { refreshCta(); return; }
    const qty = getQty();
    const picked = checkedAddons();
    const extra = picked.reduce((sum, c) => sum + (parseFloat(c.dataset.price) || 0), 0);
    const total = (mainPrice + extra) * qty;
    $('[data-addon-total]', addonsBox).textContent = money(total);
    $('[data-addon-summary-label]', addonsBox).textContent = picked.length
      ? `Swing + ${picked.length} add-on${picked.length > 1 ? 's' : ''}${qty > 1 ? ` × ${qty}` : ''}`
      : `Swing only${qty > 1 ? ` × ${qty}` : ''}`;
    $$('[data-sticky-price]').forEach((e) => (e.textContent = money(total)));
    const note = $('[data-sticky-addon-note]');
    if (note) note.textContent = picked.length ? `incl. ${picked.length} add-on${picked.length > 1 ? 's' : ''}` : '';
    refreshCta();
  }

  addonCards.forEach((card) => {
    const check = $('[data-addon-check]', card);
    const jsonEl = $('[data-addon-variants]', card);
    const variants = jsonEl ? JSON.parse(jsonEl.textContent || '[]') : [];
    const groups = $$('[data-addon-group]', card);
    const priceEl = $('[data-addon-price]', card);
    const imgEl = $('[data-addon-image]', card);

    check.addEventListener('change', refreshAddons);
    if (!variants.length) return;

    // A value is selectable only if some in-stock variant carries it.
    const available = new Set(variants.filter((v) => v.in_stock).flatMap((v) => v.values));
    $$('[data-addon-value]', card).forEach((b) => { b.disabled = !available.has(parseInt(b.dataset.valueId, 10)); });

    const selectedIds = () => groups
      .map((g) => $('[data-addon-value][data-selected="true"]', g))
      .filter(Boolean).map((b) => parseInt(b.dataset.valueId, 10));

    function paint(variant) {
      groups.forEach((g) => {
        let label = '';
        $$('[data-addon-value]', g).forEach((b) => {
          const on = variant.values.includes(parseInt(b.dataset.valueId, 10));
          b.dataset.selected = on ? 'true' : 'false';
          b.setAttribute('aria-pressed', on ? 'true' : 'false');
          if (on) label = b.dataset.valueLabel;
        });
        const lbl = $('[data-addon-selected]', g);
        if (lbl) lbl.textContent = label;
      });
      card.dataset.variantId = variant.id;
      card.dataset.price = variant.price;
      priceEl.textContent = '+' + money(variant.price);
      if (variant.image && imgEl) imgEl.src = variant.image;
    }

    function pick(clickedId) {
      const want = selectedIds();
      const inStock = variants.filter((v) => v.in_stock);
      // Exact match on every selected value, else the closest in-stock variant
      // that keeps the value just clicked (other groups adjust to fit).
      const exact = inStock.find((v) => want.length === v.values.length && want.every((id) => v.values.includes(id)));
      const best = exact || inStock
        .filter((v) => v.values.includes(clickedId))
        .sort((a, b) => b.values.filter((id) => want.includes(id)).length - a.values.filter((id) => want.includes(id)).length)[0];
      if (best) paint(best);
    }

    const initial = variants.find((v) => v.id === parseInt(card.dataset.variantId, 10)) || variants.find((v) => v.in_stock);
    if (initial) paint(initial);

    groups.forEach((g) => {
      $$('[data-addon-value]', g).forEach((btn) => {
        btn.addEventListener('click', () => {
          if (btn.disabled) return;
          $$('[data-addon-value]', g).forEach((b) => (b.dataset.selected = 'false'));
          btn.dataset.selected = 'true';
          pick(parseInt(btn.dataset.valueId, 10));
          // Choosing an option signals intent — tick the add-on for the shopper.
          if (!check.checked) check.checked = true;
          refreshAddons();
        });
      });
    });
  });
  refreshAddons();

  // ---------- Variant selection ----------
  const selected = {}; // attributeSlug -> valueId

  // Sticky bar subtitle: the options currently chosen, e.g. "Light Teak · Standard".
  function updateStickySummary() {
    const labels = $$('[data-option-group] [data-option-value][data-selected="true"]').map((b) => b.dataset.valueLabel);
    const el = $('[data-sticky-variant]');
    if (el) el.textContent = labels.join(' · ');
  }

  function updateVariant() {
    updateStickySummary();
    const values = Object.values(selected).filter(Boolean);
    if (!values.length) return;
    postJSON('product/variant', { product_id: productId, values }).then((res) => {
      const d = res.data || {};
      if (res.success && d.available) {
        currentVariant = d.variant_id;
        mainPrice = parseFloat(d.price) || 0;
        mainInStock = !!d.in_stock;
        unavailableText = 'Out of stock';
        $('[data-price]').textContent = d.price_display;
        $$('[data-sticky-price]').forEach((e) => (e.textContent = d.price_display));
        const cmp = $('[data-compare-price]');
        if (cmp) cmp.textContent = d.compare_display || '';
        const sku = $('[data-sku]');
        if (sku) sku.textContent = 'SKU: ' + d.sku;
        if (d.image && mainImg) mainImg.src = d.image;
      } else {
        mainInStock = false;
        unavailableText = 'Unavailable';
      }
      refreshAddons();
    });
  }

  $$('[data-option-group]').forEach((group) => {
    const attr = group.dataset.attribute;
    const buttons = $$('[data-option-value]', group);
    // preselect first value
    if (buttons[0]) {
      buttons[0].dataset.selected = 'true';
      selected[attr] = buttons[0].dataset.valueId;
      const lbl = group.querySelector('[data-option-selected]');
      if (lbl) lbl.textContent = buttons[0].dataset.valueLabel;
    }
    buttons.forEach((btn) => {
      btn.addEventListener('click', () => {
        buttons.forEach((b) => (b.dataset.selected = 'false'));
        btn.dataset.selected = 'true';
        selected[attr] = btn.dataset.valueId;
        const lbl = group.querySelector('[data-option-selected]');
        if (lbl) lbl.textContent = btn.dataset.valueLabel;
        updateVariant();
      });
    });
  });
  if (hasVariants) updateVariant();

  // ---------- Add / Buy ----------
  async function doAdd(button) {
    const addons = checkedAddons().map((c) => ({
      product_id: parseInt(c.dataset.productId, 10),
      variant_id: parseInt(c.dataset.variantId, 10) || null,
    }));
    return addToCart({ productId, variantId: currentVariant, quantity: getQty(), addons, button });
  }
  $$('[data-pdp-add]').forEach((b) => b.addEventListener('click', () => doAdd(b)));
  $$('[data-pdp-buy]').forEach((b) => b.addEventListener('click', async () => {
    const res = await doAdd(b);
    if (res && res.success) window.location.href = window.SG.baseUrl + 'checkout';
  }));

  // ---------- Accordions (§25, accessible) ----------
  $$('[data-accordion-trigger]').forEach((trigger) => {
    const panel = trigger.closest('div').parentElement.querySelector('[data-accordion-panel]');
    const icon = trigger.querySelector('[data-accordion-icon]');
    trigger.addEventListener('click', () => {
      const open = trigger.getAttribute('aria-expanded') === 'true';
      trigger.setAttribute('aria-expanded', open ? 'false' : 'true');
      panel.classList.toggle('grid-rows-[1fr]', !open);
      panel.classList.toggle('grid-rows-[0fr]', open);
      icon.classList.toggle('rotate-180', !open);
    });
  });

  // ---------- Sticky buy bar (§26) ----------
  // Shown whenever the main Add to Cart button is not fully on screen, so the
  // purchase action is always reachable without scrolling back up.
  const stickyBar = $('[data-sticky-bar]');
  const primaryAdd = $('[data-pdp-add]');   // first in the DOM = the main button
  const fab = $('[data-whatsapp-fab]');
  let ctaOffscreen = false, footerInView = false;
  function renderSticky() {
    const show = ctaOffscreen && !footerInView;   // never cover the footer
    stickyBar.classList.toggle('translate-y-full', !show);
    stickyBar.toggleAttribute('inert', !show);   // off-screen controls must not be tabbable
    if (fab) fab.classList.toggle('!bottom-20', show);   // keep the chat bubble clear of the bar
  }
  if (stickyBar && primaryAdd) {
    if ('IntersectionObserver' in window) {
      new IntersectionObserver((entries) => {
        entries.forEach((e) => { ctaOffscreen = !e.isIntersecting || e.intersectionRatio < 1; });
        renderSticky();
      }, { threshold: [0, 1] }).observe(primaryAdd);
      const footer = $('footer');
      if (footer) {
        new IntersectionObserver((entries) => {
          entries.forEach((e) => { footerInView = e.isIntersecting; });
          renderSticky();
        }).observe(footer);
      }
    } else {
      ctaOffscreen = true;
      renderSticky();
    }
  }
  updateStickySummary();
}
