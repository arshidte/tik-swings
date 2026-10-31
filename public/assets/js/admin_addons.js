/* Admin product Add-ons picker ("Complete your swing").
 * Search products by name/SKU, add them as add-ons, reorder, mark as
 * pre-selected, or remove. Rows are posted as addons[n][product_id] /
 * addons[n][preselected] in display order — see Admin\Products::saveAddons.
 */
(function () {
  'use strict';

  var root = document.querySelector('[data-addon-editor]');
  if (!root) return;

  var searchInput = root.querySelector('[data-addon-search]');
  var resultsEl   = root.querySelector('[data-addon-results]');
  var listEl      = root.querySelector('[data-addon-list]');
  var emptyEl     = root.querySelector('[data-addon-empty]');
  var excludeId   = parseInt(root.getAttribute('data-exclude'), 10) || 0;
  var baseUrl     = (window.SG && window.SG.baseUrl) || '/';

  var addons = [];
  try { addons = JSON.parse(root.querySelector('[data-addon-state]').textContent || '[]'); } catch (e) { addons = []; }

  function esc(s) {
    return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) {
      return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
    });
  }

  function statusBadge(a) {
    var tags = [];
    if (a.has_variants) tags.push('<span class="badge-soft text-[10px]">Has variants</span>');
    if (a.status && a.status !== 'active') tags.push('<span class="text-[10px] rounded px-1.5 py-0.5 bg-danger/10 text-danger">' + esc(a.status) + ' — hidden on store</span>');
    return tags.join(' ');
  }

  function render() {
    emptyEl.classList.toggle('hidden', addons.length > 0);
    listEl.innerHTML = addons.map(function (a, i) {
      return '' +
        '<li class="flex items-center gap-3 rounded-md border border-line p-2.5" data-addon-row="' + i + '">' +
          '<input type="hidden" name="addons[' + i + '][product_id]" value="' + a.id + '">' +
          '<img src="' + esc(a.image) + '" alt="" class="w-12 h-12 rounded object-cover bg-sand shrink-0">' +
          '<div class="flex-1 min-w-0">' +
            '<p class="text-sm font-medium truncate">' + esc(a.name) + '</p>' +
            '<p class="text-xs text-muted">' + esc(a.sku) + ' · ' + esc(a.price) + ' ' + statusBadge(a) + '</p>' +
          '</div>' +
          '<label class="flex items-center gap-1.5 text-xs whitespace-nowrap" title="Ticked by default on the product page">' +
            '<input type="checkbox" name="addons[' + i + '][preselected]" value="1" ' + (a.preselected ? 'checked' : '') + ' data-addon-pre class="w-4 h-4 rounded border-line text-wood focus:ring-wood/30"> Pre-select' +
          '</label>' +
          '<div class="flex flex-col">' +
            '<button type="button" class="px-1.5 text-muted hover:text-ink disabled:opacity-30" data-addon-up aria-label="Move up" ' + (i === 0 ? 'disabled' : '') + '>▲</button>' +
            '<button type="button" class="px-1.5 text-muted hover:text-ink disabled:opacity-30" data-addon-down aria-label="Move down" ' + (i === addons.length - 1 ? 'disabled' : '') + '>▼</button>' +
          '</div>' +
          '<button type="button" class="p-1.5 text-subtle hover:text-danger" data-addon-remove aria-label="Remove add-on">' +
            '<svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" d="M6 6l12 12M18 6 6 18"/></svg>' +
          '</button>' +
        '</li>';
    }).join('');
  }

  listEl.addEventListener('change', function (e) {
    var cb = e.target.closest('[data-addon-pre]');
    if (!cb) return;
    var i = parseInt(cb.closest('[data-addon-row]').getAttribute('data-addon-row'), 10);
    addons[i].preselected = cb.checked;
  });

  listEl.addEventListener('click', function (e) {
    var row = e.target.closest('[data-addon-row]');
    if (!row) return;
    var i = parseInt(row.getAttribute('data-addon-row'), 10);
    if (e.target.closest('[data-addon-remove]')) {
      addons.splice(i, 1);
    } else if (e.target.closest('[data-addon-up]') && i > 0) {
      addons.splice(i - 1, 0, addons.splice(i, 1)[0]);
    } else if (e.target.closest('[data-addon-down]') && i < addons.length - 1) {
      addons.splice(i + 1, 0, addons.splice(i, 1)[0]);
    } else {
      return;
    }
    render();
  });

  // ---- Search -------------------------------------------------------------
  var timer = null;
  var lastResults = [];

  function closeResults() {
    resultsEl.classList.add('hidden');
    searchInput.setAttribute('aria-expanded', 'false');
  }

  function showResults(items) {
    lastResults = items;
    var taken = addons.map(function (a) { return a.id; });
    if (!items.length) {
      resultsEl.innerHTML = '<li class="px-3 py-2.5 text-sm text-muted">No matching products.</li>';
    } else {
      resultsEl.innerHTML = items.map(function (it, idx) {
        var added = taken.indexOf(it.id) !== -1;
        return '<li role="option" aria-disabled="' + added + '" data-result="' + idx + '" class="flex items-center gap-3 px-3 py-2 text-sm ' +
          (added ? 'opacity-50 cursor-default' : 'cursor-pointer hover:bg-sand/60') + '">' +
          '<img src="' + esc(it.image) + '" alt="" class="w-9 h-9 rounded object-cover bg-sand shrink-0">' +
          '<span class="flex-1 min-w-0"><span class="block truncate font-medium">' + esc(it.name) + '</span>' +
          '<span class="block text-xs text-muted">' + esc(it.sku) + ' · ' + esc(it.price) + (it.status !== 'active' ? ' · ' + esc(it.status) : '') + '</span></span>' +
          (added ? '<span class="text-xs text-muted">Added</span>' : '<span class="text-xs text-wood font-medium">+ Add</span>') +
          '</li>';
      }).join('');
    }
    resultsEl.classList.remove('hidden');
    searchInput.setAttribute('aria-expanded', 'true');
  }

  function search(q) {
    var url = baseUrl + 'admin/products/search?q=' + encodeURIComponent(q) + '&exclude=' + excludeId;
    fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
      .then(function (r) { return r.json(); })
      .then(function (res) {
        if (searchInput.value.trim() !== q) return; // stale response
        showResults((res && res.data && res.data.items) || []);
      })
      .catch(function () { closeResults(); });
  }

  searchInput.addEventListener('input', function () {
    clearTimeout(timer);
    var q = searchInput.value.trim();
    if (q.length < 2) { closeResults(); return; }
    timer = setTimeout(function () { search(q); }, 250);
  });

  // Enter must not submit the product form.
  searchInput.addEventListener('keydown', function (e) {
    if (e.key === 'Enter') {
      e.preventDefault();
      var first = resultsEl.querySelector('[data-result][aria-disabled="false"]');
      if (first) first.click();
    } else if (e.key === 'Escape') {
      closeResults();
    }
  });

  resultsEl.addEventListener('click', function (e) {
    var li = e.target.closest('[data-result]');
    if (!li || li.getAttribute('aria-disabled') === 'true') return;
    var it = lastResults[parseInt(li.getAttribute('data-result'), 10)];
    if (!it) return;
    addons.push({
      id: it.id, name: it.name, sku: it.sku, price: it.price, status: it.status,
      has_variants: it.has_variants, image: it.image, preselected: false,
    });
    render();
    searchInput.value = '';
    closeResults();
    searchInput.focus();
  });

  document.addEventListener('click', function (e) {
    if (!root.contains(e.target)) closeResults();
  });

  render();
})();

