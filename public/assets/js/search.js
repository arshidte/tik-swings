// Debounced search overlay (§21). Requests suggestions after the user pauses;
// never one request per keystroke.
import { $, getJSON, debounce, money, url } from './util.js';

function resultsMarkup(data, q) {
  let html = '';
  if (data.categories && data.categories.length) {
    html += '<p class="eyebrow mb-3">Collections</p><div class="flex flex-wrap gap-2 mb-6">';
    html += data.categories.map((c) =>
      `<a href="${url('category/' + c.slug)}" class="badge-soft hover:bg-wood hover:text-white transition-colors">${c.name}</a>`
    ).join('');
    html += '</div>';
  }
  if (data.products && data.products.length) {
    html += '<p class="eyebrow mb-3">Products</p><div class="grid sm:grid-cols-2 gap-3">';
    html += data.products.map((p) => `
      <a href="${p.url}" class="flex items-center gap-3 rounded-md p-2 hover:bg-sand transition-colors">
        <img src="${p.image}" alt="" width="56" height="56" class="w-14 h-14 rounded-md object-cover bg-sand shrink-0">
        <span class="min-w-0">
          <span class="block font-display text-[15px] leading-snug truncate">${p.name}</span>
          <span class="block text-sm text-muted tabular-nums">${money(p.price)}</span>
        </span>
      </a>`).join('');
    html += '</div>';
    html += `<a href="${url('search?q=' + encodeURIComponent(q))}" class="inline-flex items-center gap-1 mt-5 text-sm font-medium text-wood hover:text-wood-dark">See all results for “${q}”
      <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24"><path stroke-linecap="round" d="M5 12h14m-6-6 6 6-6 6"/></svg></a>`;
  }
  if (!html) {
    html = `<div class="py-10 text-center"><p class="font-display text-xl">We couldn't find that swing.</p><p class="text-muted mt-2">Try another search — like “teak”, “balcony” or “jhula”.</p></div>`;
  }
  return html;
}

export function initSearch() {
  const input = $('[data-search-input]');
  if (!input) return;
  const results = $('[data-search-results]');
  const def = $('[data-search-default]');

  const run = debounce(async (q) => {
    if (q.length < 2) {
      results.classList.add('hidden');
      def.classList.remove('hidden');
      return;
    }
    results.innerHTML = '<div class="py-8 text-center text-muted text-sm">Searching…</div>';
    def.classList.add('hidden');
    results.classList.remove('hidden');
    const res = await getJSON('search/suggest?q=' + encodeURIComponent(q));
    if (res.success) results.innerHTML = resultsMarkup(res.data, q);
  }, 300);

  input.addEventListener('input', (e) => run(e.target.value.trim()));

  // Popular term chips
  document.addEventListener('click', (e) => {
    const chip = e.target.closest('[data-search-term]');
    if (!chip) return;
    input.value = chip.getAttribute('data-search-term');
    input.focus();
    run(input.value.trim());
  });
}
