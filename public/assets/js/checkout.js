/**
 * Thunder Mounts checkout.
 * PayPal Orders v2: the server creates and captures every order.
 * Card fields, Apple Pay and Google Pay are used only when PayPal reports
 * eligibility for the merchant account.
 */
(function () {
  'use strict';

  var cfg = window.TM_CHECKOUT;
  var form = document.getElementById('checkout-form');
  if (!cfg || !form) {
    return;
  }

  var errorBox = document.getElementById('checkout-errors');
  var methodsBox = document.querySelector('[data-shipping-methods]');
  var statusEl = document.getElementById('shipping-status');
  var paypalContainer = document.getElementById('paypal-buttons');
  var loadingEl = document.getElementById('paypal-loading');
  var failedEl = document.getElementById('paypal-failed');
  var walletBlock = document.getElementById('wallet-block');
  var cardBlock = document.getElementById('card-block');
  var busy = false;
  var quoteTimer = null;
  var quoteSeq = 0;

  function money(cents) {
    return new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' }).format(cents / 100);
  }

  function setBusy(state) {
    busy = state;
    form.querySelectorAll('button[type="button"], button[type="submit"]').forEach(function (button) {
      if (button.hasAttribute('data-manual-order')) {
        return;
      }
      button.disabled = state;
    });
  }

  function clearErrors() {
    if (!errorBox) {
      return;
    }
    errorBox.hidden = true;
    errorBox.innerHTML = '';
  }

  function showErrors(errors) {
    if (!errorBox) {
      window.alert(errors.join('\n'));
      return;
    }
    var list = document.createElement('ul');
    list.style.margin = '0';
    list.style.paddingLeft = '18px';
    (Array.isArray(errors) ? errors : [errors]).forEach(function (message) {
      var item = document.createElement('li');
      item.textContent = message;
      list.appendChild(item);
    });
    errorBox.innerHTML = '';
    errorBox.hidden = false;
    errorBox.appendChild(list);
    errorBox.scrollIntoView({ behavior: 'smooth', block: 'center' });
  }

  function dataPayload(source) {
    var data = new FormData(form);
    if (source) {
      data.append('source', source);
    }
    return data;
  }

  function post(url, body) {
    return fetch(url, {
      method: 'POST',
      body: body,
      credentials: 'same-origin',
      headers: { 'X-Requested-With': 'XMLHttpRequest', Accept: 'application/json' }
    }).then(function (response) {
      return response.json().catch(function () {
        return { ok: false, errors: { general: 'Unexpected response from the server. Please try again.' } };
      }).then(function (payload) {
        if (!response.ok && payload.ok !== false) {
          payload = { ok: false, errors: { general: 'The request failed (' + response.status + '). Please try again.' } };
        }
        return payload;
      });
    });
  }

  function errorList(payload) {
    if (payload && payload.errors) {
      return Object.keys(payload.errors).map(function (key) {
        return payload.errors[key];
      });
    }
    return [payload && payload.message ? payload.message : 'Something went wrong. Please try again.'];
  }

  function validateBasics() {
    if (!form.reportValidity()) {
      return false;
    }
    if (!form.querySelector('input[name="shipping_method"]:checked')) {
      showErrors(['Please choose a shipping method.']);
      return false;
    }
    var terms = form.querySelector('#accept_terms');
    if (terms && !terms.checked) {
      terms.focus();
      showErrors(['Please accept the terms to place your order.']);
      return false;
    }
    return true;
  }

  function renderMethods(options, preserve) {
    if (!methodsBox) {
      return null;
    }
    if (!options || options.length === 0) {
      methodsBox.innerHTML = '<div class="notice notice-warn">' + '<div>We could not find a shipping method for this address. Call 805-338-6680 and we will arrange delivery.</div></div>';
      setPayEnabled(false);
      return null;
    }
    methodsBox.innerHTML = '';
    var selected = null;
    options.forEach(function (option, index) {
      var label = document.createElement('label');
      label.className = 'method';
      var input = document.createElement('input');
      input.type = 'radio';
      input.name = 'shipping_method';
      input.value = String(option.id);
      input.required = true;
      if (String(option.id) === String(preserve) || (!preserve && index === 0)) {
        input.checked = true;
        selected = option;
      }
      var text = document.createElement('span');
      var strong = document.createElement('strong');
      strong.textContent = option.name;
      text.appendChild(strong);
      if (option.description) {
        var description = document.createElement('span');
        description.className = 'small muted';
        description.textContent = option.description;
        text.appendChild(description);
      }
      var price = document.createElement('strong');
      price.textContent = option.cents === 0 ? 'Free' : money(option.cents);
      label.appendChild(input);
      label.appendChild(text);
      label.appendChild(price);
      methodsBox.appendChild(label);
      if (!selected && input.checked) {
        selected = option;
      }
    });
    if (!selected) {
      var first = methodsBox.querySelector('input[name="shipping_method"]');
      if (first) {
        first.checked = true;
      }
    }
    setPayEnabled(true);
    return selected;
  }

  function setPayEnabled(enabled) {
    [paypalContainer, cardBlock, walletBlock].forEach(function (node) {
      if (!node) {
        return;
      }
      node.classList.toggle('is-disabled', !enabled);
      node.style.pointerEvents = enabled ? '' : 'none';
      node.style.opacity = enabled ? '' : '.55';
    });
  }

  function updateTotals(totals, taxName) {
    var map = {
      subtotal: '[data-total-subtotal]',
      shipping: '[data-total-shipping]',
      tax: '[data-total-tax]',
      total: '[data-total-total]',
      discount: '[data-total-discount]'
    };
    Object.keys(map).forEach(function (key) {
      if (totals[key] === undefined) {
        return;
      }
      var node = document.querySelector(map[key]);
      if (node) {
        node.textContent = key === 'discount' ? '−' + totals[key] : totals[key];
      }
    });
    var taxLabel = document.querySelector('[data-tax-name]');
    if (taxLabel) {
      taxLabel.textContent = taxName || '';
    }
  }

  function refreshShipping(force) {
    var required = ['address1', 'city', 'state', 'postcode', 'country'];
    for (var i = 0; i < required.length; i++) {
      var field = form.querySelector('[name="' + required[i] + '"]');
      if (!field || !String(field.value).trim()) {
        return Promise.resolve(false);
      }
    }
    var selected = form.querySelector('input[name="shipping_method"]:checked');
    var preserve = selected ? selected.value : null;
    var seq = ++quoteSeq;
    if (statusEl) {
      statusEl.textContent = 'Updating shipping…';
    }
    return post(cfg.quote, dataPayload()).then(function (payload) {
      if (seq !== quoteSeq) {
        return false;
      }
      if (!payload.ok) {
        if (statusEl) {
          statusEl.textContent = '';
        }
        if (payload.options && payload.options.length) {
          renderMethods(payload.options, preserve);
        }
        return false;
      }
      var chosen = renderMethods(payload.options, preserve);
      updateTotals(payload.totals, payload.tax_name);
      var totalNode = document.querySelector('[data-total-total]');
      if (totalNode) {
        cfg.totalCents = Math.round(parseFloat(totalNode.textContent.replace(/[^0-9.]/g, '')) * 100) || 0;
      }
      if (statusEl) {
        statusEl.textContent = '';
      }
      return !!chosen || !!payload.selected;
    }).catch(function () {
      if (seq === quoteSeq && statusEl) {
        statusEl.textContent = '';
      }
      return false;
    });
  }

  function scheduleQuote() {
    window.clearTimeout(quoteTimer);
    quoteTimer = window.setTimeout(function () {
      refreshShipping(false);
    }, 350);
  }

  ['state', 'postcode', 'country'].forEach(function (name) {
    var field = form.querySelector('[name="' + name + '"]');
    if (field) {
      field.addEventListener('change', scheduleQuote);
      field.addEventListener('blur', scheduleQuote);
    }
  });
  ['address1', 'city'].forEach(function (name) {
    var field = form.querySelector('[name="' + name + '"]');
    if (field) {
      field.addEventListener('blur', scheduleQuote);
    }
  });

  var billingSame = document.getElementById('billing_same');
  var billingFields = document.getElementById('billing-fields');
  if (billingSame && billingFields) {
    billingSame.addEventListener('change', function () {
      billingFields.hidden = billingSame.checked;
    });
  }

  var createAccount = document.getElementById('create_account');
  var accountPassword = document.getElementById('account-password-field');
  if (createAccount && accountPassword) {
    createAccount.addEventListener('change', function () {
      accountPassword.hidden = !createAccount.checked;
      var input = accountPassword.querySelector('input');
      if (input) {
        input.required = createAccount.checked;
      }
    });
  }

  function createOrder(source) {
    if (busy) {
      return Promise.reject(new Error('busy'));
    }
    clearErrors();
    if (!validateBasics()) {
      return Promise.reject(new Error('invalid'));
    }
    setBusy(true);
    return refreshShipping(true).then(function () {
      if (!form.querySelector('input[name="shipping_method"]:checked')) {
        throw { errors: { shipping_method: 'Choose a shipping method for this address.' } };
      }
      return post(cfg.create, dataPayload(source));
    }).then(function (payload) {
      setBusy(false);
      if (!payload.ok) {
        throw { errors: payload.errors, message: payload.message };
      }
      return payload.id;
    }).catch(function (error) {
      setBusy(false);
      if (error && error.errors) {
        showErrors(errorList(error));
      } else if (error && error.message && error.message !== 'invalid' && error.message !== 'busy') {
        showErrors([error.message]);
      }
      throw error;
    });
  }

  function captureOrder(paypalOrderId, options) {
    options = options || {};
    setBusy(true);
    return post(cfg.capture, (function () {
      var data = new FormData();
      data.append('_token', cfg.csrf);
      data.append('paypal_order_id', paypalOrderId);
      return data;
    })()).then(function (payload) {
      if (!payload.ok) {
        throw new Error(payload.message || 'Payment could not be completed.');
      }
      if (options.redirect !== false) {
        window.location.href = payload.redirect;
      }
      return payload;
    }).catch(function (error) {
      setBusy(false);
      showErrors([error.message || 'Payment could not be completed. Please try again.']);
      throw error;
    });
  }

  function appendNotice(container, message, tone) {
    var note = document.createElement('div');
    note.className = 'notice notice-' + (tone || 'info');
    note.innerHTML = '<div></div>';
    note.firstChild.textContent = message;
    container.appendChild(note);
  }

  function initPayPal() {
    if (!window.paypal || !window.paypal.Buttons) {
      return false;
    }
    if (loadingEl) {
      loadingEl.hidden = true;
    }

    window.paypal.Buttons({
      style: { layout: 'vertical', shape: 'rect', label: 'paypal', height: 48, tagline: false },
      createOrder: function () {
        return createOrder('paypal');
      },
      onApprove: function (data) {
        return captureOrder(data.orderID);
      },
      onCancel: function () {
        showErrors(['Payment was cancelled. Your card has not been charged — you can try again.']);
      },
      onError: function (error) {
        console.error('PayPal error', error);
        showErrors(['PayPal could not complete the payment. Please try again or call 805-338-6680.']);
      }
    }).render('[data-paypal-container]').catch(function (error) {
      console.error('PayPal render failed', error);
      if (failedEl) {
        failedEl.hidden = false;
      }
    });

    // Venmo is shown only when the merchant and buyer are eligible.
    try {
      if (window.paypal.FUNDING && window.paypal.FUNDING.VENMO) {
        var venmoWrap = document.createElement('div');
        venmoWrap.id = 'venmo-button';
        venmoWrap.style.marginTop = '8px';
        paypalContainer.appendChild(venmoWrap);
        window.paypal.Buttons({
          fundingSource: window.paypal.FUNDING.VENMO,
          style: { layout: 'vertical', shape: 'rect', height: 48 },
          createOrder: function () {
            return createOrder('paypal');
          },
          onApprove: function (data) {
            return captureOrder(data.orderID);
          },
          onError: function (error) {
            console.error('Venmo error', error);
            showErrors(['Venmo could not complete the payment. Please try another method.']);
          }
        }).render('#venmo-button').catch(function () {
          venmoWrap.remove();
        });
      }
    } catch (error) {
      console.error('Venmo setup failed', error);
    }

    initCardFields();
    initWallets();
    return true;
  }

  function initCardFields() {
    if (!window.paypal.CardFields) {
      return;
    }
    var cardFields;
    try {
      cardFields = window.paypal.CardFields({
        createOrder: function () {
          return createOrder('card');
        },
        onApprove: function (data) {
          return captureOrder(data.orderID);
        },
        onError: function (error) {
          console.error('Card error', error);
          showErrors(['The card payment could not be completed. Check the details or use another card.']);
        },
        style: {
          input: { 'font-size': '16px', 'font-family': 'Inter, system-ui, sans-serif', color: '#181818' },
          '.invalid': { color: '#C82026' }
        }
      });
    } catch (error) {
      console.error('Card fields unavailable', error);
      return;
    }
    if (!cardFields.isEligible()) {
      return;
    }
    if (cardBlock) {
      cardBlock.hidden = false;
    }
    cardFields.NumberField().render('#card-number-field');
    cardFields.ExpiryField().render('#card-expiry-field');
    cardFields.CVVField().render('#card-cvv-field');
    if (cardFields.NameField) {
      cardFields.NameField().render('#card-name-field');
    }
    var submit = document.getElementById('card-submit');
    if (submit) {
      submit.addEventListener('click', function () {
        clearErrors();
        if (!validateBasics()) {
          return;
        }
        setBusy(true);
        cardFields.submit().then(function () {
          setBusy(false);
        }).catch(function (error) {
          setBusy(false);
          console.error('Card submit failed', error);
        });
      });
    }
  }

  function initWallets() {
    if (!walletBlock) {
      return;
    }
    var shown = false;

    if (window.ApplePaySession && window.paypal.Applepay) {
      window.paypal.Applepay().config().then(function (config) {
        if (!config || !config.isApplePayEligible) {
          return;
        }
        var button = document.getElementById('apple-pay-button');
        if (!button) {
          return;
        }
        button.hidden = false;
        walletBlock.hidden = false;
        shown = true;
        button.addEventListener('click', function () {
          startApplePay(config);
        });
      }).catch(function (error) {
        console.error('Apple Pay config unavailable', error);
      });
    }

    if (window.paypal.Googlepay) {
      window.paypal.Googlepay().config().then(function (config) {
        if (!config || config.isEligible === false) {
          return;
        }
        initGooglePay(config, function () {
          walletBlock.hidden = false;
          shown = true;
        });
      }).catch(function (error) {
        console.error('Google Pay config unavailable', error);
      });
    }

    if (!shown) {
      walletBlock.hidden = true;
    }
  }

  function startApplePay(config) {
    clearErrors();
    if (!validateBasics()) {
      return;
    }
    refreshShipping(true).then(function () {
      var amount = (cfg.totalCents / 100).toFixed(2);
      var request = {
        countryCode: 'US',
        currencyCode: 'USD',
        merchantCapabilities: config.merchantCapabilities || ['supports3DS'],
        supportedNetworks: config.supportedNetworks || ['visa', 'masterCard', 'amex', 'discover'],
        requiredBillingContactFields: ['postalAddress', 'name'],
        requiredShippingContactFields: [],
        total: { label: cfg.storeName, type: 'final', amount: amount }
      };
      var session = new window.ApplePaySession(4, request);

      session.onvalidatemerchant = function (event) {
        window.paypal.Applepay().validateMerchant({
          validationUrl: event.validationURL,
          displayName: cfg.storeName
        }).then(function (result) {
          session.completeMerchantValidation(result.merchantSession);
        }).catch(function (error) {
          console.error('Apple Pay merchant validation failed', error);
          session.abort();
          showErrors(['Apple Pay could not be validated for this store. Please use PayPal or a card.']);
        });
      };

      session.onpaymentauthorized = function (event) {
        createOrder('applepay').then(function (orderId) {
          return window.paypal.Applepay().confirmOrder({
            orderId: orderId,
            token: event.payment.token,
            billingContact: event.payment.billingContact
          }).then(function () {
            return captureOrder(orderId, { redirect: false });
          });
        }).then(function (payload) {
          session.completePayment(window.ApplePaySession.STATUS_SUCCESS);
          if (payload && payload.redirect) {
            window.setTimeout(function () {
              window.location.href = payload.redirect;
            }, 250);
          }
        }).catch(function (error) {
          console.error('Apple Pay payment failed', error);
          session.completePayment(window.ApplePaySession.STATUS_FAILURE);
        });
      };

      session.oncancel = function () {
        setBusy(false);
      };

      session.begin();
    });
  }

  function initGooglePay(config, activate) {
    var attempts = 0;
    var timer = window.setInterval(function () {
      attempts++;
      if (window.google && window.google.payments && window.google.payments.api) {
        window.clearInterval(timer);
        setupGooglePay(config, activate);
      } else if (attempts > 40) {
        window.clearInterval(timer);
      }
    }, 150);
  }

  function setupGooglePay(config, activate) {
    var client;
    try {
      client = new window.google.payments.api.PaymentsClient({
        environment: cfg.mode === 'live' ? 'PRODUCTION' : 'TEST',
        paymentDataCallbacks: {
          onPaymentAuthorized: function (paymentData) {
            return createOrder('googlepay').then(function (orderId) {
              return window.paypal.Googlepay().confirmOrder({
                orderId: orderId,
                paymentMethodData: paymentData.paymentMethodData
              }).then(function (response) {
                if (response.status === 'PAYER_ACTION_REQUIRED') {
                  return window.paypal.Googlepay().initiatePayerAction({ orderId: orderId });
                }
                return response;
              }).then(function () {
                return captureOrder(orderId, { redirect: false });
              });
            }).then(function (payload) {
              if (payload && payload.redirect) {
                window.setTimeout(function () {
                  window.location.href = payload.redirect;
                }, 250);
              }
              return { transactionState: 'SUCCESS' };
            }).catch(function (error) {
              console.error('Google Pay payment failed', error);
              return {
                transactionState: 'ERROR',
                error: { intent: 'PAYMENT_AUTHORIZATION', message: 'Payment could not be completed.' }
              };
            });
          }
        }
      });
    } catch (error) {
      console.error('Google Pay client failed', error);
      return;
    }

    var allowed = config.allowedPaymentMethods || [];
    client.isReadyToPay({ allowedPaymentMethods: allowed, apiVersion: config.apiVersion, apiVersionMinor: config.apiVersionMinor })
      .then(function (response) {
        if (!response.result) {
          return;
        }
        var button = client.createButton({
          onClick: function () {
            clearErrors();
            if (!validateBasics()) {
              return;
            }
            refreshShipping(true).then(function () {
              client.loadPaymentData({
                apiVersion: config.apiVersion,
                apiVersionMinor: config.apiVersionMinor,
                allowedPaymentMethods: allowed,
                merchantInfo: config.merchantInfo,
                transactionInfo: {
                  countryCode: 'US',
                  currencyCode: 'USD',
                  totalPriceStatus: 'FINAL',
                  totalPrice: (cfg.totalCents / 100).toFixed(2)
                },
                callbackIntents: ['PAYMENT_AUTHORIZATION']
              });
            });
          },
          buttonType: 'buy',
          buttonColor: 'black'
        });
        var slot = document.getElementById('google-pay-button');
        if (slot) {
          slot.innerHTML = '';
          slot.appendChild(button);
          activate();
        }
      })
      .catch(function (error) {
        console.error('Google Pay readiness failed', error);
      });
  }

  var attempts = 0;
  var sdkTimer = window.setInterval(function () {
    attempts++;
    if (initPayPal()) {
      window.clearInterval(sdkTimer);
    } else if (attempts > 80) {
      window.clearInterval(sdkTimer);
      if (loadingEl) {
        loadingEl.hidden = true;
      }
      if (failedEl) {
        failedEl.hidden = false;
      }
    }
  }, 150);

  if (window.paypal && window.paypal.Buttons) {
    window.clearInterval(sdkTimer);
    initPayPal();
  }

  form.addEventListener('submit', function (event) {
    // The manual/invoice button posts to its own endpoint.
    if (event.submitter && event.submitter.hasAttribute('data-manual-order')) {
      return;
    }
    event.preventDefault();
  });
})();
