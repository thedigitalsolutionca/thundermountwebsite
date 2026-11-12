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
    node.className = 'toast toast-' + (type || 'success');
    node.innerHTML = '<span>' + escapeHtml(message) + '</span><button type="button" aria-label="Dismiss">&times;</button>';
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
  // Cart drawer
  // ---------------------------------------------------------------------
  var drawer = $('#cart-drawer');

  function openDrawer() {
    if (!drawer) {
      return;
    }
    drawer.classList.add('open');
    drawer.setAttribute('aria-hidden', 'false');
    document.body.classList.add('no-scroll');
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
    document.body.classList.remove('no-scroll');
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

  // Add to cart
  $$('form[data-add-to-cart]').forEach(function (form) {
    form.addEventListener('submit', function (event) {
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
        refreshDrawer();
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
        if (parseInt(data.get('qty'), 10) <= 0) {
          window.location.reload();
          return;
        }
        var line = form.closest('.cart-line');
        if (line && payload.summary) {
          line.querySelector('strong').textContent = payload.summary.total;
        }
        refreshDrawer();
        setCartCount(payload.count);
        if (payload.summary && payload.summary.total) {
          var total = $('[data-total-total]');
          if (total) {
            total.textContent = payload.summary.total;
          }
        }
        toast('Quantity updated.', 'success');
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
      document.body.classList.toggle('no-scroll', open);
    });
  }

  // ---------------------------------------------------------------------
  // Search
  // ---------------------------------------------------------------------
  var searchModal = $('#search-modal');
  var searchInput = $('[data-search-input]');
  var searchResults = $('[data-search-results]');
  var searchTimer;

  function openSearch() {
    if (!searchModal) {
      return;
    }
    searchModal.classList.add('open');
    searchModal.setAttribute('aria-hidden', 'false');
    document.body.classList.add('no-scroll');
    window.setTimeout(function () { if (searchInput) { searchInput.focus(); } }, 40);
  }

  function closeSearch() {
    if (!searchModal) {
      return;
    }
    searchModal.classList.remove('open');
    searchModal.setAttribute('aria-hidden', 'true');
    document.body.classList.remove('no-scroll');
  }

  $$('[data-open-search]').forEach(function (button) {
    button.addEventListener('click', function (event) {
      event.preventDefault();
      openSearch();
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
          if (!payload.items || payload.items.length === 0) {
            searchResults.innerHTML = '<p class="small muted">No products matched “' + escapeHtml(query) + '”.</p>';
            return;
          }
          searchResults.innerHTML = payload.items.map(function (item) {
            return '<a class="search-result" href="' + escapeHtml(url(item.url)) + '">' +
              '<img src="' + escapeHtml(base.replace(/\/$/, '') + item.image) + '" alt="" loading="lazy">' +
              '<span><strong>' + escapeHtml(item.name) + '</strong><small>' + escapeHtml(item.sku) + '</small></span>' +
              '<span class="price">' + escapeHtml(item.price) + '</span></a>';
          }).join('');
        }).catch(function () {
          searchResults.innerHTML = '';
        });
      }, 220);
    });
  }

  document.addEventListener('keydown', function (event) {
    if (event.key === 'Escape') {
      closeSearch();
      closeDrawer();
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
