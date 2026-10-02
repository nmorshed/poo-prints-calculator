(function () {
  'use strict';
  var busy = false;
  var pending = null;
  var overlay;
  var returnFocus;
  var previousOverflow;

  function showStatus(text, state, button) {
    if (!overlay) {
      overlay = document.createElement('dialog');
      overlay.id = 'pp-order-overlay';
      overlay.setAttribute('aria-labelledby', 'pp-order-title');
      overlay.setAttribute('aria-describedby', 'pp-order-status');
      overlay.innerHTML = '<div class="pp-order-panel">' +
        '<span class="pp-order-icon" aria-hidden="true"></span>' +
        '<h2 id="pp-order-title" tabindex="-1"></h2>' +
        '<p id="pp-order-status" role="status" aria-live="polite" aria-atomic="true"></p>' +
        '<button type="button" class="pp-order-close" hidden>Close</button></div>';
      document.body.appendChild(overlay);
      overlay.querySelector('.pp-order-close').addEventListener('click', function () {
        overlay.close();
      });
      overlay.addEventListener('cancel', function (event) {
        // Keep the page blocked until saving finishes, or navigation begins.
        if (overlay.dataset.state !== 'error') event.preventDefault();
      });
      overlay.addEventListener('close', function () {
        document.body.style.overflow = previousOverflow;
        if (returnFocus && returnFocus.isConnected) returnFocus.focus();
      });
    }
    var opening = !overlay.open;
    overlay.dataset.state = state;
    overlay.querySelector('#pp-order-title').textContent = state === 'error'
      ? 'Unable to complete your order' : state === 'success' ? 'Order placed' : 'Processing your order';
    overlay.querySelector('.pp-order-icon').textContent = state === 'error' ? '!' : state === 'success' ? '✓' : '';
    overlay.querySelector('.pp-order-close').hidden = state !== 'error';
    if (opening) {
      returnFocus = button;
      previousOverflow = document.body.style.overflow;
      document.body.style.overflow = 'hidden';
      overlay.showModal();
      overlay.querySelector('#pp-order-title').focus();
    }
    overlay.querySelector('#pp-order-status').textContent = text;
    if (state === 'error') overlay.querySelector('.pp-order-close').focus();
  }

  // Listen for the independent link, including clicks on its span or SVG.
  document.addEventListener('click', async function (event) {
    var button = event.target.closest('#pp-place-order');
    if (!button) return;
    event.preventDefault();
    if (busy) return;

    try {
      if (typeof ppOrder === 'undefined') throw new Error('Order service unavailable. Please refresh the page.');
      var label = button.querySelector('[data-gs="order-label"]');
      var match = label && label.textContent.match(/\$\s*([\d,]+(?:\.\d{2})?)(\s*\/\s*month)?\s*$/i);
      if (!match) throw new Error('Unable to read the order amount. Please refresh the page.');
      var params = new URLSearchParams(window.location.search);
      var details = { amount: '$' + match[1] + (match[2] ? '/month' : '') };
      ['option', 'o1_qty_swab', 'o1_qty_waste', 'o2_qty_swab'].forEach(function (key) {
        var value = params.get(key);
        if (value === null) throw new Error('Missing order detail: ' + key + '. Please check your order link.');
        details[key] = value;
      });
      var destination = new URL(button.getAttribute('href'), window.location.href);
      if (!/^https?:$/.test(destination.protocol) || button.getAttribute('href') === '#') {
        throw new Error('The order button is missing its destination link.');
      }
      var fingerprint = JSON.stringify(details);
      // Keep the same reference after errors/reloads, including a lost success response.
      try { pending = JSON.parse(sessionStorage.getItem('pp-pending-order')) || pending; } catch (ignore) { /* storage may be unavailable */ }
      if (!pending || pending.fingerprint !== fingerprint) {
        pending = { fingerprint: fingerprint, id: window.crypto.randomUUID() };
        try { sessionStorage.setItem('pp-pending-order', JSON.stringify(pending)); } catch (ignore) { /* in-memory retry still works */ }
      }
      details.request_id = pending.id;
      details.action = 'pooprints_place_order';
      details.nonce = ppOrder.nonce;
      busy = true;
      button.setAttribute('aria-disabled', 'true');
      button.setAttribute('aria-busy', 'true');
      showStatus('We are processing your order........', 'processing', button);
      var response = await fetch(ppOrder.ajax_url, {
        method: 'POST', credentials: 'same-origin', body: new URLSearchParams(details)
      });
      var result;
      try { result = await response.json(); } catch (ignore) {
        throw new Error('The server could not confirm the save (HTTP ' + response.status + '). Please contact support.');
      }
      if (!response.ok || !result || !result.success) {
        throw new Error(result && result.data && result.data.message || 'Unable to save your order. Please try again.');
      }
      showStatus('Your order is placed successfully. Redirecting.....', 'success', button);
      window.setTimeout(function () { window.location.assign(destination.href); }, 1500);
    } catch (error) {
      showStatus(error.message || 'Unable to save your order. Please try again.', 'error', button);
      busy = false;
      button.removeAttribute('aria-disabled');
      button.removeAttribute('aria-busy');
    }
  });
})();
