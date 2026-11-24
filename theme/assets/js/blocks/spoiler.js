/*
 * Stackless blocks: Spoiler.
 * Reveals and hides the content, and swaps the button's label.
 */
(function () {
	'use strict';

	document.addEventListener('click', function (event) {
		var toggle = event.target.closest ? event.target.closest('.stb-spoiler-toggle') : null;
		var spoiler = toggle ? toggle.closest('.stb-spoiler') : null;
		var content = spoiler ? spoiler.querySelector('.stb-spoiler-content') : null;
		if (!content) {
			return;
		}
		event.preventDefault();
		var reveal = content.hidden;
		var label = toggle.querySelector('.stb-spoiler-label');
		content.hidden = !reveal;
		toggle.setAttribute('aria-expanded', reveal ? 'true' : 'false');
		spoiler.classList.toggle('is-revealed', reveal);
		if (label) {
			label.textContent = toggle.getAttribute(reveal ? 'data-revealed-label' : 'data-label') || label.textContent;
		}
	});
})();
