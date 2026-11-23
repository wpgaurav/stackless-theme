/*
 * Stackless blocks: Spoiler.
 * Reveals and hides the content, and swaps the button's label.
 */
(function () {
	'use strict';

	document.addEventListener('click', function (event) {
		var toggle = event.target.closest ? event.target.closest('.md-spoiler-toggle') : null;
		var spoiler = toggle ? toggle.closest('.md-spoiler') : null;
		var content = spoiler ? spoiler.querySelector('.md-spoiler-content') : null;
		if (!content) {
			return;
		}
		event.preventDefault();
		var reveal = content.hidden;
		var label = toggle.querySelector('.md-spoiler-label');
		content.hidden = !reveal;
		toggle.setAttribute('aria-expanded', reveal ? 'true' : 'false');
		spoiler.classList.toggle('is-revealed', reveal);
		if (label) {
			label.textContent = toggle.getAttribute(reveal ? 'data-revealed-label' : 'data-label') || label.textContent;
		}
	});
})();
