/* ============================================================
   POOPRINTS CALCULATOR — main.js
   ============================================================ */

document.addEventListener('DOMContentLoaded', function () {

  /* ============================================================
     PRICES — pulled from wp_localize_script (ppPrices) with fallbacks
     ============================================================ */
  const pp = (typeof ppPrices !== 'undefined') ? ppPrices : {};
  const PRICES = {
    swab_kit:            parseFloat(pp.swab_kit)            || 57.97,
    waste_kit:           parseFloat(pp.waste_kit)           || 39.97,
    setup_fee:           parseFloat(pp.setup_fee)           || 97.00,
    subscription_fee:    parseFloat(pp.subscription_fee)    || 227.00,
    single_pay_discount: parseFloat(pp.single_pay_discount) || 0.90,
    profit_per_test:     parseFloat(pp.profit_per_test)     || 52.00,
    waste_reduction:     0.95,
    regression_a:        parseFloat(pp.regression_a)        || 0.2177,
    regression_b:        parseFloat(pp.regression_b)        || 0.001345,
    regression_c:        parseFloat(pp.regression_c)        || 0.004391,
    weeks_per_year:      52,
    minutes_email:       parseInt(pp.minutes_email)         || 10,
    minutes_phone:       parseInt(pp.minutes_phone)         || 15,
    minutes_social:      parseInt(pp.minutes_social)        || 10,
  };

  /* ============================================================
     FIELD MAP — passed from PHP via wp_localize_script (ppPrices.field_map).
     Key = URL param = HTML input ID. Default comes from field_map() in class-shortcodes.php.
     ============================================================ */
  const FIELD_MAP = (typeof ppPrices !== 'undefined' && ppPrices.field_map) ? ppPrices.field_map : {};

  /* ============================================================
     HELPERS
     ============================================================ */
  function val(id) {
    const el = document.getElementById(id);
    if (!el) return 0;
    const n = parseFloat(el.value);
    return isNaN(n) || n < 0 ? 0 : n;
  }

  function fmt(n) {
    return '$' + Math.abs(n).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
  }

  function fmtInt(n) {
    return '$' + Math.round(Math.abs(n)).toLocaleString('en-US');
  }

  function fmtHours(n) {
    return Math.abs(n).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
  }

  function set(id, text) {
    const el = document.getElementById(id);
    if (el) el.textContent = text;
  }

  // Update all [data-pp-key="key"] spans — shortcode [pooprints_value] renders these.
  function setByKey(key, text) {
    document.querySelectorAll('[data-pp-key="' + key + '"]').forEach(function (el) {
      el.textContent = text;
    });
  }

  /* ============================================================
     COLLAPSE / EXPAND
     ============================================================ */
  window.roiToggleAll = function (linkEl) {
    var expanding = linkEl.textContent.indexOf('Expand') !== -1;

    // Left col: groupA, groupB
    var leftSections = ['groupA', 'groupB'];
    leftSections.forEach(function (id) {
      var el = document.getElementById(id);
      if (!el) return;
      el.style.display = expanding ? '' : 'none';
      var wrapper = el.closest ? el.closest('.roi-group-wrapper') : el.parentElement;
      if (wrapper) {
        expanding ? wrapper.classList.add('is-open') : wrapper.classList.remove('is-open');
      }
      var arrow = el.previousElementSibling ? el.previousElementSibling.querySelector('.row-arrow') : null;
      if (arrow) arrow.innerHTML = expanding ? '&#9650;' : '&#9658;';
    });

    // Right col: roiCommunity, roiTurnover, roiTime
    var rightSections = [
      { id: 'roiCommunity', arrowClass: 'section-arrow' },
      { id: 'roiTurnover',  arrowClass: 'section-arrow' },
      { id: 'roiTime',      arrowClass: 'section-arrow' },
    ];
    rightSections.forEach(function (s) {
      var el = document.getElementById(s.id);
      if (!el) return;
      if (expanding) {
        el.style.display = '';
        el.classList.add('is-open');
      } else {
        el.style.display = 'none';
        el.classList.remove('is-open');
      }
      var header = el.previousElementSibling;
      if (header) {
        var arrow = header.querySelector('.' + s.arrowClass);
        if (arrow) arrow.innerHTML = expanding ? '&#9660;' : '&#9658;';
      }
    });

    // Also expand/collapse the "More Choices" sub-section inside Community Information
    var communityExtra = document.getElementById('roiCommunityExtra');
    var fewerChoicesBtn = document.getElementById('fewerChoicesBtn');
    if (communityExtra) {
      communityExtra.style.display = expanding ? '' : 'none';
    }
    if (fewerChoicesBtn) {
      fewerChoicesBtn.innerHTML = expanding ? '&#9660; Fewer Choices' : '&#9658; More Choices';
    }

    linkEl.innerHTML = expanding ? '&#9650; Collapse All' : '&#9660; Expand All';
  };

  window.roiResetDefaults = function () {
    if (typeof ppPrices === 'undefined' || !ppPrices.field_map) return;
    Object.keys(ppPrices.field_map).forEach(function (param) {
      var entry = ppPrices.field_map[param];
      var el = document.getElementById(entry.id);
      if (el) el.value = entry.default;
    });
    // Keep dogs/o1_qty_swab in sync
    var swabEl = document.getElementById('o1_qty_swab');
    var dogsEl = document.getElementById('dogs');
    if (swabEl && dogsEl) dogsEl.value = swabEl.value;
    calcOption1();
    calcOption2();
  };

  window.toggleSection = function (sectionId, arrowEl) {
    var section = document.getElementById(sectionId);
    if (!section) return;
    var isHidden = section.style.display === 'none';
    section.style.display = isHidden ? '' : 'none';
    if (arrowEl) {
      if (arrowEl.classList && arrowEl.classList.contains('fewer-choices-toggle')) {
        arrowEl.innerHTML = isHidden ? '&#9660; Fewer Choices' : '&#9658; More Choices';
      } else if (arrowEl.classList && arrowEl.classList.contains('section-arrow')) {
        // Right column — expands downward: ▼ open, ► closed
        arrowEl.innerHTML = isHidden ? '&#9660;' : '&#9658;';
      } else {
        // Left column ROI groups — expands upward: ▲ open, ► closed
        arrowEl.innerHTML = isHidden ? '&#9650;' : '&#9658;';
      }
    }
    // Toggle is-open on the closest roi-group-wrapper ancestor (left column)
    // or directly on the section element itself if it's an roi-input-section body (right column)
    var groupWrapper = section.closest('.roi-group-wrapper');
    if (groupWrapper) {
      groupWrapper.classList.toggle('is-open', isHidden);
    } else if (section.parentElement && section.parentElement.classList.contains('roi-input-section')) {
      section.classList.toggle('is-open', isHidden);
    }
  };

  /* ============================================================
     CALCULATOR — Option 1
     ============================================================ */
  var singlePayment = 0;

  function calcOption1() {
    var qty_swab  = val('o1_qty_swab');
    var qty_waste = val('o1_qty_waste');

    var swab_cost  = qty_swab  * PRICES.swab_kit;
    var waste_cost = qty_waste * PRICES.waste_kit;
    var total_before_discount = swab_cost + waste_cost + PRICES.setup_fee + PRICES.subscription_fee;

    singlePayment = total_before_discount;
    var monthly = total_before_discount / PRICES.single_pay_discount / 12;

    set('o1_swab_cost',       fmt(swab_cost));
    set('o1_waste_cost',      fmt(waste_cost));
    set('o1_single_payment',  fmt(singlePayment));
    set('o1_monthly_payment', fmt(monthly));

    setByKey('opt1_qty_swab',        qty_swab);
    setByKey('opt1_qty_waste',       qty_waste);
    setByKey('opt1_payment_monthly',  monthly.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }));
    setByKey('opt1_payment_single',   singlePayment.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }));
    var total_12 = total_before_discount / PRICES.single_pay_discount;
    setByKey('opt1_payment_12total',  total_12.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }));
    setByKey('opt1_payment_savings',  Math.round(total_12 - singlePayment).toLocaleString('en-US'));

    calcROI();
  }

  /* ============================================================
     CALCULATOR — Option 1 Template 2
     ============================================================ */
  function calcOption1T2() {
    var subtotal = PRICES.setup_fee + PRICES.subscription_fee;
    document.querySelectorAll('#pp-table-opt1 [data-pp-line]').forEach(function (input) {
      var qty      = parseInt(input.value, 10) || 0;
      var price    = parseFloat(input.dataset.ppPrice) || 0;
      var lineCost = qty * price;
      var costEl   = document.querySelector('#pp-table-opt1 [data-pp-cost="' + input.dataset.ppLine + '"]');
      if (costEl) costEl.textContent = fmt(lineCost);
      subtotal += lineCost;
    });

    singlePayment = subtotal;
    var monthly  = subtotal / PRICES.single_pay_discount / 12;
    var total_12 = subtotal / PRICES.single_pay_discount;

    var singleEl  = document.querySelector('[data-pp-total="opt1-t2-single"]');
    var monthlyEl = document.querySelector('[data-pp-total="opt1-t2-monthly"]');
    if (singleEl)  singleEl.textContent  = fmt(singlePayment);
    if (monthlyEl) monthlyEl.textContent = fmt(monthly);

    var dnaInput   = document.querySelector('#pp-table-opt1 [data-pp-line="dna"]');
    var wasteInput = document.querySelector('#pp-table-opt1 [data-pp-line="waste"]');
    setByKey('opt1_qty_swab',       dnaInput   ? parseInt(dnaInput.value, 10)   || 0 : 0);
    setByKey('opt1_qty_waste',      wasteInput ? parseInt(wasteInput.value, 10) || 0 : 0);
    setByKey('opt1_payment_monthly',  monthly.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }));
    setByKey('opt1_payment_single',   singlePayment.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }));
    setByKey('opt1_payment_12total',  total_12.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }));
    setByKey('opt1_payment_savings',  Math.round(total_12 - singlePayment).toLocaleString('en-US'));

    calcROI();
  }

  /* ============================================================
     CALCULATOR — Option 2 Template 2
     ============================================================ */
  function calcOption2T2() {
    var dna_cost = 0;
    document.querySelectorAll('#pp-table-opt2 [data-pp-line]').forEach(function (input) {
      var qty      = parseInt(input.value, 10) || 0;
      var price    = parseFloat(input.dataset.ppPrice) || 0;
      var lineCost = qty * price;
      var costEl   = document.querySelector('#pp-table-opt2 [data-pp-cost="' + input.dataset.ppLine + '"]');
      if (costEl) costEl.textContent = fmt(lineCost);
      dna_cost += lineCost;
    });

    var total_2 = dna_cost + PRICES.setup_fee + PRICES.subscription_fee;

    var singleEl  = document.querySelector('[data-pp-total="opt2-t2-single"]');
    var monthlyEl = document.querySelector('[data-pp-total="opt2-t2-monthly"]');
    if (singleEl)  singleEl.textContent  = fmt(total_2);
    if (monthlyEl) monthlyEl.textContent = fmt(total_2 / 12);

    var dnaInput = document.querySelector('#pp-table-opt2 [data-pp-line="dna2"]');
    setByKey('opt2_qty_swab',       dnaInput ? parseInt(dnaInput.value, 10) || 0 : 0);
    setByKey('opt2_payment_single', total_2.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }));
  }

  /* ============================================================
     CALCULATOR — Option 2
     ============================================================ */
  function calcOption2() {
    var qty_swab  = val('o2_qty_swab');
    var swab_cost = qty_swab * PRICES.swab_kit;
    var total_2   = swab_cost + PRICES.setup_fee + PRICES.subscription_fee;

    set('o2_swab_cost', fmt(swab_cost));
    set('o2_total',     fmt(total_2));

    setByKey('opt2_qty_swab',       qty_swab);
    setByKey('opt2_payment_single', total_2.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }));
  }

  /* ============================================================
     CALCULATOR — ROI
     ============================================================ */
  function calcROI() {
    var units               = val('units');
    var dogs                = val('dogs');
    var rent_per_dog        = val('rent_per_dog');
    var dog_fee             = val('dog_fee');
    var hidden_dogs         = val('hidden_dogs');
    var turnover_cost       = val('turnover_cost');
    var acquisition_cost    = val('acquisition_cost');
    var renewals_saved      = val('renewals_saved');
    var complaints_per_week = val('complaints_per_week');
    var staff_wage          = val('staff_wage');
    var pickup_hours_pw     = val('pickup_hours_per_week');
    var minutes_email       = val('minutes_email');
    var minutes_phone       = val('minutes_phone');
    var minutes_social      = val('minutes_social');

    var turnover_saved    = renewals_saved * turnover_cost;
    var acquisition_saved = renewals_saved * acquisition_cost;

    var annual_tests      = (PRICES.regression_a + (PRICES.regression_b * units) + (PRICES.regression_c * dogs)) * 12;
    var waste_test_profit = annual_tests * PRICES.profit_per_test;

    var total_cash_savings  = turnover_saved + acquisition_saved + waste_test_profit;
    var fees_recovered      = hidden_dogs * ((rent_per_dog * 12) + dog_fee);
    var total_cash_and_fees = total_cash_savings + fees_recovered;

    var complaints_per_channel = complaints_per_week / 3;
    var minutes_per_complaint  = minutes_email + minutes_phone + minutes_social;
    var complaint_hours_saved  = complaints_per_channel * (minutes_per_complaint / 60) * PRICES.weeks_per_year;

    var annual_pickup_hours = pickup_hours_pw * PRICES.weeks_per_year;
    var pickup_hours_saved  = annual_pickup_hours * PRICES.waste_reduction;

    var total_hours_saved = complaint_hours_saved + pickup_hours_saved;
    var time_value        = total_hours_saved * staff_wage;
    var total_all_savings = total_cash_and_fees + time_value;
    var roi               = total_all_savings - singlePayment;

    set('roi_turnover_saved',        fmtInt(turnover_saved));
    set('roi_acquisition_saved',     fmtInt(acquisition_saved));
    set('roi_waste_test_profit',     fmtInt(waste_test_profit));
    set('roi_total_cash_savings',    fmtInt(total_cash_savings));
    set('roi_fees_recovered',        fmtInt(fees_recovered));
    set('roi_total_cash_and_fees',   fmtInt(total_cash_and_fees));
    set('roi_complaint_hours_saved', fmtHours(complaint_hours_saved));
    set('roi_pickup_hours_saved',    fmtHours(pickup_hours_saved));
    set('roi_total_hours_saved',     fmtHours(total_hours_saved));
    set('roi_time_value',            fmtInt(time_value));
    set('roi_total_all_savings',     fmtInt(total_all_savings));
    set('roi_investment_display',    '\u2212$' + Math.round(singlePayment).toLocaleString('en-US'));
    set('roi_final',                 fmtInt(roi));

    setByKey('total_units',            units);
    setByKey('total_dogs',             dogs);
    setByKey('roi_savings_total',      Math.round(total_all_savings).toLocaleString('en-US'));
    setByKey('roi_net_return',         Math.abs(roi).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }));
    setByKey('roi_hours_saved',        fmtHours(total_hours_saved));
    setByKey('roi_cash_savings',       Math.round(total_cash_savings).toLocaleString('en-US'));
    setByKey('roi_fees_recovered',     Math.round(fees_recovered).toLocaleString('en-US'));
    setByKey('roi_turnover_saved',     Math.round(turnover_saved).toLocaleString('en-US'));
    setByKey('roi_acquisition_saved',  Math.round(acquisition_saved).toLocaleString('en-US'));
  }

  /* ============================================================
     WIRE UP INPUTS
     ============================================================ */
  ['o1_qty_swab', 'o1_qty_waste'].forEach(function (id) {
    var el = document.getElementById(id);
    if (el) el.addEventListener('input', calcOption1);
  });

  // Sync o1_qty_swab ↔ dogs (ROI Total # of Dogs)
  var o1SwabEl = document.getElementById('o1_qty_swab');
  var dogsEl   = document.getElementById('dogs');
  if (o1SwabEl && dogsEl) {
    o1SwabEl.addEventListener('input', function () {
      dogsEl.value = this.value;
    });
    dogsEl.addEventListener('input', function () {
      o1SwabEl.value = this.value;
      calcOption1();
    });
  }

  var o2el = document.getElementById('o2_qty_swab');
  if (o2el) o2el.addEventListener('input', calcOption2);

  document.querySelectorAll('#pp-table-opt1 [data-pp-line]').forEach(function (input) {
    input.addEventListener('input', calcOption1T2);
  });
  document.querySelectorAll('#pp-table-opt2 [data-pp-line]').forEach(function (input) {
    input.addEventListener('input', calcOption2T2);
  });

  [
    'units', 'dogs', 'rent_per_dog', 'dog_fee',
    'hidden_dogs', 'turnover_cost', 'acquisition_cost',
    'renewals_saved', 'complaints_per_week', 'staff_wage',
    'pickup_hours_per_week', 'minutes_email', 'minutes_phone',
    'minutes_social',
  ].forEach(function (id) {
    var el = document.getElementById(id);
    if (el) el.addEventListener('input', calcROI);
  });

  /* ============================================================
     FORWARD QUOTE — build shareable URL with only changed params
     ============================================================ */
  function buildQuoteURL() {
    var base   = window.location.origin + window.location.pathname;
    var params = new URLSearchParams();

    Object.keys(FIELD_MAP).forEach(function (param) {
      var entry   = FIELD_MAP[param];
      var el      = document.getElementById(entry.id);
      if (!el) return;
      var current = parseFloat(el.value);
      if (current !== parseFloat(entry.default)) {
        params.set(param, current);
      }
    });

    return params.toString() ? base + '?' + params.toString() : base;
  }

  document.querySelectorAll('.forward-quote a').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var url = buildQuoteURL();

      // Inject URL into popup after Elementor finishes opening it
      setTimeout(function () {
        var popupLink = document.getElementById('pp-quote-link');
        if (popupLink) {
          popupLink.href = url;
          popupLink.textContent = url;
        }
      }, 100);

      navigator.clipboard.writeText(url).then(function () {
        console.log('[PooPrints] Quote URL copied to clipboard:', url);
      }).catch(function () {
        console.warn('[PooPrints] Clipboard write failed. URL:', url);
      });
    });
  });

  /* ============================================================
     SYNC roi_units → Keap _ofUnits on change
     @future: waiting for client to confirm unit count is needed to update Keap field.
     ============================================================ 
  var unitsEl = document.getElementById('roi_units');
  if (unitsEl) {
    unitsEl.addEventListener('change', function () {
      var units = parseInt(this.value, 10);
      if (isNaN(units) || units < 0) return;

      fetch(ppPrices.ajax_url, {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams({
          action: 'pooprints_update_units',
          nonce:  ppPrices.nonce,
          units:  units,
        }),
      })
      .then(function (r) { return r.json(); })
      .then(function (data) {
        if (data.success) {
          console.log('[PooPrints] _ofUnits updated to ' + units);
        } else {
          console.log('[PooPrints] _ofUnits update failed', data);
        }
      });
    });
  }
    */

  /* ============================================================
     INITIAL RENDER
     ============================================================ */
  if (document.getElementById('o1_qty_swab')) calcOption1();
  if (document.getElementById('o2_qty_swab')) calcOption2();
  if (document.querySelector('#pp-table-opt1 [data-pp-line]')) calcOption1T2();
  if (document.querySelector('#pp-table-opt2 [data-pp-line]')) calcOption2T2();
  // calcROI() is already called inside calcOption1() and calcOption1T2().
  // Only call standalone if option1 is absent but ROI card is present.
  if (!document.getElementById('o1_qty_swab') && !document.querySelector('#pp-table-opt1 [data-pp-line]') && document.getElementById('units')) calcROI();

  /* ============================================================
     SIDENAV — scroll spy (same-page anchors, e.g. Elementor menu anchors)
     ============================================================ */
  (function initSidenavScrollSpy() {
    var nav = document.querySelector('.pp-sidenav');
    if (!nav) return;

    var OFFSET = 100; // px from viewport top; tune for sticky header
    var SCROLL_TOP_MAX = 8; // treat as "not scrolled yet" for same-page highlight

    var allLinks = Array.prototype.slice.call(nav.querySelectorAll('.pp-sidenav__item'));

    function hrefIsSamePage(href) {
      if (!href) return false;
      try {
        var u = new URL(href, window.location.href);
        if (u.origin !== window.location.origin) return false;
        return u.pathname === window.location.pathname && u.search === window.location.search;
      } catch (e) {
        return false;
      }
    }

    var samePageLinks = allLinks.filter(function (link) {
      return hrefIsSamePage(link.getAttribute('href'));
    });

    var pairs = [];
    nav.querySelectorAll('.pp-sidenav__item').forEach(function (link) {
      var href = link.getAttribute('href');
      if (!href || href.indexOf('#') === -1) return;
      var hash = href.slice(href.indexOf('#'));
      if (hash.length < 2) return;
      var id = hash.slice(1);
      var target = document.getElementById(id);
      if (!target) {
        try {
          target = document.querySelector(hash);
        } catch (e) {
          return;
        }
      }
      if (!target) return;
      pairs.push({ link: link, section: target });
    });

    if (!pairs.length && !samePageLinks.length) return;

    var ticking = false;

    function setActiveOnLink(activeLink) {
      allLinks.forEach(function (a) {
        a.classList.toggle('is-active', a === activeLink);
      });
    }

    function clearActive() {
      allLinks.forEach(function (a) {
        a.classList.remove('is-active');
      });
    }

    function updateActive() {
      var atTop = window.scrollY <= SCROLL_TOP_MAX;

      if (atTop && samePageLinks.length) {
        setActiveOnLink(samePageLinks[0]);
        ticking = false;
        return;
      }

      if (!pairs.length) {
        clearActive();
        ticking = false;
        return;
      }

      var activeLink = null;
      var i;
      for (i = 0; i < pairs.length; i++) {
        var top = pairs[i].section.getBoundingClientRect().top;
        if (top <= OFFSET) {
          activeLink = pairs[i].link;
        }
      }
      if (!activeLink) {
        if (atTop) {
          clearActive();
        } else {
          activeLink = pairs[0].link;
        }
      }

      if (activeLink) {
        setActiveOnLink(activeLink);
      }
      ticking = false;
    }

    function onScrollOrResize() {
      if (!ticking) {
        ticking = true;
        requestAnimationFrame(updateActive);
      }
    }

    window.addEventListener('scroll', onScrollOrResize, { passive: true });
    window.addEventListener('resize', onScrollOrResize, { passive: true });
    updateActive();
  })();

});
