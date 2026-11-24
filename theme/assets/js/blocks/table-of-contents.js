/*
 * Stackless blocks: Table of Contents.
 * A collapsible table of contents opens and closes from its title row.
 */
(function () {
	'use strict';

	document.addEventListener('click', function (event) {
		var head = event.target.closest ? event.target.closest('.stb-toc-collapsible .stb-toc-head') : null;
		var toc = head ? head.closest('.stb-toc') : null;
		var toggle = toc ? toc.querySelector('.stb-toc-toggle') : null;
		var body = toc ? toc.querySelector('.stb-toc-body') : null;
		if (!toggle || !body) {
			return;
		}
		var open = body.hidden;
		body.hidden = !open;
		toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
		toc.classList.toggle('is-collapsed', !open);
	});
})();
