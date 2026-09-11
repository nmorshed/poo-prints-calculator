(function () {
  var root = document.querySelector('[data-pp-form-v2]');
  if (!root || typeof ppFormV2 === 'undefined') return;

  var form = root.querySelector('.pp-form-v2__form');
  var panels = Array.prototype.slice.call(root.querySelectorAll('[data-pp-step]'));
  var dots = Array.prototype.slice.call(root.querySelectorAll('[data-pp-step-dot]'));
  var notice = root.querySelector('[data-pp-form-notice]');
  var prevBtn = root.querySelector('[data-pp-prev]');
  var nextBtn = root.querySelector('[data-pp-next]');
  var currentStep = 1;
  var finalTagged = false;
  var step3Initialized = false;

  function fieldValue(name) {
    var checked = form.querySelector('[name="' + name + '"]:checked');
    if (checked) return checked.value;

    var field = form.querySelector('[name="' + name + '"]');
    if (!field) return '';
    if (field.type === 'radio' || field.type === 'checkbox') return '';

    return field.value;
  }

  function updateConditionals() {
    root.querySelectorAll('[data-pp-show-if]').forEach(function (section) {
      var rule = section.getAttribute('data-pp-show-if') || '';
      var parts = rule.split(':');
      var shouldShow = parts.length === 2 && fieldValue(parts[0]) === parts[1];
      section.classList.toggle('is-visible', shouldShow);
    });

    root.querySelectorAll('[data-pp-step3-branch]').forEach(function (section) {
      var hasManagementCompany = fieldValue('has_management_company');
      var propertyType = (root.getAttribute('data-pp-property-type') || '').trim().toLowerCase();
      var branch = section.getAttribute('data-pp-step3-branch');
      var shouldShow = false;

      if (hasManagementCompany === 'no') {
        shouldShow = propertyType === 'rental' ? branch === 'rental' : branch === 'hoa';
      }

      section.classList.toggle('is-visible', shouldShow);
    });

    root.querySelectorAll('.pp-form-v2__conditional').forEach(function (section) {
      var isVisible = section.classList.contains('is-visible') && !section.parentElement.closest('.pp-form-v2__conditional:not(.is-visible)');

      section.querySelectorAll('input, select, textarea').forEach(function (input) {
        input.disabled = !isVisible;
      });
    });
  }

  function showNotice(message, type) {
    if (!notice) return;
    notice.textContent = message || '';
    notice.className = 'pp-form-v2__notice';
    if (message) notice.classList.add('is-visible', 'is-' + (type || 'info'));
  }

  function showStep(step) {
    currentStep = step;
    if (step === 3 && !step3Initialized) {
      form.querySelectorAll('[name="has_management_company"]').forEach(function (field) {
        field.checked = false;
      });
      step3Initialized = true;
    }

    panels.forEach(function (panel) {
      panel.classList.toggle('is-active', parseInt(panel.dataset.ppStep, 10) === step);
    });
    dots.forEach(function (dot) {
      var dotStep = parseInt(dot.dataset.ppStepDot, 10);
      dot.classList.toggle('is-active', dotStep === step);
      dot.classList.toggle('is-complete', dotStep < step);
    });

    if (prevBtn) prevBtn.style.display = step === 1 || step === 4 ? 'none' : '';
    if (nextBtn) {
      nextBtn.style.display = step === 4 ? 'none' : '';
      nextBtn.textContent = step === 3 ? 'Submit Info' : 'Continue';
    }

    showNotice('', 'info');
    updateConditionals();

    if (step === 4 && !finalTagged) {
      finalTagged = true;
      submitStep(4, true);
    }
  }

  function activePanel() {
    return root.querySelector('[data-pp-step="' + currentStep + '"]');
  }

  function validateStep() {
    var panel = activePanel();
    if (!panel) return true;

    var fields = Array.prototype.slice.call(panel.querySelectorAll('input, select, textarea'));
    for (var i = 0; i < fields.length; i++) {
      var field = fields[i];
      if (field.disabled || field.offsetParent === null) continue;
      if (!field.checkValidity()) {
        field.reportValidity();
        return false;
      }
    }
    return true;
  }

  function submitStep(step, silent) {
    var data = new FormData(form);
    data.set('action', ppFormV2.action);
    data.set('nonce', ppFormV2.nonce);
    data.set('step', String(step));

    if (!silent) {
      nextBtn.disabled = true;
      nextBtn.textContent = step === 3 ? 'Submitting...' : 'Saving...';
      showNotice('', 'info');
    }

    return fetch(ppFormV2.ajax_url, {
      method: 'POST',
      credentials: 'same-origin',
      body: data
    })
      .then(function (response) { return response.json(); })
      .then(function (payload) {
        if (!payload || !payload.success) {
          var msg = payload && payload.data && payload.data.message ? payload.data.message : 'Unable to save this step.';
          throw new Error(msg);
        }
        return payload;
      })
      .catch(function (error) {
        if (!silent) showNotice(error.message, 'error');
        throw error;
      })
      .finally(function () {
        if (!silent) {
          nextBtn.disabled = false;
          nextBtn.textContent = step === 3 ? 'Submit Info' : 'Continue';
        }
      });
  }

  function addHoaContact() {
    var list = root.querySelector('[data-pp-hoa-list]');
    var item = list ? list.querySelector('.pp-form-v2__repeat-item') : null;
    if (!list || !item) return;

    var clone = item.cloneNode(true);
    clone.querySelectorAll('input, select').forEach(function (field) {
      field.value = '';
      if (field.type === 'checkbox' || field.type === 'radio') field.checked = false;
    });
    var index = list.querySelectorAll('.pp-form-v2__repeat-item').length;
    clone.querySelectorAll('[name^="hoa_on_board"]').forEach(function (field) {
      field.name = 'hoa_on_board[' + index + ']';
    });
    list.appendChild(clone);
  }

  root.addEventListener('change', updateConditionals);

  var addHoaBtn = root.querySelector('[data-pp-add-hoa]');
  if (addHoaBtn) {
    addHoaBtn.addEventListener('click', addHoaContact);
  }

  if (prevBtn) {
    prevBtn.addEventListener('click', function () {
      if (currentStep > 1 && currentStep < 4) showStep(currentStep - 1);
    });
  }

  if (nextBtn) {
    nextBtn.addEventListener('click', function () {
      if (!validateStep()) return;
      submitStep(currentStep, false).then(function () {
        showStep(Math.min(currentStep + 1, 4));
      }).catch(function () {});
    });
  }

  updateConditionals();
  showStep(1);
})();

(function () {
  var root = document.querySelector('[data-pp-quote-present]');
  if (!root || typeof ppFormV2 === 'undefined') return;

  var form = root.querySelector('.pp-quote-present__form');
  var panels = Array.prototype.slice.call(root.querySelectorAll('[data-pp-quote-step]'));
  var dots = Array.prototype.slice.call(root.querySelectorAll('[data-pp-quote-step-dot]'));
  var notice = root.querySelector('[data-pp-quote-notice]');
  var loading = root.querySelector('[data-pp-quote-loading]');
  var heroKicker = root.querySelector('[data-pp-quote-kicker]');
  var heroTitle = root.querySelector('[data-pp-quote-title]');
  var message = root.querySelector('[data-pp-quote-message]');
  var nextBtn = root.querySelector('[data-pp-quote-next]');
  var submitBtn = root.querySelector('[data-pp-quote-submit]');
  var currentStep = 2;

  function showNotice(messageText, type) {
    if (!notice) return;
    notice.textContent = messageText || '';
    notice.className = 'pp-form-v2__notice';
    if (messageText) notice.classList.add('is-visible', 'is-' + (type || 'info'));
  }

  function setLoading(isLoading) {
    if (loading) loading.hidden = !isLoading;
    Array.prototype.slice.call(form.querySelectorAll('input, select, textarea, button')).forEach(function (field) {
      field.disabled = isLoading;
    });
  }

  function showStep(step) {
    currentStep = step;
    panels.forEach(function (panel) {
      panel.classList.toggle('is-active', parseInt(panel.dataset.ppQuoteStep, 10) === step);
    });
    dots.forEach(function (dot) {
      var dotStep = parseInt(dot.dataset.ppQuoteStepDot, 10);
      dot.classList.toggle('is-active', dotStep === step);
      dot.classList.toggle('is-complete', dotStep < step);
      dot.querySelector('span').textContent = dotStep < step ? '\u2713' : String(dotStep);
    });

    if (heroKicker) heroKicker.textContent = step === 3 ? 'About the dogs' : 'About your property';
    if (heroTitle) heroTitle.textContent = step === 3 ? 'Last step' : 'Where is the property located?';
    showNotice('', 'info');
  }

  function activePanel() {
    return root.querySelector('[data-pp-quote-step="' + currentStep + '"]');
  }

  function validateStep() {
    var panel = activePanel();
    if (!panel) return true;

    var fields = Array.prototype.slice.call(panel.querySelectorAll('input, select, textarea'));
    for (var i = 0; i < fields.length; i++) {
      var field = fields[i];
      if (field.disabled || field.offsetParent === null) continue;
      if (!field.checkValidity()) {
        field.reportValidity();
        return false;
      }
    }
    return true;
  }

  function submitStep(step) {
    var data = new FormData(form);
    data.set('action', ppFormV2.quote_action);
    data.set('nonce', ppFormV2.quote_nonce);
    data.set('step', String(step));

    return fetch(ppFormV2.ajax_url, {
      method: 'POST',
      credentials: 'same-origin',
      body: data
    })
      .then(function (response) { return response.json(); })
      .then(function (payload) {
        if (!payload || !payload.success) {
          var msg = payload && payload.data && payload.data.message ? payload.data.message : 'Unable to save this step.';
          throw new Error(msg);
        }
        console.log('PooPrints quote decision:', payload.data);
        return payload.data;
      });
  }

  function showMessage() {
    panels.forEach(function (panel) {
      panel.classList.remove('is-active');
    });
    root.querySelector('.pp-quote-present__steps').hidden = true;
    if (message) message.hidden = false;
    if (heroKicker) heroKicker.textContent = 'Quote request received';
    if (heroTitle) heroTitle.textContent = "Thanks \u2014 we'll be in touch.";
    showNotice('', 'info');
  }

  if (nextBtn) {
    nextBtn.addEventListener('click', function () {
      if (!validateStep()) return;

      nextBtn.disabled = true;
      nextBtn.textContent = 'Saving...';
      showNotice('', 'info');

      submitStep(2).then(function () {
        showStep(3);
      }).catch(function (error) {
        console.error('PooPrints quote step 2 error:', error);
        showNotice(error.message, 'error');
      }).finally(function () {
        nextBtn.disabled = false;
        nextBtn.textContent = 'Continue';
      });
    });
  }

  if (submitBtn) {
    submitBtn.addEventListener('click', function () {
      if (!validateStep()) return;

      setLoading(true);
      showNotice('', 'info');

      submitStep(3).then(function (result) {
        if (result.action === 'redirect' && result.url) {
          window.location.href = result.url;
          return;
        }

        setLoading(false);
        showMessage();
      }).catch(function (error) {
        console.error('PooPrints quote step 3 error:', error);
        setLoading(false);
        showNotice(error.message, 'error');
      });
    });
  }

  showStep(2);
})();
