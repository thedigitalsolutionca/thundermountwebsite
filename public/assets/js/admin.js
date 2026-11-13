/**
 * Thunder Mounts admin helpers.
 */
(function () {
  'use strict';

  // Sidebar
  var side = document.getElementById('admin-side');
  var backdrop = document.querySelector('.admin-backdrop');
  function toggleSide(open) {
    if (!side) {
      return;
    }
    side.classList.toggle('open', open);
    if (backdrop) {
      backdrop.hidden = !open;
    }
    document.body.classList.toggle('no-scroll', open);
  }
  document.querySelectorAll('[data-admin-menu]').forEach(function (button) {
    button.addEventListener('click', function () {
      toggleSide(!side.classList.contains('open'));
    });
  });
  if (backdrop) {
    backdrop.addEventListener('click', function () { toggleSide(false); });
  }

  // Destructive-form confirmation is handled by the shared centred dialog
  // in app.js ([data-confirm] on document, capture phase).

  // Inventory: only one of delta / set-count is active.
  document.querySelectorAll('.admin-adjust').forEach(function (form) {
    var mode = form.querySelector('select[name="mode"]');
    var delta = form.querySelector('input[name="delta"]');
    var quantity = form.querySelector('input[name="quantity"]');
    if (!mode || !delta || !quantity) {
      return;
    }
    function sync() {
      var setMode = mode.value === 'set';
      delta.disabled = setMode;
      quantity.disabled = !setMode;
      delta.style.opacity = setMode ? '.45' : '';
      quantity.style.opacity = setMode ? '' : '.45';
      delta.placeholder = setMode ? '—' : '±';
      quantity.placeholder = setMode ? 'Qty' : '—';
    }
    mode.addEventListener('change', sync);
    sync();
  });

  // Flash dismissal is handled by app.js ([data-flash-close]).
})();

// Select only visible cleanup records; selections never carry across pages.
(function () {
  var toggle = document.querySelector('[data-select-records]');
  if (!toggle) return;
  var form = toggle.closest('form');
  var boxes = Array.from(form.querySelectorAll('input[name="ids[]"]'));
  toggle.addEventListener('change', function () { boxes.forEach(function (box) { box.checked = toggle.checked; }); });
  boxes.forEach(function (box) { box.addEventListener('change', function () {
    var count = boxes.filter(function (item) { return item.checked; }).length;
    toggle.checked = count === boxes.length;
    toggle.indeterminate = count > 0 && count < boxes.length;
  }); });
})();

// Gallery & Portfolio media mode switcher (Photo / Upload Video / YouTube & Vimeo)
(function () {
  var form = document.querySelector('[data-gallery-form]');
  if (!form) return;
  var radios = Array.prototype.slice.call(form.querySelectorAll('[data-gallery-mode]'));
  var panels = {
    image: form.querySelector('[data-gallery-panel="image"]'),
    video_upload: form.querySelector('[data-gallery-panel="video_upload"]'),
    video_url: form.querySelector('[data-gallery-panel="video_url"]'),
    poster: form.querySelector('[data-gallery-panel="poster"]')
  };
  function syncMode() {
    var selected = 'image';
    radios.forEach(function (radio) {
      var pill = radio.closest('.media-mode-pill');
      if (radio.checked) {
        selected = radio.getAttribute('data-gallery-mode') || 'image';
      }
      if (pill) {
        pill.classList.toggle('active', radio.checked);
      }
    });
    if (panels.image) panels.image.hidden = selected !== 'image';
    if (panels.video_upload) panels.video_upload.hidden = selected !== 'video_upload';
    if (panels.video_url) panels.video_url.hidden = selected !== 'video_url';
    if (panels.poster) panels.poster.hidden = selected === 'image';
  }
  radios.forEach(function (radio) {
    radio.addEventListener('change', syncMode);
  });
  syncMode();
})();
