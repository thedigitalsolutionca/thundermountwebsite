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

  // Confirm destructive forms
  document.querySelectorAll('form[data-confirm]').forEach(function (form) {
    form.addEventListener('submit', function (event) {
      if (!window.confirm(form.getAttribute('data-confirm'))) {
        event.preventDefault();
      }
    });
  });

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

  // Flash auto-dismiss
  document.querySelectorAll('[data-flash-close]').forEach(function (button) {
    button.addEventListener('click', function () {
      var flash = button.closest('.flash');
      if (flash) {
        flash.remove();
      }
    });
  });
})();
