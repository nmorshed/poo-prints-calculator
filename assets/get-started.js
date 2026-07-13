(function () {
	var rolloutGroup = document.querySelector('[data-pp-group="rollout"]');
	if (!rolloutGroup) return;

	// Init: Option 1 selected by default — hide Option 2 card on load
	var card2init = document.getElementById('pp-table-opt2');
	if (card2init) card2init.style.display = 'none';

	// Pre-select Option 1 card on load
	var defaultCard = rolloutGroup.querySelector('[data-pp-option="standard"]');
	if (defaultCard) defaultCard.classList.add('pp-card--selected');

	rolloutGroup.addEventListener('click', function (e) {
		var card = e.target.closest('[data-pp-option]');
		if (!card) return;

		var selected = card.dataset.ppOption;

		rolloutGroup.querySelectorAll('[data-pp-option]').forEach(function (c) {
			var isSelected = c.dataset.ppOption === selected;
			c.classList.toggle('pp-card--selected', isSelected);

			var head = c.querySelector('.pp-card-head');
			var pill = c.querySelector('.pp-pill-selected');
			var btn  = c.querySelector('.pp-link-switch');

			if (isSelected) {
				if (btn) btn.remove();
				if (!pill) {
					var newPill = document.createElement('span');
					newPill.className = 'pp-pill-selected';
					newPill.innerHTML = '&#10003; Selected';
					head.appendChild(newPill);
				}
			} else {
				if (pill) pill.remove();
				if (!btn) {
					var newBtn = document.createElement('button');
					newBtn.type = 'button';
					newBtn.className = 'pp-link-switch';
					newBtn.innerHTML = 'Switch to this option &#8594;';
					head.appendChild(newBtn);
				}
			}
		});

		var step3      = document.getElementById('pp-section-step3');
		var confirmOpt1 = document.getElementById('pp-confirm-opt1');
		var confirmOpt2 = document.getElementById('pp-confirm-opt2');
		var card1       = document.getElementById('pp-table-opt1');
		var card2       = document.getElementById('pp-table-opt2');
		var heroOpt1    = document.getElementById('pp-hero-opt1');
		var heroOpt2    = document.getElementById('pp-hero-opt2');
		var isPhased    = selected === 'phased';

		if (step3)       step3.style.display       = isPhased ? 'none' : '';
		if (confirmOpt1) confirmOpt1.style.display = isPhased ? 'none' : '';
		if (confirmOpt2) confirmOpt2.style.display = isPhased ? '' : 'none';
		if (card1)       card1.style.display       = isPhased ? 'none' : '';
		if (card2)       card2.style.display       = isPhased ? '' : 'none';
		if (heroOpt1)    heroOpt1.style.display    = isPhased ? 'none' : '';
		if (heroOpt2)    heroOpt2.style.display    = isPhased ? '' : 'none';
	});

	var paymentGroup = document.querySelector('[data-pp-group="payment"]');
	if (paymentGroup) {
		paymentGroup.addEventListener('click', function (e) {
			var card = e.target.closest('[data-pp-plan]');
			if (!card) return;

			var plan = card.dataset.ppPlan;
			var full    = document.getElementById('pp-confirm-opt1-full');
			var monthly = document.getElementById('pp-confirm-opt1-monthly');

			if (full)    full.style.display    = plan === 'full'    ? '' : 'none';
			if (monthly) monthly.style.display = plan === 'monthly' ? '' : 'none';

			paymentGroup.querySelectorAll('[data-pp-plan]').forEach(function (c) {
				var isSelected = c.dataset.ppPlan === plan;
				c.classList.toggle('pp-card--selected', isSelected);

				var head = c.querySelector('.pp-plan-head');
				var radio = head ? head.querySelector('.pp-radio') : null;
				if (radio) radio.style.background = isSelected ? 'var(--pp-red)' : '';
			});
		});
	}
})();
