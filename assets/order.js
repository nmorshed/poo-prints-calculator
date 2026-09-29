(function () {
  'use strict';
  var busy = false;
  var pending = null;

  // Listen for the independent link, including clicks on its span or SVG.
  document.addEventListener('click', async function (event) {
    var button = event.target.closest('#pp-place-order');
    if (!button) return;
    event.preventDefault();
    if (busy) return;

    var message = document.getElementById('pp-order-status');
    if (!message) {
      message = document.createElement('p');
      message.id = 'pp-order-status';
      message.setAttribute('role', 'status');
      message.setAttribute('aria-live', 'polite');
      button.insertAdjacentElement('afterend', message);
    }
    function status(text, failed) {
      message.textContent = text;
      message.style.color = failed ? '#b42318' : '#166534';
    }

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
      status('We are processing your order........', false);
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
      status('Your order is placed successfully. Redirecting.....', false);
      window.setTimeout(function () { window.location.assign(destination.href); }, 1500);
    } catch (error) {
      status(error.message || 'Unable to save your order. Please try again.', true);
      busy = false;
      button.removeAttribute('aria-disabled');
      button.removeAttribute('aria-busy');
    }
  });
})();
