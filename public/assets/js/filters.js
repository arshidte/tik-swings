// Shop filtering (§19 / §20). Updates results via AJAX partial — never a full
// SPA, just progressive enhancement over the server-rendered grid.
import { $, $$, getHTML, url, debounce } from './util.js';

const results = $('[data-results]');
if (results) {
  const desktopForm = $('[data-filter-form]');
  const mobileForm = $('[data-filter-form-mobile]');
  const sortSelect = $('[data-sort]');
  const category = (desktopForm || mobileForm)?.dataset.category || '';

  function collect(form) {
    const params = new URLSearchParams();
    if (category) params.set('category', category);
    if (sortSelect && sortSelect.value) params.set('sort', sortSelect.value);
    if (form) {
      $$('[data-filter-input]', form).forEach((el) => {
        if (el.type === 'checkbox') {
          if (el.checked) params.append(el.name, el.value);
        } else if (el.value !== '') {
          params.set(el.name, el.value);
        }
      });
    }
    return params;
  }

  // Mirror one form's state into the other so desktop/mobile stay in sync.
  function syncForms(source) {
    const target = source === desktopForm ? mobileForm : desktopForm;
    if (!source || !target) return;
    const map = {};
    $$('[data-filter-input]', source).forEach((el) => {
      map[el.name + (el.type === 'checkbox' ? ':' + el.value : '')] = el.type === 'checkbox' ? el.checked : el.value;
    });
    $$('[data-filter-input]', target).forEach((el) => {
      const key = el.name + (el.type === 'checkbox' ? ':' + el.value : '');
      if (key in map) { if (el.type === 'checkbox') el.checked = map[key]; else el.value = map[key]; }
    });
  }

  async function apply(form, push = true) {
    const params = collect(form || desktopForm || mobileForm);
    results.classList.add('opacity-50', 'pointer-events-none');
    const html = await getHTML('shop/filter?' + params.toString());
    results.innerHTML = html;
    results.classList.remove('opacity-50', 'pointer-events-none');
    if (push) {
      const base = category ? url('category/' + category) : url('shop');
      const pretty = params.toString().replace(/(^|&)category=[^&]*/, '').replace(/^&/, '');
      history.pushState({}, '', pretty ? base + '?' + pretty : base);
    }
    results.scrollIntoView({ behavior: 'smooth', block: 'start' });
  }

  const debouncedApply = debounce((form) => apply(form), 400);

  // Desktop: live updates on change.
  desktopForm?.addEventListener('change', (e) => {
    if (e.target.matches('[data-filter-input]')) { syncForms(desktopForm); apply(desktopForm); }
  });
  desktopForm?.addEventListener('input', (e) => {
    if (e.target.matches('input[type="number"]')) { syncForms(desktopForm); debouncedApply(desktopForm); }
  });

  sortSelect?.addEventListener('change', () => apply(desktopForm || mobileForm));

  // Clear filters
  $$('[data-clear-filters]').forEach((b) => b.addEventListener('click', () => {
    [desktopForm, mobileForm].forEach((f) => f && $$('[data-filter-input]', f).forEach((el) => {
      if (el.type === 'checkbox') el.checked = false; else el.value = '';
    }));
    apply(desktopForm || mobileForm);
  }));

  // Pager links (delegated) — load via AJAX.
  results.addEventListener('click', (e) => {
    const link = e.target.closest('[data-page-link]');
    if (!link) return;
    e.preventDefault();
    const u = new URL(link.href);
    const page = u.searchParams.get('page') || '1';
    const params = collect(desktopForm || mobileForm);
    params.set('page', page);
    results.classList.add('opacity-50');
    getHTML('shop/filter?' + params.toString()).then((html) => {
      results.innerHTML = html;
      results.classList.remove('opacity-50');
      history.pushState({}, '', link.href);
      window.scrollTo({ top: results.getBoundingClientRect().top + window.scrollY - 120, behavior: 'smooth' });
    });
  });

  // ---- Mobile filter sheet ----
  const sheet = $('[data-filter-sheet]');
  if (sheet) {
    const overlay = $('[data-filter-overlay]', sheet);
    const panel = $('[data-filter-panel]', sheet);
    const open = () => {
      sheet.classList.remove('hidden');
      requestAnimationFrame(() => { overlay.style.opacity = '1'; panel.classList.remove('translate-y-full'); });
    };
    const close = () => {
      overlay.style.opacity = '0'; panel.classList.add('translate-y-full');
      setTimeout(() => sheet.classList.add('hidden'), 300);
    };
    $$('[data-filter-open]').forEach((b) => b.addEventListener('click', open));
    $$('[data-filter-close]', sheet).forEach((b) => b.addEventListener('click', close));
    overlay.addEventListener('click', close);
    $('[data-filter-apply]')?.addEventListener('click', () => { syncForms(mobileForm); apply(mobileForm); close(); });
    sheet.addEventListener('keydown', (e) => { if (e.key === 'Escape') close(); });
  }
}
