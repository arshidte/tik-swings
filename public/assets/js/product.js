// Product detail interactions (§15, §22–§26). Vanilla, no gallery library.
import { $, $$, postJSON, getHTML } from './util.js';
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

  // ---------- Quantity ----------
  const qtyInput = $('[data-qty-input]');
  const getQty = () => Math.max(1, parseInt(qtyInput.value, 10) || 1);
  $('[data-qty-inc]')?.addEventListener('click', () => { qtyInput.value = getQty() + 1; });
  $('[data-qty-dec]')?.addEventListener('click', () => { qtyInput.value = Math.max(1, getQty() - 1); });

  // ---------- Variant selection ----------
  const selected = {}; // attributeSlug -> valueId
  function updateVariant() {
    const values = Object.values(selected).filter(Boolean);
    if (!values.length) return;
    postJSON('product/variant', { product_id: productId, values }).then((res) => {
      const d = res.data || {};
      const addBtns = $$('[data-pdp-add]');
      if (res.success && d.available) {
        currentVariant = d.variant_id;
        $('[data-price]').textContent = d.price_display;
        $$('[data-sticky-price]').forEach((e) => (e.textContent = d.price_display));
        const cmp = $('[data-compare-price]');
        if (cmp) cmp.textContent = d.compare_display || '';
        const sku = $('[data-sku]');
        if (sku) sku.textContent = 'SKU: ' + d.sku;
        if (d.image && mainImg) mainImg.src = d.image;
        addBtns.forEach((b) => {
          b.disabled = !d.in_stock;
          const l = b.querySelector('[data-btn-label]');
          if (l) l.textContent = d.in_stock ? 'Add to Cart' : 'Out of stock';
        });
      } else {
        addBtns.forEach((b) => { b.disabled = true; });
      }
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
    return addToCart({ productId, variantId: currentVariant, quantity: getQty(), button });
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

  // ---------- Mobile sticky bar (§26) ----------
  const stickyBar = $('[data-sticky-bar]');
  const primaryAdd = $('[data-pdp-add]');
  if (stickyBar && primaryAdd && 'IntersectionObserver' in window) {
    const io = new IntersectionObserver((entries) => {
      entries.forEach((e) => {
        stickyBar.classList.toggle('translate-y-full', e.isIntersecting);
      });
    }, { rootMargin: '0px 0px -80% 0px' });
    io.observe(primaryAdd);
  }
}
