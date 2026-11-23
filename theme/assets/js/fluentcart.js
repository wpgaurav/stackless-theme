/*
 * Stackless theme: keyboard and screen-reader behavior over FluentCart's own cart drawer, mobile shop
 * filters and checkout summary toggle. FluentCart still opens and closes everything; this script
 * follows its classes (.open on the drawer, .show on the filter panel, .box_closed on the summary).
 */
(function () {
	'use strict';

	var config = window.stacklessFluentCart || {};
	var i18n = config.i18n || {};
	var FOCUSABLE = 'a[href], button:not([disabled]), input:not([type="hidden"]):not([disabled]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])';
	var watched = typeof WeakSet === 'function' ? new WeakSet() : null;
	var active = null;

	function shown(el) {
		return !!(el.offsetWidth || el.offsetHeight || el.getClientRects().length);
	}

	function focusables(root) {
		return Array.prototype.filter.call(root.querySelectorAll(FOCUSABLE), shown);
	}

	/* ------------------------------------------------ Panels that cover the page */
	// On open focus moves in and Tab stays inside; Escape closes; on close focus returns to the opener.
	function openPanel(panel, close, opener) {
		if (active && active.panel === panel) {
			return;
		}
		var current = document.activeElement;
		active = {
			panel: panel,
			close: close,
			opener: opener || (current && current !== document.body && !panel.contains(current) ? current : null)
		};
		if (!panel.hasAttribute('tabindex')) {
			panel.setAttribute('tabindex', '-1');
		}
		panel.focus({ preventScroll: true });
	}

	function closePanel(panel, fallback) {
		if (!active || active.panel !== panel) {
			return;
		}
		var opener = active.opener;
		active = null;
		var focus = document.activeElement;
		if (focus && focus !== document.body && !panel.contains(focus)) {
			return;
		}
		var target = opener && opener.isConnected && shown(opener) ? opener : fallback;
		if (target) {
			target.focus({ preventScroll: true });
		}
	}

	document.addEventListener('keydown', function (event) {
		if (!active) {
			return;
		}
		if (event.key === 'Escape') {
			event.preventDefault();
			active.close();
			return;
		}
		if (event.key !== 'Tab') {
			return;
		}
		var items = focusables(active.panel);
		if (!items.length) {
			event.preventDefault();
			active.panel.focus();
			return;
		}
		var first = items[0];
		var last = items[items.length - 1];
		var focus = document.activeElement;
		if (!active.panel.contains(focus)) {
			event.preventDefault();
			first.focus();
		} else if (event.shiftKey && (focus === first || focus === active.panel)) {
			event.preventDefault();
			last.focus();
		} else if (!event.shiftKey && focus === last) {
			event.preventDefault();
			first.focus();
		}
	}, true);

	function watch(el, callback) {
		if (!el || !window.MutationObserver || (watched && watched.has(el)) || el.hasAttribute('data-st-watched')) {
			return;
		}
		if (watched) {
			watched.add(el);
		} else {
			el.setAttribute('data-st-watched', '');
		}
		new MutationObserver(function () {
			callback(el);
		}).observe(el, { attributes: true, attributeFilter: ['class'] });
		callback(el);
	}

	/* ------------------------------------------------ Cart drawer */
	// Closed, the drawer is inert, so its links and Close Cart button leave the tab order and the
	// accessibility tree. Open, it is a modal dialog with focus inside.
	function syncDrawer(drawer) {
		var open = drawer.classList.contains('open');
		drawer.inert = !open;
		if (open) {
			drawer.removeAttribute('aria-hidden');
			drawer.setAttribute('aria-modal', 'true');
			openPanel(drawer, function () {
				var close = drawer.querySelector('[data-fluent-cart-cart-collapse-button]');
				if (close) {
					close.click();
				}
			});
		} else {
			drawer.setAttribute('aria-hidden', 'true');
			drawer.removeAttribute('aria-modal');
			closePanel(drawer, document.querySelector('.header-cart'));
		}
	}

	// FluentCart prints "(1 Items)"; the word follows the count.
	function syncDrawerCount() {
		document.querySelectorAll('[data-fluent-cart-cart-drawer] [data-fluent-cart-cart-total-item]').forEach(function (counter) {
			var node = counter.nextSibling;
			while (node && node.nodeType === 3 && !node.textContent.trim()) {
				node = node.nextSibling;
			}
			if (!node || node.nodeType !== 3 || !i18n.item || !i18n.items) {
				return;
			}
			var count = parseInt(counter.textContent, 10) || 0;
			var word = count === 1 ? i18n.item : i18n.items;
			var text = node.textContent.replace(/[^\s()]+/, word);
			if (text !== node.textContent) {
				node.textContent = text;
			}
		});
	}

	/* ------------------------------------------------ Mobile shop filters */
	function filterToggle() {
		var toggles = document.querySelectorAll('[data-fluent-cart-shop-app-filter-toggle-button]');
		for (var i = 0; i < toggles.length; i++) {
			if (shown(toggles[i])) {
				return toggles[i];
			}
		}
		return toggles[0] || null;
	}

	function labelFilterToggles(open) {
		document.querySelectorAll('[data-fluent-cart-shop-app-filter-toggle-button]').forEach(function (button) {
			// FluentCart titles it "Toggle List"; the visible word is Filter.
			button.removeAttribute('title');
			if (i18n.filters) {
				button.setAttribute('aria-label', i18n.filters);
			}
			button.setAttribute('aria-haspopup', 'dialog');
			if (typeof open === 'boolean') {
				button.setAttribute('aria-expanded', open ? 'true' : 'false');
			} else if (!button.hasAttribute('aria-expanded')) {
				button.setAttribute('aria-expanded', 'false');
			}
		});
	}

	function syncFilterPanel(panel) {
		var open = panel.isConnected && panel.classList.contains('show');
		panel.inert = !open;
		panel.setAttribute('role', 'dialog');
		if (i18n.filters) {
			panel.setAttribute('aria-label', i18n.filters);
		}
		labelFilterToggles(open);
		var close = function () {
			var button = panel.querySelector('[data-fluent-cart-shop-app-responsive-filter-close-button]');
			if (button) {
				button.click();
			}
		};
		if (open) {
			panel.setAttribute('aria-modal', 'true');
			if (active && active.panel === panel) {
				return;
			}
			openPanel(panel, close, filterToggle());
			// Start on the first filter control, not the close button.
			var first = focusables(panel).filter(function (el) {
				return !el.matches('[data-fluent-cart-shop-app-responsive-filter-close-button]');
			})[0];
			if (first) {
				first.focus({ preventScroll: true });
			}
		} else {
			panel.removeAttribute('aria-modal');
			closePanel(panel, filterToggle());
		}
	}

	/* ------------------------------------------------ Checkout order summary */
	// FluentCart's toggle is a plain div: make it a button that reports whether the summary is open.
	function syncSummaryToggles() {
		document.querySelectorAll('[data-fluent-cart-checkout-cart-items-toggle]').forEach(function (toggle) {
			if (toggle.getAttribute('role') !== 'button') {
				toggle.setAttribute('role', 'button');
				toggle.setAttribute('tabindex', '0');
			}
			var form = toggle.closest('form') || document;
			var panel = form.querySelector('[data-fluent-cart-checkout-page-cart-items-wrapper]');
			if (panel) {
				if (!panel.id) {
					panel.id = 'st-fc-order-summary';
				}
				toggle.setAttribute('aria-controls', panel.id);
				toggle.setAttribute('aria-expanded', panel.classList.contains('box_closed') ? 'false' : 'true');
			}
		});
	}

	document.addEventListener('keydown', function (event) {
		if (event.key !== 'Enter' && event.key !== ' ') {
			return;
		}
		var toggle = event.target.closest && event.target.closest('[data-fluent-cart-checkout-cart-items-toggle]');
		if (toggle) {
			event.preventDefault();
			toggle.click();
		}
	});

	document.addEventListener('click', function (event) {
		if (event.target.closest && event.target.closest('[data-fluent-cart-checkout-cart-items-toggle]')) {
			// FluentCart's form handler toggles the class first; read the result after it.
			window.setTimeout(syncSummaryToggles, 0);
		}
	});

	/* ------------------------------------------------ Landmarks */
	// FluentCart marks its product grid role="main". Inside the page's own main that is a second
	// main landmark; a named region describes it.
	function fixNestedMain() {
		document.querySelectorAll('[data-fluent-cart-shop-app][role="main"]').forEach(function (grid) {
			if (grid.parentElement && grid.parentElement.closest('main, [role="main"]')) {
				grid.setAttribute('role', 'region');
			}
		});
	}

	/* ------------------------------------------------ Price slider */
	// The slider's handles expose values but no names; they take the labels of the fields they move.
	function nameSliderHandles() {
		document.querySelectorAll('[data-filter-type="range"]').forEach(function (range) {
			[['.noUi-handle-lower', '[data-range-slider-from-value]'], ['.noUi-handle-upper', '[data-range-slider-to-value]']].forEach(function (pair) {
				var handle = range.querySelector(pair[0]);
				var input = range.querySelector(pair[1]);
				var label = input && input.id ? range.querySelector('label[for="' + input.id + '"]') : null;
				var name = label ? label.textContent.trim() : '';
				if (handle && name && handle.getAttribute('aria-label') !== name) {
					handle.setAttribute('aria-label', name);
				}
			});
		});
	}

	/* ------------------------------------------------ Wiring */
	function enhance() {
		fixNestedMain();
		nameSliderHandles();
		watch(document.querySelector('[data-fluent-cart-cart-drawer]'), syncDrawer);
		// The panel FluentCart 2.3 appends to the body; the second class is the older name.
		document.querySelectorAll('[data-fluent-cart-shop-app-responsive-filter-wrapper], .fluent-cart-shop-app-responsive-filter-wrapper').forEach(function (panel) {
			watch(panel, syncFilterPanel);
		});
		if (active && !active.panel.isConnected) {
			// FluentCart rebuilds the filter panel on each open; a removed panel cannot hold focus.
			closePanel(active.panel, filterToggle());
		}
		labelFilterToggles();
		syncSummaryToggles();
		syncDrawerCount();
	}

	var queued = false;
	function schedule() {
		if (!queued) {
			queued = true;
			window.requestAnimationFrame(function () {
				queued = false;
				enhance();
			});
		}
	}

	function start() {
		enhance();
		if (window.MutationObserver) {
			new MutationObserver(schedule).observe(document.body, { childList: true, subtree: true });
		}
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', start);
	} else {
		start();
	}
})();
