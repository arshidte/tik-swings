/* Admin product Options & Variants editor.
 * Reads an initial state blob rendered by the form, lets the admin define
 * options (reused by name) with optional colour swatches, mark which options
 * affect price, and set a +/- adjustment on each value. Variants are the
 * purchasable combinations; each carries its own editable price. The additive
 * helpers (base price + selected price-affecting adjustments) only seed the
 * price of newly generated rows — a price the admin has edited is pinned and
 * never auto-overwritten, and is persisted as-is on save.
 * All data is carried by the inputs' `name` attributes — see
 * Admin\Products::saveVariants.
 */
(function () {
  'use strict';

  var root = document.querySelector('[data-variant-editor]');
  if (!root) return;

  var attrList    = root.querySelector('[data-attr-list]');
  var variantRows = root.querySelector('[data-variant-rows]');
  var countEl     = root.querySelector('[data-variant-count]');
  var emptyEl     = root.querySelector('[data-variant-empty]');
  var skuInput    = document.getElementById('sku');
  var priceInput  = document.getElementById('price');

  var seq = 0;
  function uid(prefix) { return prefix + 'k' + (++seq) + (Date.now() % 10000); }

  function slug(s) {
    return String(s).toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, '');
  }
  function esc(s) {
    return String(s == null ? '' : s)
      .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;');
  }
  function isHex(s) { return /^#[0-9a-fA-F]{6}$/.test(String(s || '')); }
  function basePrice() { return parseFloat(priceInput && priceInput.value) || 0; }

  // --- state ---------------------------------------------------------------
  var state;
  try {
    state = JSON.parse(root.querySelector('[data-variant-state]').textContent || '{}');
  } catch (e) { state = {}; }
  state.attributes = state.attributes || [];
  state.variants   = state.variants || [];
  // Variants loaded with a stored price are "pinned" — never auto-overwritten
  // by the additive helpers; the admin edits them directly.
  state.variants.forEach(function (v) {
    if (v.priceEdited === undefined) v.priceEdited = v.price != null;
  });

  function variantByKey(key) {
    for (var i = 0; i < state.variants.length; i++) {
      if (state.variants[i].key === key) return state.variants[i];
    }
    return null;
  }
  function attrByKey(key) {
    for (var i = 0; i < state.attributes.length; i++) {
      if (state.attributes[i].key === key) return state.attributes[i];
    }
    return null;
  }
  function valueOf(attr, valueKey) {
    for (var i = 0; i < attr.values.length; i++) {
      if (attr.values[i].key === valueKey) return attr.values[i];
    }
    return null;
  }
  function usableAttrs() {
    return state.attributes.filter(function (a) {
      return (a.name || '').trim() && a.values.some(function (v) { return (v.label || '').trim(); });
    });
  }

  // Variant price = base + adjustments of selected price-affecting values.
  function priceFor(combo) {
    var total = basePrice();
    state.attributes.forEach(function (a) {
      if (!a.affectsPrice || !combo || !combo[a.key]) return;
      var val = valueOf(a, combo[a.key]);
      if (val) total += parseFloat(val.adjust) || 0;
    });
    return total;
  }

  // Pull current DOM values back into state before any re-render.
  function syncFromDom() {
    root.querySelectorAll('[data-attr]').forEach(function (block) {
      var attr = attrByKey(block.dataset.attr);
      if (!attr) return;
      attr.name = block.querySelector('[data-attr-name]').value;
      attr.affectsPrice = block.querySelector('[data-affects]').checked;
      block.querySelectorAll('[data-value]').forEach(function (row) {
        var val = valueOf(attr, row.dataset.value);
        if (!val) return;
        val.label  = row.querySelector('[data-value-label]').value;
        val.swatch = row.querySelector('[data-value-swatch]').value.trim();
        var bdg = row.querySelector('[data-value-badge]');
        if (bdg) val.badge = bdg.value;
        var adj = row.querySelector('[data-value-adjust]');
        if (adj) val.adjust = adj.value;
      });
    });

    var defRadio = root.querySelector('input[name="variant_default"]:checked');
    var defKey   = defRadio ? defRadio.value : null;
    root.querySelectorAll('[data-variant]').forEach(function (rowEl) {
      var v = null;
      for (var i = 0; i < state.variants.length; i++) {
        if (state.variants[i].key === rowEl.dataset.variant) { v = state.variants[i]; break; }
      }
      if (!v) return;
      v.sku       = rowEl.querySelector('[data-v-sku]').value;
      v.stock     = rowEl.querySelector('[data-v-stock]').value;
      v.status    = rowEl.querySelector('[data-v-status]').value;
      v.isDefault = defKey === v.key;
      var priceEl = rowEl.querySelector('[data-v-price]');
      if (priceEl) v.price = priceEl.value;
      v.combo = v.combo || {};
      rowEl.querySelectorAll('[data-combo]').forEach(function (sel) {
        v.combo[sel.dataset.combo] = sel.value;
      });
    });
  }

  function ensureDefault() {
    if (!state.variants.length) return;
    if (!state.variants.some(function (v) { return v.isDefault; })) {
      state.variants[0].isDefault = true;
    }
  }

  // --- rendering -----------------------------------------------------------
  function render() { renderAttributes(); renderVariants(); }

  function renderAttributes() {
    attrList.innerHTML = state.attributes.map(function (a) {
      var values = a.values.map(function (v) {
        var picker = isHex(v.swatch) ? v.swatch : '#8b5a2b';
        var adjust = a.affectsPrice
          ? '<input data-value-adjust type="number" step="0.01" name="attributes[' + esc(a.key) + '][values][' + esc(v.key) + '][adjust]" ' +
              'value="' + esc(parseFloat(v.adjust) ? v.adjust : '') + '" placeholder="+ ₹0" title="Price adjustment" ' +
              'class="field-input !py-1.5 !text-sm w-24 shrink-0">'
          : '';
        return '' +
          '<div class="flex items-center gap-2" data-value="' + esc(v.key) + '">' +
            '<input data-value-label name="attributes[' + esc(a.key) + '][values][' + esc(v.key) + '][label]" ' +
              'value="' + esc(v.label) + '" placeholder="Value e.g. Teak" class="field-input !py-1.5 !text-sm flex-1">' +
            '<input type="color" data-swatch-picker value="' + esc(picker) + '" title="Pick colour" ' +
              'class="w-9 h-9 rounded border border-line bg-transparent cursor-pointer shrink-0">' +
            '<input data-value-swatch name="attributes[' + esc(a.key) + '][values][' + esc(v.key) + '][swatch]" ' +
              'value="' + esc(v.swatch) + '" placeholder="#hex" class="field-input !py-1.5 !text-sm w-24">' +
            '<input data-value-badge name="attributes[' + esc(a.key) + '][values][' + esc(v.key) + '][badge]" ' +
              'value="' + esc(v.badge || '') + '" placeholder="Badge e.g. Most Popular" maxlength="40" ' +
              'title="Optional pill shown on the product page" class="field-input !py-1.5 !text-sm w-36 shrink-0">' +
            adjust +
            '<button type="button" data-remove-value class="text-muted hover:text-danger w-7 h-7 shrink-0" aria-label="Remove value">&times;</button>' +
          '</div>';
      }).join('');

      return '' +
        '<div class="rounded-md border border-line p-3 space-y-2.5" data-attr="' + esc(a.key) + '">' +
          '<div class="flex items-center gap-2 flex-wrap">' +
            '<input data-attr-name name="attributes[' + esc(a.key) + '][name]" list="attr-name-suggestions" ' +
              'value="' + esc(a.name) + '" placeholder="Option name e.g. Color" class="field-input !py-1.5 !text-sm font-medium flex-1 min-w-[8rem]">' +
            '<label class="flex items-center gap-1.5 text-xs text-muted whitespace-nowrap">' +
              '<input type="checkbox" data-affects name="attributes[' + esc(a.key) + '][affects_price]" value="1"' +
                (a.affectsPrice ? ' checked' : '') + ' class="w-4 h-4 rounded border-line text-wood focus:ring-wood/30"> Affects price' +
            '</label>' +
            '<button type="button" data-remove-attr class="text-muted hover:text-danger w-7 h-7 shrink-0" aria-label="Remove option">&times;</button>' +
          '</div>' +
          '<div class="space-y-2">' + values + '</div>' +
          '<button type="button" data-add-value class="text-xs text-wood hover:underline">+ Add value</button>' +
        '</div>';
    }).join('');
  }

  function renderVariants() {
    var attrs = usableAttrs();
    countEl.textContent = state.variants.length ? '(' + state.variants.length + ')' : '';
    emptyEl.style.display = state.variants.length ? 'none' : '';

    variantRows.innerHTML = state.variants.map(function (v) {
      var selects = attrs.map(function (a) {
        var opts = a.values.filter(function (val) { return (val.label || '').trim(); }).map(function (val) {
          var sel = v.combo && v.combo[a.key] === val.key ? ' selected' : '';
          return '<option value="' + esc(val.key) + '"' + sel + '>' + esc(val.label) + '</option>';
        }).join('');
        return '<select data-combo="' + esc(a.key) + '" name="variants[' + esc(v.key) + '][combo][' + esc(a.key) + ']" ' +
          'class="field-input !py-1.5 !text-sm" title="' + esc(a.name) + '">' + opts + '</select>';
      }).join('');

      return '' +
        '<div class="flex flex-wrap items-center gap-2 rounded-md border border-line p-2.5" data-variant="' + esc(v.key) + '">' +
          '<label class="flex items-center gap-1 text-xs text-muted shrink-0" title="Default variant">' +
            '<input type="radio" name="variant_default" value="' + esc(v.key) + '"' + (v.isDefault ? ' checked' : '') +
              ' class="w-4 h-4 text-wood focus:ring-wood/30"> Default' +
          '</label>' +
          selects +
          '<input data-v-sku name="variants[' + esc(v.key) + '][sku]" value="' + esc(v.sku) + '" placeholder="SKU" ' +
            'class="field-input !py-1.5 !text-sm w-36">' +
          '<input data-v-stock type="number" min="0" name="variants[' + esc(v.key) + '][stock]" value="' + esc(v.stock != null ? v.stock : 0) + '" ' +
            'placeholder="Stock" class="field-input !py-1.5 !text-sm w-20">' +
          '<label class="flex items-center gap-1 shrink-0" title="Variant price">' +
            '<span class="text-sm text-muted">₹</span>' +
            '<input data-v-price type="number" step="0.01" min="0" name="variants[' + esc(v.key) + '][price]" ' +
              'value="' + esc(v.price != null ? v.price : priceFor(v.combo)) + '" class="field-input !py-1.5 !text-sm w-28 tabular-nums">' +
          '</label>' +
          '<select data-v-status name="variants[' + esc(v.key) + '][status]" class="field-input !py-1.5 !text-sm w-24">' +
            '<option value="active"' + (v.status !== 'inactive' ? ' selected' : '') + '>Active</option>' +
            '<option value="inactive"' + (v.status === 'inactive' ? ' selected' : '') + '>Inactive</option>' +
          '</select>' +
          '<button type="button" data-remove-variant class="text-muted hover:text-danger w-7 h-7 shrink-0 ml-auto" aria-label="Remove variant">&times;</button>' +
        '</div>';
    }).join('');
  }

  // Re-seed prices from base + adjustments, but only for rows the admin hasn't
  // edited (pinned rows keep their explicit price). No structural change.
  function refreshPrices() {
    variantRows.querySelectorAll('[data-variant]').forEach(function (rowEl) {
      var v = variantByKey(rowEl.dataset.variant);
      if (!v || v.priceEdited) return;
      var combo = {};
      rowEl.querySelectorAll('[data-combo]').forEach(function (sel) { combo[sel.dataset.combo] = sel.value; });
      v.price = priceFor(combo);
      var el = rowEl.querySelector('[data-v-price]');
      if (el) el.value = v.price;
    });
  }

  // --- combinations --------------------------------------------------------
  function comboSignature(attrs, combo) {
    return attrs.map(function (a) { return a.key + ':' + (combo[a.key] || ''); }).join('|');
  }
  function autoSku(attrs, combo) {
    var base = (skuInput && skuInput.value.trim()) || 'SKU';
    var parts = attrs.map(function (a) {
      var val = valueOf(a, combo[a.key]);
      return val ? slug(val.label) : '';
    }).filter(Boolean);
    return parts.length ? base + '-' + parts.join('-') : base;
  }

  function generate() {
    var attrs = usableAttrs();
    if (!attrs.length) {
      alert('Add at least one option with a value before generating variants.');
      return;
    }
    var combos = [{}];
    attrs.forEach(function (a) {
      var vals = a.values.filter(function (v) { return (v.label || '').trim(); });
      var next = [];
      combos.forEach(function (partial) {
        vals.forEach(function (val) {
          var copy = Object.assign({}, partial);
          copy[a.key] = val.key;
          next.push(copy);
        });
      });
      combos = next;
    });

    var existing = {};
    state.variants.forEach(function (v) { existing[comboSignature(attrs, v.combo || {})] = v; });

    state.variants = combos.map(function (combo) {
      var sig = comboSignature(attrs, combo);
      if (existing[sig]) { existing[sig].combo = combo; return existing[sig]; }
      return { key: uid('x'), sku: autoSku(attrs, combo), stock: 0, status: 'active', isDefault: false, price: priceFor(combo), priceEdited: false, combo: combo };
    });
    ensureDefault();
    renderVariants();
  }

  function addManualVariant() {
    var attrs = usableAttrs();
    var combo = {};
    attrs.forEach(function (a) {
      var first = a.values.filter(function (v) { return (v.label || '').trim(); })[0];
      if (first) combo[a.key] = first.key;
    });
    state.variants.push({ key: uid('x'), sku: autoSku(attrs, combo), stock: 0, status: 'active', isDefault: !state.variants.length, price: priceFor(combo), priceEdited: false, combo: combo });
    renderVariants();
  }

  // --- events --------------------------------------------------------------
  root.addEventListener('click', function (e) {
    var t = e.target;

    if (t.closest('[data-add-attribute]')) {
      syncFromDom();
      state.attributes.push({ key: uid('a'), name: '', affectsPrice: false, values: [{ key: uid('v'), label: '', swatch: '', adjust: 0, badge: '' }] });
      renderAttributes();
      return;
    }
    if (t.closest('[data-add-value]')) {
      syncFromDom();
      var a = attrByKey(t.closest('[data-attr]').dataset.attr);
      if (a) { a.values.push({ key: uid('v'), label: '', swatch: '', adjust: 0, badge: '' }); renderAttributes(); }
      return;
    }
    if (t.closest('[data-remove-value]')) {
      syncFromDom();
      var vrow = t.closest('[data-value]');
      var attr = attrByKey(t.closest('[data-attr]').dataset.attr);
      if (attr) { attr.values = attr.values.filter(function (v) { return v.key !== vrow.dataset.value; }); render(); }
      return;
    }
    if (t.closest('[data-remove-attr]')) {
      syncFromDom();
      var key = t.closest('[data-attr]').dataset.attr;
      state.attributes = state.attributes.filter(function (a) { return a.key !== key; });
      render();
      return;
    }
    if (t.closest('[data-generate-variants]')) { syncFromDom(); generate(); return; }
    if (t.closest('[data-add-variant]'))       { syncFromDom(); addManualVariant(); return; }
    if (t.closest('[data-remove-variant]')) {
      syncFromDom();
      var vk = t.closest('[data-variant]').dataset.variant;
      state.variants = state.variants.filter(function (v) { return v.key !== vk; });
      ensureDefault();
      renderVariants();
      return;
    }
  });

  // Toggling "Affects price" shows/hides the adjustment inputs and changes prices.
  root.addEventListener('change', function (e) {
    if (e.target.matches('[data-affects]')) { syncFromDom(); render(); return; }
    if (e.target.matches('[data-combo]'))   { refreshPrices(); return; }
  });

  // Live updates: colour picker fills the hex field; adjustments update prices.
  root.addEventListener('input', function (e) {
    if (e.target.matches('[data-swatch-picker]')) {
      var box = e.target.parentNode.querySelector('[data-value-swatch]');
      if (box) box.value = e.target.value;
    }
    if (e.target.matches('[data-value-adjust]')) { syncFromDom(); refreshPrices(); }
    if (e.target.matches('[data-v-price]')) {
      var v = variantByKey(e.target.closest('[data-variant]').dataset.variant);
      if (v) { v.priceEdited = true; v.price = e.target.value; }
    }
  });

  // Base price changes ripple into every computed variant price.
  if (priceInput) priceInput.addEventListener('input', refreshPrices);

  ensureDefault();
  render();
})();
