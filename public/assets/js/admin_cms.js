/* Admin CMS page editor.
 *
 * - Rich-text editor (contenteditable + execCommand; no dependencies) with
 *   toolbar, link/image dialogs, image paste/drop upload, HTML source mode.
 *   The server re-sanitizes everything on save (App\Services\HtmlSanitizer),
 *   so this script only has to make editing pleasant, not safe.
 * - FAQ builder (question/answer pairs) for the FAQ page.
 * - Slug autofill, character counters, search-result preview, schedule hint,
 *   cover preview and an unsaved-changes guard.
 */
(function () {
  'use strict';

  var form = document.querySelector('[data-cms-form]');
  if (!form) return;

  var $  = function (sel, ctx) { return (ctx || document).querySelector(sel); };
  var $$ = function (sel, ctx) { return Array.prototype.slice.call((ctx || document).querySelectorAll(sel)); };
  function esc(s) {
    return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) {
      return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
    });
  }
  function slugify(s) {
    return String(s).toLowerCase().normalize('NFKD').replace(/[\u0300-\u036f]/g, '')
      .replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, '');
  }

  var dirty = false, submitting = false;
  form.addEventListener('input', function () { dirty = true; });
  form.addEventListener('change', function () { dirty = true; });
  window.addEventListener('beforeunload', function (e) {
    if (dirty && !submitting) { e.preventDefault(); e.returnValue = ''; }
  });

  /* ------------------------------------------------------------ basics -- */
  var titleEl  = $('[data-title]', form);
  var slugEl   = $('[data-slug]', form);
  var typeEl   = $('[data-type]', form);
  var statusEl = $('[data-status]', form);
  var pubEl    = $('#published_at', form);
  var pubHelp  = $('[data-pub-help]', form);
  var prefixEl = $('[data-slug-prefix]', form);
  var authorWrap = $('[data-author-wrap]', form);
  var seoTitle = $('[data-seo-title]', form);
  var seoDesc  = $('[data-seo-desc]', form);
  var excerpt  = $('#excerpt', form);
  var isNew    = form.dataset.new === '1';
  var locked   = form.dataset.locked === '1';
  var siteBase = (window.SG && window.SG.baseUrl) || '/';
  var origin   = location.origin.replace(/\/$/, '');

  var slugTouched = !isNew || (slugEl && slugEl.value !== '');
  if (slugEl && !locked) {
    slugEl.addEventListener('input', function () { slugTouched = true; });
    slugEl.addEventListener('blur', function () { slugEl.value = slugify(slugEl.value); updateSnippet(); });
  }
  if (titleEl && slugEl && !locked) {
    titleEl.addEventListener('input', function () {
      if (!slugTouched) slugEl.value = slugify(titleEl.value);
    });
  }

  if (typeEl && !locked) {
    typeEl.addEventListener('change', function () {
      var isJournal = typeEl.value === 'journal';
      if (prefixEl) prefixEl.textContent = origin + siteBase.replace(origin, '').replace(/\/$/, '') + '/' + (isJournal ? 'journal/' : 'page/');
      if (authorWrap) authorWrap.classList.toggle('hidden', !isJournal);
      updateSnippet();
    });
  }

  // Character counters (soft = recommended length, hard = enforced).
  $$('[data-count-for]', form).forEach(function (el) {
    var input = document.getElementById(el.dataset.countFor);
    var max = parseInt(el.dataset.max, 10);
    var soft = el.dataset.soft === '1';
    function update() {
      var n = input.value.length;
      el.textContent = n + ' / ' + max + (soft ? ' recommended' : '');
      el.classList.toggle('text-danger', n > max);
      el.classList.toggle('text-muted', n <= max);
    }
    input.addEventListener('input', update);
    update();
  });

  // Search-result preview.
  function updateSnippet() {
    var u = $('[data-snippet-url]', form), t = $('[data-snippet-title]', form), d = $('[data-snippet-desc]', form);
    if (!u) return;
    var slug = slugEl ? (slugEl.value || slugify(titleEl.value) || 'your-page') : '';
    var base = prefixEl ? prefixEl.textContent : origin + '/';
    u.textContent = base + slug;
    t.textContent = (seoTitle.value || titleEl.value || 'Page title').trim();
    d.textContent = (seoDesc.value || excerpt.value || 'The meta description appears here. Add a summary or a meta description.').trim();
  }
  [titleEl, slugEl, seoTitle, seoDesc, excerpt].forEach(function (el) {
    if (el) el.addEventListener('input', updateSnippet);
  });
  updateSnippet();

  // Schedule hint.
  function updatePubHelp() {
    if (!pubHelp) return;
    var published = statusEl.value === 'published';
    var when = pubEl.value ? new Date(pubEl.value) : null;
    var fmt = function (d) { return d.toLocaleString(undefined, { dateStyle: 'medium', timeStyle: 'short' }); };
    var msg;
    if (!published) msg = when ? 'Draft — not visible. Planned date: ' + fmt(when) + '.' : 'Draft — not visible on the store until published.';
    else if (!when) msg = 'Goes live as soon as you save.';
    else if (when > new Date()) msg = 'Scheduled — goes live ' + fmt(when) + '.';
    else msg = 'Live since ' + fmt(when) + '.';
    pubHelp.textContent = msg;
  }
  statusEl.addEventListener('change', updatePubHelp);
  pubEl.addEventListener('input', updatePubHelp);
  updatePubHelp();

  // Cover preview.
  var coverFile = $('[data-cover-file]', form);
  var coverImg  = $('[data-cover-preview]', form);
  var coverRm   = $('[data-cover-remove]', form);
  var coverUrl  = null;
  if (coverFile && coverImg) {
    coverFile.addEventListener('change', function () {
      if (coverUrl) URL.revokeObjectURL(coverUrl);
      var f = coverFile.files && coverFile.files[0];
      coverUrl = f ? URL.createObjectURL(f) : null;
      coverImg.src = coverUrl || coverImg.dataset.original;
      if (f && coverRm) coverRm.checked = false;
    });
  }
  if (coverRm && coverImg) {
    coverRm.addEventListener('change', function () {
      coverImg.style.opacity = coverRm.checked ? '.35' : '';
    });
  }

  /* --------------------------------------------------------- FAQ builder -- */
  var faqList = $('[data-faq-list]', form);
  if (faqList) {
    var faqState = [];
    try { faqState = JSON.parse($('[data-faq-state]', form).textContent || '[]'); } catch (e) { faqState = []; }
    if (!faqState.length) faqState = [{ q: '', a: '' }];

    var syncFaq = function () {
      faqState = $$('[data-faq-row]', faqList).map(function (row) {
        return { q: $('[data-faq-q]', row).value, a: $('[data-faq-a]', row).value };
      });
    };
    var renderFaq = function (focusIndex) {
      faqList.innerHTML = faqState.map(function (item, i) {
        return '<li class="rounded-md border border-line p-3 space-y-2" data-faq-row>' +
          '<div class="flex items-start gap-2">' +
            '<div class="flex-1 min-w-0">' +
              '<label class="sr-only" for="faq-q-' + i + '">Question ' + (i + 1) + '</label>' +
              '<input id="faq-q-' + i + '" name="faq[' + i + '][q]" data-faq-q value="' + esc(item.q) + '" placeholder="Question" class="field-input font-medium">' +
            '</div>' +
            '<div class="flex flex-col">' +
              '<button type="button" data-faq-up class="px-1.5 text-muted hover:text-ink disabled:opacity-30" aria-label="Move question up"' + (i === 0 ? ' disabled' : '') + '>▲</button>' +
              '<button type="button" data-faq-down class="px-1.5 text-muted hover:text-ink disabled:opacity-30" aria-label="Move question down"' + (i === faqState.length - 1 ? ' disabled' : '') + '>▼</button>' +
            '</div>' +
            '<button type="button" data-faq-remove class="p-1.5 text-subtle hover:text-danger" aria-label="Remove question ' + (i + 1) + '">' +
              '<svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" d="M6 6l12 12M18 6 6 18"/></svg>' +
            '</button>' +
          '</div>' +
          '<label class="sr-only" for="faq-a-' + i + '">Answer ' + (i + 1) + '</label>' +
          '<textarea id="faq-a-' + i + '" name="faq[' + i + '][a]" data-faq-a rows="3" placeholder="Answer" class="field-input">' + esc(item.a) + '</textarea>' +
        '</li>';
      }).join('');
      if (typeof focusIndex === 'number') {
        var q = $('#faq-q-' + focusIndex);
        if (q) q.focus();
      }
    };
    form.addEventListener('click', function (e) {
      var row = e.target.closest('[data-faq-row]');
      if (e.target.closest('[data-faq-add]')) {
        syncFaq(); faqState.push({ q: '', a: '' }); renderFaq(faqState.length - 1); dirty = true; return;
      }
      if (!row) return;
      var i = $$('[data-faq-row]', faqList).indexOf(row);
      if (e.target.closest('[data-faq-remove]')) {
        syncFaq(); faqState.splice(i, 1); if (!faqState.length) faqState.push({ q: '', a: '' });
        renderFaq(Math.min(i, faqState.length - 1)); dirty = true;
      } else if (e.target.closest('[data-faq-up]') && i > 0) {
        syncFaq(); faqState.splice(i - 1, 0, faqState.splice(i, 1)[0]); renderFaq(i - 1); dirty = true;
      } else if (e.target.closest('[data-faq-down]') && i < faqState.length - 1) {
        syncFaq(); faqState.splice(i + 1, 0, faqState.splice(i, 1)[0]); renderFaq(i + 1); dirty = true;
      }
    });
    renderFaq();
  }

  /* ------------------------------------------------------ rich-text editor -- */
  var rte = $('[data-rte]', form);
  if (!rte) {
    form.addEventListener('submit', function () { submitting = true; });
    return;
  }

  var area     = $('[data-rte-area]', rte);
  var source   = $('[data-rte-source]', rte);
  var valueEl  = $('[data-rte-value]', rte);
  var toolbar  = $('[data-rte-toolbar]', rte);
  var blockSel = $('[data-rte-block]', rte);
  var statsEl  = $('[data-rte-stats]', form);
  var sourceMode = false;
  var savedRange = null;

  function csrf() { return (window.SG && window.SG.csrf) || {}; }

  // -- selection helpers
  function inArea(node) { return node && area.contains(node.nodeType === 1 ? node : node.parentNode); }
  function saveRange() {
    var sel = window.getSelection();
    savedRange = sel.rangeCount && inArea(sel.anchorNode) ? sel.getRangeAt(0).cloneRange() : null;
  }
  function restoreRange() {
    area.focus();
    if (!savedRange) return;
    var sel = window.getSelection();
    sel.removeAllRanges();
    sel.addRange(savedRange);
  }
  function closest(sel) {
    var s = window.getSelection();
    var n = s.anchorNode;
    if (!n || !inArea(n)) return null;
    n = n.nodeType === 1 ? n : n.parentNode;
    var hit = n.closest(sel);
    return hit && area.contains(hit) ? hit : null;
  }

  function exec(cmd, val) {
    area.focus();
    document.execCommand(cmd, false, val == null ? null : val);
    changed();
  }

  function changed() {
    dirty = true;
    updateStats();
    refreshToolbar();
  }

  function updateStats() {
    if (!statsEl) return;
    var text = (sourceMode ? '' : area.textContent).trim();
    var words = text ? text.split(/\s+/).length : 0;
    statsEl.textContent = words + ' word' + (words === 1 ? '' : 's') + (words > 0 ? ' · ' + Math.max(1, Math.round(words / 200)) + ' min read' : '');
  }

  function refreshToolbar() {
    if (sourceMode) return;
    var state = ['bold', 'italic', 'underline', 'strikeThrough', 'insertUnorderedList', 'insertOrderedList'];
    var focused = inArea(window.getSelection().anchorNode);
    $$('[data-cmd]', toolbar).forEach(function (b) {
      var c = b.dataset.cmd, on = false;
      if (focused) {
        if (state.indexOf(c) !== -1) { try { on = document.queryCommandState(c); } catch (e) { on = false; } }
        else if (c === 'blockquote') on = !!closest('blockquote');
        else if (c === 'link') on = !!closest('a');
      }
      b.setAttribute('aria-pressed', on ? 'true' : 'false');
    });
    if (focused) {
      var h = closest('h2,h3,h4');
      blockSel.value = h ? h.tagName.toLowerCase() : 'p';
    }
  }
  document.addEventListener('selectionchange', function () {
    if (inArea(window.getSelection().anchorNode)) refreshToolbar();
  });

  // -- content normalisation
  function ensureBlock() {
    if (!area.firstChild || (area.children.length === 0 && !area.textContent.trim())) area.innerHTML = '<p><br></p>';
  }
  area.addEventListener('focus', function () {
    try { document.execCommand('defaultParagraphSeparator', false, 'p'); } catch (e) { /* older engines */ }
    ensureBlock();
  });
  area.addEventListener('blur', function () {
    if (!area.textContent.trim() && !area.querySelector('img,hr')) area.innerHTML = '';
  });
  area.addEventListener('input', changed);
  area.addEventListener('keyup', refreshToolbar);
  area.addEventListener('mouseup', refreshToolbar);

  // Clicking an image selects it so Delete/Backspace removes it.
  area.addEventListener('click', function (e) {
    if (e.target.tagName === 'IMG') {
      var r = document.createRange();
      r.selectNode(e.target);
      var s = window.getSelection();
      s.removeAllRanges();
      s.addRange(r);
    }
  });

  // -- toolbar
  toolbar.addEventListener('mousedown', function (e) {
    // Keep the caret/selection in the editor when clicking buttons.
    if (e.target.closest('[data-cmd]')) e.preventDefault();
  });
  toolbar.addEventListener('click', function (e) {
    var btn = e.target.closest('[data-cmd]');
    if (!btn || btn.disabled) return;
    var cmd = btn.dataset.cmd;
    switch (cmd) {
      case 'hr':         exec('insertHorizontalRule'); break;
      case 'clear':      exec('removeFormat'); exec('unlink'); exec('formatBlock', '<p>'); break;
      case 'blockquote': exec('formatBlock', closest('blockquote') ? '<p>' : '<blockquote>'); break;
      case 'link':       openLinkDialog(); break;
      case 'image':      openImageDialog(); break;
      case 'source':     toggleSource(); break;
      default:           exec(cmd);
    }
  });
  blockSel.addEventListener('change', function () {
    restoreRange();
    exec('formatBlock', '<' + blockSel.value + '>');
  });
  blockSel.addEventListener('mousedown', saveRange);
  area.addEventListener('keydown', function (e) {
    if ((e.ctrlKey || e.metaKey) && !e.shiftKey && !e.altKey && e.key.toLowerCase() === 'k') {
      e.preventDefault();
      openLinkDialog();
    }
  });

  // -- HTML source mode
  function cleanForEditor(html) {
    // Parse inertly, then strip anything executable before it touches the live DOM.
    var doc = new DOMParser().parseFromString('<body>' + html + '</body>', 'text/html');
    $$('script,style,iframe,object,embed,link,meta,form,base,noscript', doc).forEach(function (n) { n.remove(); });
    $$('*', doc.body).forEach(function (el) {
      Array.prototype.slice.call(el.attributes).forEach(function (a) {
        var name = a.name.toLowerCase(), v = a.value.replace(/[\s\u0000-\u001f]+/g, '').toLowerCase();
        if (name.indexOf('on') === 0 || ((name === 'href' || name === 'src') && /^(javascript|data|vbscript):/.test(v))) el.removeAttribute(a.name);
      });
    });
    return doc.body.innerHTML;
  }
  function toggleSource() {
    var btn = $('[data-cmd="source"]', toolbar);
    if (!sourceMode) {
      source.value = area.innerHTML.replace(/<\/(p|h[2-4]|ul|ol|li|blockquote|figure|table|tr)>/g, '</$1>\n').trim();
      area.classList.add('hidden');
      source.classList.remove('hidden');
      source.focus();
      sourceMode = true;
    } else {
      area.innerHTML = cleanForEditor(source.value);
      source.classList.add('hidden');
      area.classList.remove('hidden');
      sourceMode = false;
      area.focus();
    }
    btn.setAttribute('aria-pressed', sourceMode ? 'true' : 'false');
    $$('[data-cmd],select', toolbar).forEach(function (el) {
      if (el !== btn) el.disabled = sourceMode;
    });
    updateStats();
  }

  // -- link dialog
  var linkDlg   = document.getElementById('rte-link-dialog');
  var linkForm  = $('[data-link-form]', linkDlg);
  var linkUrl   = $('[data-link-url]', linkDlg);
  var linkBlank = $('[data-link-blank]', linkDlg);

  function normalizeUrl(u) {
    u = u.trim();
    if (/^(javascript|data|vbscript):/i.test(u.replace(/[\s\u0000-\u001f]+/g, ''))) return '';
    if (/^([a-z][a-z0-9+.-]*:|\/|#)/i.test(u)) return u;          // scheme, /path, #anchor
    if (/^[\w-]+(\.[\w-]+)+(\/|$)/.test(u)) return 'https://' + u; // bare domain
    return u;
  }
  function openLinkDialog() {
    saveRange();
    var a = closest('a');
    linkUrl.value = a ? (a.getAttribute('href') || '') : '';
    linkBlank.checked = !!(a && a.getAttribute('target') === '_blank');
    linkDlg.showModal();
  }
  linkDlg.addEventListener('close', function () {
    if (linkDlg.returnValue !== 'ok') return;
    var url = normalizeUrl(linkUrl.value);
    if (!url) return;
    restoreRange();
    var existing = closest('a');
    if (existing) {
      existing.setAttribute('href', url);
      if (linkBlank.checked) existing.setAttribute('target', '_blank'); else existing.removeAttribute('target');
    } else if (savedRange && savedRange.collapsed) {
      exec('insertHTML', '<a href="' + esc(url) + '"' + (linkBlank.checked ? ' target="_blank"' : '') + '>' + esc(linkUrl.value.trim()) + '</a>');
    } else {
      document.execCommand('createLink', false, url);
      $$('a', area).forEach(function (a) {
        if (a.getAttribute('href') === url) {
          if (linkBlank.checked) a.setAttribute('target', '_blank'); else a.removeAttribute('target');
        }
      });
    }
    changed();
  });

  // -- image dialog + uploads
  var imgDlg   = document.getElementById('rte-image-dialog');
  var imgForm  = $('[data-image-form]', imgDlg);
  var imgFile  = $('[data-image-file]', imgDlg);
  var imgUrl   = $('[data-image-url]', imgDlg);
  var imgAlt   = $('[data-image-alt]', imgDlg);
  var imgErr   = $('[data-image-error]', imgDlg);
  var imgOk    = $('[data-image-ok]', imgDlg);

  function uploadImage(file) {
    var fd = new FormData();
    fd.append('image', file);
    var c = csrf();
    if (c.name) fd.append(c.name, c.hash);
    return fetch(siteBase + 'admin/pages/upload-image', {
      method: 'POST', body: fd,
      headers: { 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': c.hash || '' },
    }).then(function (r) { return r.json().catch(function () { return { success: false, message: 'Upload failed.' }; }); })
      .then(function (res) {
        if (res.data && res.data.csrf && window.SG) window.SG.csrf.hash = res.data.csrf;
        if (!res.success) throw new Error(res.message || 'Upload failed.');
        return res.data.url;
      });
  }
  function insertImage(url, alt) {
    restoreRange();
    exec('insertHTML', '<img src="' + esc(url) + '" alt="' + esc(alt || '') + '">');
  }

  function openImageDialog() {
    saveRange();
    imgFile.value = ''; imgUrl.value = ''; imgAlt.value = '';
    imgErr.classList.add('hidden');
    imgDlg.showModal();
  }
  imgForm.addEventListener('submit', function (e) {
    if (e.submitter && e.submitter.value !== 'ok') return; // Cancel closes natively
    e.preventDefault();
    imgErr.classList.add('hidden');
    var file = imgFile.files && imgFile.files[0];
    var url = imgUrl.value.trim();
    if (!file && !url) {
      imgErr.textContent = 'Upload an image or paste its address.';
      imgErr.classList.remove('hidden');
      return;
    }
    if (!file && !/^https?:\/\//i.test(url) && url.charAt(0) !== '/') {
      imgErr.textContent = 'The image address must start with https:// or /.';
      imgErr.classList.remove('hidden');
      return;
    }
    var alt = imgAlt.value;
    imgOk.disabled = true;
    imgOk.textContent = file ? 'Uploading…' : 'Inserting…';
    (file ? uploadImage(file) : Promise.resolve(url)).then(function (finalUrl) {
      imgDlg.close('ok');
      insertImage(finalUrl, alt);
    }).catch(function (err) {
      imgErr.textContent = err.message;
      imgErr.classList.remove('hidden');
    }).then(function () {
      imgOk.disabled = false;
      imgOk.textContent = 'Insert image';
    });
  });

  function dropImages(files) {
    var images = Array.prototype.filter.call(files || [], function (f) { return /^image\/(jpeg|png|webp)$/.test(f.type); });
    if (!images.length) return false;
    saveRange();
    var prev = statsEl ? statsEl.textContent : '';
    if (statsEl) statsEl.textContent = 'Uploading image…';
    images.reduce(function (chain, f) {
      return chain.then(function () {
        return uploadImage(f).then(function (url) { insertImage(url, ''); savedRange = null; });
      });
    }, Promise.resolve()).catch(function (err) {
      alert(err.message);
    }).then(function () { if (statsEl) { statsEl.textContent = prev; updateStats(); } });
    return true;
  }
  area.addEventListener('paste', function (e) {
    if (e.clipboardData && dropImages(e.clipboardData.files)) e.preventDefault();
  });
  area.addEventListener('dragover', function (e) {
    if (e.dataTransfer && Array.prototype.indexOf.call(e.dataTransfer.types || [], 'Files') !== -1) e.preventDefault();
  });
  area.addEventListener('drop', function (e) {
    if (!e.dataTransfer || !e.dataTransfer.files.length) return;
    // Place the caret where the file was dropped.
    var r = document.caretRangeFromPoint ? document.caretRangeFromPoint(e.clientX, e.clientY) : null;
    if (r) { var s = window.getSelection(); s.removeAllRanges(); s.addRange(r); }
    if (dropImages(e.dataTransfer.files)) e.preventDefault();
  });

  // -- load & submit
  area.innerHTML = cleanForEditor(area.innerHTML);
  updateStats();
  form.addEventListener('submit', function () {
    var html = sourceMode ? source.value : area.innerHTML;
    var empty = !area.textContent.trim() && !area.querySelector('img,hr') && !(sourceMode && source.value.trim());
    valueEl.value = empty ? '' : html;
    submitting = true;
  });
})();
