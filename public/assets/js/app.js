/**
 * Thunder Mounts storefront interactions.
 * No frameworks, no build step.
 */
(function () {
  'use strict';

  var TM = window.TM || {};
  var base = TM.base || '/';

  function $(selector, scope) {
    return (scope || document).querySelector(selector);
  }

  function $$(selector, scope) {
    return Array.prototype.slice.call((scope || document).querySelectorAll(selector));
  }

  function url(path) {
    return base.replace(/\/$/, '') + path;
  }

  function escapeHtml(value) {
    return String(value == null ? '' : value).replace(/[&<>"']/g, function (char) {
      return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[char];
    });
  }

  function money(cents) {
    return new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' }).format(cents / 100);
  }

  function request(path, options) {
    options = options || {};
    options.credentials = 'same-origin';
    options.headers = Object.assign({ 'X-Requested-With': 'XMLHttpRequest', Accept: 'application/json' }, options.headers || {});
    return fetch(url(path), options).then(function (response) {
      return response.json().catch(function () {
        return { ok: false, message: 'Unexpected response. Please try again.' };
      }).then(function (payload) {
        if (!response.ok && payload.ok !== false) {
          return { ok: false, message: payload.message || 'The request failed (' + response.status + ').' };
        }
        return payload;
      });
    });
  }

  function toast(message, type) {
    var wrap = $('[data-toast-wrap]');
    if (!wrap) {
      return;
    }
    var node = document.createElement('div');
    var kind = ['success', 'error', 'info'].indexOf(type) >= 0 ? type : 'info';
    node.className = 'toast flash flash-' + kind;
    node.setAttribute('role', kind === 'error' ? 'alert' : 'status');
    node.innerHTML = '<div class="flash-copy"><strong>' + (kind === 'error' ? 'Please check' : kind === 'success' ? 'Success' : 'Notice') + '</strong><span>' + escapeHtml(message) + '</span></div><button class="flash-close" type="button" aria-label="Dismiss">&times;</button>';
    node.querySelector('button').addEventListener('click', function () {
      node.remove();
    });
    wrap.appendChild(node);
    window.setTimeout(function () {
      if (node.isConnected) {
        node.classList.add('out');
        window.setTimeout(function () { node.remove(); }, 250);
      }
    }, 4500);
  }

  function setCartCount(count) {
    $$('[data-cart-count]').forEach(function (node) {
      node.textContent = count;
      node.hidden = count <= 0;
    });
    $$('[data-cart-count-label]').forEach(function (node) {
      node.textContent = '(' + count + ')';
    });
    if (TM) {
      TM.cartCount = count;
    }
  }

  function refreshDrawer() {
    return request('/cart/drawer').then(function (payload) {
      if (!payload || payload.html === undefined) {
        return;
      }
      var body = $('[data-cart-drawer-body]');
      if (body) {
        body.innerHTML = payload.html;
      }
      var subtotal = $('[data-cart-subtotal]');
      if (subtotal) {
        subtotal.textContent = payload.subtotal;
      }
      setCartCount(payload.count);
    });
  }

  // ---------------------------------------------------------------------
  // Overlays share one scroll lock so closing a dialog never unlocks an open cart.
  // ---------------------------------------------------------------------
  function syncScrollLock() {
    document.body.classList.toggle('no-scroll',
      (drawer && drawer.classList.contains('open')) ||
      (searchModal && searchModal.classList.contains('open')) ||
      (quickModal && quickModal.classList.contains('open')) ||
      (mobileNav && mobileNav.classList.contains('open')));
  }

  // Cart drawer
  var drawer = $('#cart-drawer');

  function openDrawer() {
    if (!drawer) {
      return;
    }
    drawer.classList.add('open');
    drawer.setAttribute('aria-hidden', 'false');
    syncScrollLock();
    refreshDrawer();
    var close = $('[data-close-cart]', drawer);
    if (close) {
      window.setTimeout(function () { close.focus(); }, 30);
    }
  }

  function closeDrawer() {
    if (!drawer) {
      return;
    }
    drawer.classList.remove('open');
    drawer.setAttribute('aria-hidden', 'true');
    syncScrollLock();
  }

  $$('[data-open-cart]').forEach(function (button) {
    button.addEventListener('click', function (event) {
      event.preventDefault();
      openDrawer();
    });
  });
  $$('[data-close-cart]').forEach(function (button) {
    button.addEventListener('click', closeDrawer);
  });

  // Add to cart — delegated so quick-view and freshly rendered forms work.
  document.addEventListener('submit', function (event) {
    var form = event.target.closest ? event.target.closest('form[data-add-to-cart]') : null;
    if (!form) {
      return;
    }
    event.preventDefault();
    var submit = form.querySelector('button[type="submit"]');
    var original = submit ? submit.innerHTML : '';
    if (submit) {
      submit.disabled = true;
      submit.textContent = 'Adding…';
    }
    request('/cart/add', { method: 'POST', body: new FormData(form) }).then(function (payload) {
      if (!payload.ok) {
        toast(payload.message || 'Could not add that item.', 'error');
        return;
      }
      setCartCount(payload.count);
      if (quickModal && quickModal.classList.contains('open')) {
        closeQuickView(false);
      }
      openDrawer();
      toast(payload.message || 'Added to your cart.', 'success');
    }).catch(function () {
      toast('Network error. Please try again.', 'error');
    }).finally(function () {
      if (submit) {
        submit.disabled = false;
        submit.innerHTML = original;
      }
    });
  });

  // Cart quantity auto-update
  $$('form[data-cart-update]').forEach(function (form) {
    var input = form.querySelector('input[name="qty"]');
    if (!input) {
      return;
    }
    var timer;
    function submit() {
      var data = new FormData(form);
      request('/cart/update', { method: 'POST', body: data }).then(function (payload) {
        if (!payload.ok) {
          toast(payload.message || 'Could not update the quantity.', 'error');
          return;
        }
        // Re-render from the server: discounts, line totals, counts and availability
        // can all change with quantity. The response flashes the success message.
        window.location.reload();
      }).catch(function () {
        toast('Network error. Please try again.', 'error');
      });
    }
    input.addEventListener('change', function () {
      window.clearTimeout(timer);
      timer = window.setTimeout(submit, 250);
    });
  });

  // Quantity steppers
  $$('[data-qty]').forEach(function (wrap) {
    var input = wrap.querySelector('input[type="number"]');
    if (!input) {
      return;
    }
    var dec = wrap.querySelector('[data-qty-dec]');
    var inc = wrap.querySelector('[data-qty-inc]');
    if (dec) {
      dec.addEventListener('click', function () {
        var min = parseInt(input.min || '1', 10);
        input.value = Math.max(min, (parseInt(input.value, 10) || min) - 1);
        input.dispatchEvent(new Event('change', { bubbles: true }));
      });
    }
    if (inc) {
      inc.addEventListener('click', function () {
        var max = parseInt(input.max || '99', 10);
        input.value = Math.min(max, (parseInt(input.value, 10) || 1) + 1);
        input.dispatchEvent(new Event('change', { bubbles: true }));
      });
    }
  });

  // ---------------------------------------------------------------------
  // Mobile nav
  // ---------------------------------------------------------------------
  var menuToggle = $('[data-menu-toggle]');
  var mobileNav = $('#mobile-nav');
  if (menuToggle && mobileNav) {
    menuToggle.addEventListener('click', function () {
      var open = mobileNav.classList.toggle('open');
      menuToggle.setAttribute('aria-expanded', open ? 'true' : 'false');
      syncScrollLock();
    });
  }

  // ---------------------------------------------------------------------
  // Search
  // ---------------------------------------------------------------------
  var searchModal = $('#search-modal');
  var searchInput = $('[data-search-input]');
  var searchResults = $('[data-search-results]');
  var searchTimer;
  var searchReturnFocus = null;

  function openSearch(trigger) {
    if (!searchModal) {
      return;
    }
    searchReturnFocus = trigger || document.activeElement;
    searchModal.classList.add('open');
    searchModal.setAttribute('aria-hidden', 'false');
    syncScrollLock();
    window.setTimeout(function () {
      if (searchInput && searchModal.classList.contains('open')) {
        searchInput.focus();
      }
    }, 40);
  }

  function closeSearch() {
    if (!searchModal || !searchModal.classList.contains('open')) {
      return;
    }
    searchModal.classList.remove('open');
    searchModal.setAttribute('aria-hidden', 'true');
    syncScrollLock();
    if (searchReturnFocus && searchReturnFocus.isConnected) {
      searchReturnFocus.focus();
    }
  }

  $$('[data-open-search]').forEach(function (button) {
    button.addEventListener('click', function (event) {
      event.preventDefault();
      openSearch(button);
    });
  });
  $$('[data-close-search]').forEach(function (button) {
    button.addEventListener('click', closeSearch);
  });

  if (searchInput && searchResults) {
    searchInput.addEventListener('input', function () {
      var query = searchInput.value.trim();
      window.clearTimeout(searchTimer);
      if (query.length < 2) {
        searchResults.innerHTML = '';
        return;
      }
      searchTimer = window.setTimeout(function () {
        request('/search/suggest?q=' + encodeURIComponent(query)).then(function (payload) {
          if (searchInput.value.trim() !== query) {
            return; // A newer query has already replaced this one.
          }
          if (!payload.items || payload.items.length === 0) {
            searchResults.innerHTML = '<p class="small muted">No products matched “' + escapeHtml(query) + '”.</p>';
            return;
          }
          searchResults.innerHTML = payload.items.map(function (item) {
            var image = item.image || '/assets/img/tm-mount-1.jpg';
            var imageUrl = /^https?:\/\//i.test(image) ? image : url(image);
            return '<a class="search-result" href="' + escapeHtml(url(item.url)) + '">' +
              '<img src="' + escapeHtml(imageUrl) + '" alt="" loading="lazy">' +
              '<span><strong>' + escapeHtml(item.name) + '</strong><small>' + escapeHtml(item.sku) + '</small></span>' +
              '<span class="price">' + escapeHtml(item.price) + '</span></a>';
          }).join('');
        }).catch(function () {
          if (searchInput.value.trim() === query) {
            searchResults.innerHTML = '';
          }
        });
      }, 220);
    });
  }

  document.addEventListener('keydown', function (event) {
    if (event.key === 'Escape') {
      closeSearch();
      closeDrawer();
      if (mobileNav && mobileNav.classList.contains('open')) {
        mobileNav.classList.remove('open');
        menuToggle.setAttribute('aria-expanded', 'false');
        syncScrollLock();
        menuToggle.focus();
      }
    }
    if (event.key === 'Tab' && searchModal && searchModal.classList.contains('open')) {
      var focusable = $$('a[href], button:not([disabled]), input:not([disabled])', searchModal);
      var first = focusable[0];
      var last = focusable[focusable.length - 1];
      if (first && event.shiftKey && (document.activeElement === first || !searchModal.contains(document.activeElement))) {
        event.preventDefault();
        last.focus();
      } else if (last && !event.shiftKey && (document.activeElement === last || !searchModal.contains(document.activeElement))) {
        event.preventDefault();
        first.focus();
      }
    }
    if ((event.key === '/' || (event.key === 'k' && (event.metaKey || event.ctrlKey))) && !/input|textarea|select/i.test(document.activeElement.tagName)) {
      event.preventDefault();
      openSearch();
    }
  });

  // ---------------------------------------------------------------------
  // Tabs
  // ---------------------------------------------------------------------
  $$('[data-tab]').forEach(function (button) {
    button.addEventListener('click', function () {
      var target = button.getAttribute('data-tab');
      $$('[data-tab]').forEach(function (other) {
        other.classList.toggle('active', other === button);
        other.setAttribute('aria-selected', other === button ? 'true' : 'false');
      });
      $$('[data-panel]').forEach(function (panel) {
        panel.classList.toggle('active', panel.getAttribute('data-panel') === target);
      });
    });
  });

  // ---------------------------------------------------------------------
  // Gallery
  // ---------------------------------------------------------------------
  var mainImage = $('#pdp-main');
  $$('[data-gallery-thumb]').forEach(function (button) {
    button.addEventListener('click', function () {
      if (!mainImage) {
        return;
      }
      mainImage.src = button.getAttribute('data-src');
      mainImage.alt = button.getAttribute('data-alt') || '';
      $$('[data-gallery-thumb]').forEach(function (other) {
        other.classList.toggle('active', other === button);
      });
    });
  });

  // ---------------------------------------------------------------------
  // Flash / announcement / misc
  // ---------------------------------------------------------------------
  $$('[data-flash-close]').forEach(function (button) {
    button.addEventListener('click', function () {
      var flash = button.closest('.flash');
      if (flash) {
        flash.remove();
      }
    });
  });

  $$('[data-flash-auto]').forEach(function (flash) {
    window.setTimeout(function () {
      if (flash.isConnected) {
        flash.classList.add('out');
        window.setTimeout(function () { flash.remove(); }, 250);
      }
    }, 7000);
  });

  // Quick view ---------------------------------------------------------
  var quickModal = document.getElementById('quick-view-modal');
  var quickBody = quickModal ? quickModal.querySelector('[data-quick-view-body]') : null;
  var quickReturnFocus = null;

  function closeQuickView(returnFocus) {
    if (!quickModal) {
      return;
    }
    quickModal.classList.remove('open');
    quickModal.setAttribute('aria-hidden', 'true');
    syncScrollLock();
    if (returnFocus !== false && quickReturnFocus && quickReturnFocus.isConnected) {
      quickReturnFocus.focus();
    }
  }

  function openQuickView(data, trigger) {
    if (!quickModal || !quickBody || !data) {
      return;
    }
    quickReturnFocus = trigger || null;
    var image = escapeHtml(data.image || '');
    var name = escapeHtml(data.name || '');
    var detailUrl = escapeHtml(data.url || '#');
    var compare = data.compare ? '<span class="price-old">' + escapeHtml(data.compare) + '</span>' : '';
    var description = data.description ? '<p class="desc">' + escapeHtml(data.description) + '</p>' : '';
    var buy = '';
    if (data.can_purchase) {
      buy = '<form action="' + escapeHtml(url('/cart/add')) + '" method="post" data-add-to-cart>' +
        '<input type="hidden" name="_token" value="' + escapeHtml(TM.csrf || '') + '">' +
        '<input type="hidden" name="product_id" value="' + escapeHtml(data.id) + '">' +
        '<input type="hidden" name="qty" value="1">' +
        '<button class="btn btn-primary" type="submit">Add to Cart</button></form>' +
        '<a class="btn btn-outline" href="' + detailUrl + '">Full details</a>';
    } else if (data.availability === 'preorder') {
      buy = '<a class="btn btn-primary" href="' + detailUrl + '#notify">Notify me</a><a class="btn btn-outline" href="' + detailUrl + '">Full details</a>';
    } else {
      buy = '<a class="btn btn-primary" href="' + escapeHtml(data.quote_url || data.url || '#') + '">' +
        (data.availability === 'quote' ? 'Request a quote' : 'Check availability') +
        '</a><a class="btn btn-outline" href="' + detailUrl + '">Full details</a>';
    }
    quickModal.querySelector('#quick-view-title').textContent = 'Quick view: ' + (data.name || 'Product');
    quickBody.innerHTML = '<div class="quick-view">' +
      '<div class="quick-view-media"><img src="' + image + '" alt="' + name + '" width="600" height="600"></div>' +
      '<div class="quick-view-body">' +
        (data.category ? '<span class="cat">' + escapeHtml(data.category) + '</span>' : '') +
        '<h2>' + name + '</h2>' +
        '<span class="sku">Model ' + escapeHtml(data.model || '') + ' · SKU ' + escapeHtml(data.sku || '') + '</span>' +
        '<div class="price-row"><span class="price">' + escapeHtml(data.price || '') + '</span>' + compare + '</div>' +
        description +
        '<span class="stock ' + escapeHtml(data.stock_class || '') + '"><i class="dot"></i>' + escapeHtml(data.stock || '') + '</span>' +
        '<div class="buy-row">' + buy + '</div>' +
      '</div></div>';
    quickModal.classList.add('open');
    quickModal.setAttribute('aria-hidden', 'false');
    syncScrollLock();
    var close = quickModal.querySelector('[data-close-quick-view].close-x');
    if (close) {
      close.focus();
    }
  }

  document.addEventListener('click', function (event) {
    var trigger = event.target.closest ? event.target.closest('[data-quick-view]') : null;
    if (trigger) {
      event.preventDefault();
      var raw = trigger.getAttribute('data-quick-view');
      try {
        openQuickView(JSON.parse(raw), trigger);
      } catch (error) {
        var card = trigger.closest('.product-card');
        window.location.href = card && card.querySelector('a[href]') ? card.querySelector('a[href]').href : base;
      }
      return;
    }
    if (event.target.closest && event.target.closest('[data-close-quick-view]')) {
      event.preventDefault();
      closeQuickView();
    }
  });

  document.addEventListener('keydown', function (event) {
    if (!quickModal || !quickModal.classList.contains('open')) {
      return;
    }
    if (event.key === 'Escape') {
      closeQuickView();
    }
    if (event.key === 'Tab') {
      var focusable = $$('a[href], button:not([disabled]), input:not([disabled])', quickModal);
      var first = focusable[0];
      var last = focusable[focusable.length - 1];
      if (!first) {
        return;
      }
      if (event.shiftKey && (document.activeElement === first || !quickModal.contains(document.activeElement))) {
        event.preventDefault();
        last.focus();
      } else if (!event.shiftKey && (document.activeElement === last || !quickModal.contains(document.activeElement))) {
        event.preventDefault();
        first.focus();
      }
    }
  });

  // Expose a tiny public API for page-specific scripts (checkout errors, etc).
  TM.toast = toast;
  window.TM = TM;

  var announce = $('#announce-bar');
  if (announce) {
    try {
      if (window.localStorage.getItem('tm_announce_hidden') === '1') {
        announce.remove();
      }
    } catch (error) {}
    var announceClose = $('[data-announce-close]');
    if (announceClose) {
      announceClose.addEventListener('click', function () {
        try { window.localStorage.setItem('tm_announce_hidden', '1'); } catch (error) {}
        announce.remove();
      });
    }
  }

  $$('[data-confirm]').forEach(function (form) {
    form.addEventListener('submit', function (event) {
      if (!window.confirm(form.getAttribute('data-confirm'))) {
        event.preventDefault();
      }
    });
  });

  $$('[data-print]').forEach(function (button) {
    button.addEventListener('click', function () {
      window.print();
    });
  });

  $$('[data-auto-submit]').forEach(function (field) {
    field.addEventListener('change', function () {
      if (field.form) {
        field.form.submit();
      }
    });
  });

  var filtersToggle = $('[data-filters-toggle]');
  var filters = $('#shop-filters');
  if (filtersToggle && filters) {
    filtersToggle.addEventListener('click', function () {
      var open = filters.classList.toggle('open');
      filtersToggle.setAttribute('aria-expanded', open ? 'true' : 'false');
    });
  }

  $$('[data-newsletter-form]').forEach(function (form) {
    form.addEventListener('submit', function (event) {
      event.preventDefault();
      var data = new FormData(form);
      data.append('_token', TM.csrf || '');
      fetch(url('/newsletter'), {
        method: 'POST',
        body: data,
        credentials: 'same-origin',
        headers: { 'X-Requested-With': 'XMLHttpRequest', Accept: 'application/json' }
      }).then(function (response) {
        return response.json();
      }).then(function (payload) {
        toast(payload.message || 'Thanks for subscribing.', payload.ok ? 'success' : 'error');
        if (payload.ok) {
          form.reset();
        }
      }).catch(function () {
        toast('Network error. Please try again.', 'error');
      });
    });
  });
})();
