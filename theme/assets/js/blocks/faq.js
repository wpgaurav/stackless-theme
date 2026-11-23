/*
 * Stackless blocks: FAQ accordion.
 * Opens and closes answers, keeping the active class, the hidden attribute and aria-expanded in
 * step. One answer is open at a time unless the FAQ carries data-accordion-multi.
 */
(function () {
	'use strict';

	function set(item, open) {
		var button = item.querySelector('.accordion-title');
		var panel = item.querySelector('.accordion-content');
		item.classList.toggle('active', open);
		if (button) {
			button.setAttribute('aria-expanded', open ? 'true' : 'false');
		}
		if (panel) {
			panel.hidden = !open;
		}
	}

	document.addEventListener('click', function (event) {
		var button = event.target.closest ? event.target.closest('.faq-accordion .accordion-title') : null;
		if (!button) {
			return;
		}
		var item = button.closest('.accordion-group');
		var faq = button.closest('.faq-accordion');
		if (!item || !faq) {
			return;
		}
		var open = !item.classList.contains('active');
		if (open && !faq.hasAttribute('data-accordion-multi')) {
			Array.prototype.forEach.call(faq.querySelectorAll('.accordion-group.active'), function (other) {
				if (other !== item) {
					set(other, false);
				}
			});
		}
		set(item, open);
	});
})();
