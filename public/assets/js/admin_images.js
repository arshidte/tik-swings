/* Admin product photo manager: pick the product-card default photo and the
 * hover photo, mark photos for removal, and see a live card preview.
 * Radios/checkboxes carry the data (card_image, hover_image, delete_images[]);
 * this script only keeps the choices consistent and the preview in sync.
 */
(function () {
  'use strict';
  var root = document.querySelector('[data-image-manager]');
  if (!root) return;

  var tiles = Array.prototype.slice.call(root.querySelectorAll('[data-img-tile]'));
  if (!tiles.length) return;
  var prevCard = root.querySelector('[data-prev-card]');
  var prevHover = root.querySelector('[data-prev-hover]');
  var note = root.querySelector('[data-prev-note]');
  var noneRadio = root.querySelector('[data-none]');

  function radio(tile, kind) { return tile.querySelector('[data-pick="' + kind + '"]'); }
  function removed(tile) { return tile.querySelector('[data-remove]').checked; }
  function selected(kind) {
    return tiles.filter(function (t) { return radio(t, kind).checked && !removed(t); })[0] || null;
  }

  function sync(changed) {
    var card = selected('card');
    var hover = selected('hover');

    // A removed photo can't be the card or hover photo: fall back sensibly.
    if (!card) {
      card = tiles.filter(function (t) { return !removed(t); })[0] || null;
      if (card) radio(card, 'card').checked = true;
    }
    // The hover photo must differ from the card photo.
    if (hover && card && hover === card) {
      // The most recent action wins: picking a photo as hover clears it as card's twin.
      if (changed === 'card') { noneRadio.checked = true; hover = null; }
      else { noneRadio.checked = true; hover = null; }
    }
    if (!hover) noneRadio.checked = true;

    tiles.forEach(function (t) {
      var gone = removed(t);
      t.style.opacity = gone ? '.4' : '';
      radio(t, 'card').disabled = gone;
      radio(t, 'hover').disabled = gone || t === card;
      t.querySelector('[data-badge-card]').classList.toggle('hidden', t !== card);
      t.querySelector('[data-badge-hover]').classList.toggle('hidden', t !== hover);
    });

    if (prevCard) prevCard.src = card ? card.dataset.src : prevCard.src;
    if (prevHover) {
      prevHover.classList.toggle('hidden', !hover);
      if (hover) prevHover.src = hover.dataset.src;
    }
    if (note) {
      note.textContent = !card ? 'All photos are marked for removal.'
        : hover ? 'Shoppers see the card photo; hovering swaps to the hover photo.'
        : 'No hover photo — the card photo stays on hover.';
    }
  }

  root.addEventListener('change', function (e) {
    var t = e.target;
    if (!t.matches('[data-pick],[data-remove]')) return;
    sync(t.dataset.pick || 'remove');
  });
  sync();
})();
