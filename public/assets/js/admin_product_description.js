/* Admin product rich-text fields (Description, Craftsmanship, Returns,
 * Care instructions).
 *
 * A lean rich-text editor (contenteditable + execCommand; no dependencies):
 * bold/italic/underline/strike, headings, lists, links, clear formatting and
 * an HTML source mode. Modeled on the CMS page editor (admin_cms.js) but
 * without its image upload, FAQ builder and page-specific fields, which
 * don't apply here. Every [data-rte] block on the page gets its own
 * instance; they share the one link dialog in the page.
 *
 * The server re-sanitizes everything on save (App\Services\HtmlSanitizer),
 * so this script only has to make editing pleasant, not safe.
 */
(function () {
  'use strict';

  var $  = function (sel, ctx) { return (ctx || document).querySelector(sel); };
  var $$ = function (sel, ctx) { return Array.prototype.slice.call((ctx || document).querySelectorAll(sel)); };
  function esc(s) {
    return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) {
      return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
    });
  }

  var linkDlg = document.getElementById('rte-link-dialog');

  function initRte(rte) {
    var form     = rte.closest('form');
    var area     = $('[data-rte-area]', rte);
    var source   = $('[data-rte-source]', rte);
    var valueEl  = $('[data-rte-value]', rte);
    var toolbar  = $('[data-rte-toolbar]', rte);
    var blockSel = $('[data-rte-block]', rte);
    var sourceMode = false;
    var savedRange = null;

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

    function changed() { refreshToolbar(); }

    function refreshToolbar() {
      if (sourceMode) return;
      var state = ['bold', 'italic', 'underline', 'strikeThrough', 'insertUnorderedList', 'insertOrderedList'];
      var focused = inArea(window.getSelection().anchorNode);
      $$('[data-cmd]', toolbar).forEach(function (b) {
        var c = b.dataset.cmd, on = false;
        if (focused) {
          if (state.indexOf(c) !== -1) { try { on = document.queryCommandState(c); } catch (e) { on = false; } }
          else if (c === 'link') on = !!closest('a');
        }
        b.setAttribute('aria-pressed', on ? 'true' : 'false');
      });
      if (focused && blockSel) {
        var h = closest('h3,h4');
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
      if (!area.textContent.trim()) area.innerHTML = '';
    });
    area.addEventListener('input', changed);
    area.addEventListener('keyup', refreshToolbar);
    area.addEventListener('mouseup', refreshToolbar);

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
        case 'clear':  exec('removeFormat'); exec('unlink'); exec('formatBlock', '<p>'); break;
        case 'link':   openLinkDialog(); break;
        case 'source': toggleSource(); break;
        default:       exec(cmd);
      }
    });
    if (blockSel) {
      blockSel.addEventListener('change', function () {
        restoreRange();
        exec('formatBlock', '<' + blockSel.value + '>');
      });
      blockSel.addEventListener('mousedown', saveRange);
    }
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
        source.value = area.innerHTML.replace(/<\/(p|h[3-4]|ul|ol|li)>/g, '</$1>\n').trim();
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
    }

    // -- link dialog (shared element; this instance's state while it's open)
    function openLinkDialog() {
      if (!linkDlg) return;
      saveRange();
      var a = closest('a');
      $('[data-link-url]', linkDlg).value = a ? (a.getAttribute('href') || '') : '';
      $('[data-link-blank]', linkDlg).checked = !!(a && a.getAttribute('target') === '_blank');
      linkDlg.dataset.activeFor = rte.id || '';
      linkDlg._applyLink = function (url, blank) {
        restoreRange();
        var existing = closest('a');
        if (existing) {
          existing.setAttribute('href', url);
          if (blank) existing.setAttribute('target', '_blank'); else existing.removeAttribute('target');
        } else if (savedRange && savedRange.collapsed) {
          exec('insertHTML', '<a href="' + esc(url) + '"' + (blank ? ' target="_blank"' : '') + '>' + esc($('[data-link-url]', linkDlg).value.trim()) + '</a>');
        } else {
          document.execCommand('createLink', false, url);
          $$('a', area).forEach(function (a) {
            if (a.getAttribute('href') === url) {
              if (blank) a.setAttribute('target', '_blank'); else a.removeAttribute('target');
            }
          });
        }
        changed();
      };
      linkDlg.showModal();
    }

    // -- load & submit
    area.innerHTML = cleanForEditor(area.innerHTML);
    if (form) {
      form.addEventListener('submit', function () {
        var html = sourceMode ? source.value : area.innerHTML;
        var empty = !area.textContent.trim() && !(sourceMode && source.value.trim());
        valueEl.value = empty ? '' : html;
      });
    }
  }

  function normalizeUrl(u) {
    u = u.trim();
    if (/^(javascript|data|vbscript):/i.test(u.replace(/[\s\u0000-\u001f]+/g, ''))) return '';
    if (/^([a-z][a-z0-9+.-]*:|\/|#)/i.test(u)) return u;          // scheme, /path, #anchor
    if (/^[\w-]+(\.[\w-]+)+(\/|$)/.test(u)) return 'https://' + u; // bare domain
    return u;
  }
  if (linkDlg) {
    linkDlg.addEventListener('close', function () {
      if (linkDlg.returnValue !== 'ok') return;
      var url = normalizeUrl($('[data-link-url]', linkDlg).value);
      if (!url || !linkDlg._applyLink) return;
      linkDlg._applyLink(url, $('[data-link-blank]', linkDlg).checked);
    });
  }

  $$('[data-rte]').forEach(initRte);
})();
