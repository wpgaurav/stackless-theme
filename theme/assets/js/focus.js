/*
 * Stackless theme: a focus trap for panels that cover the page (drawers, dialogs, popups, menus).
 * Registered as the stackless-theme-focus script handle; a script that needs it lists the handle
 * as a dependency.
 *
 *   var release = window.StacklessFocus.trap(panel, { onEscape: close, returnFocus: button });
 *   // ...later, when the panel closes:
 *   release();
 *
 * trap() moves focus into the container, keeps Tab and Shift+Tab inside it and calls onEscape on
 * Escape. release() ends the trap and returns focus to returnFocus (by default the element that
 * had focus before), or to the element passed to release() when that one is gone or hidden. Focus
 * the visitor has already moved elsewhere on the page stays where it is. Traps stack: only the
 * newest one handles keys, and releasing it hands the keys back to the one under it.
 */
(function () {
	'use strict';

	if (window.StacklessFocus) {
		return;
	}

	var FOCUSABLE = 'a[href], button:not([disabled]), input:not([type="hidden"]):not([disabled]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])';
	var stack = [];

	function shown(el) {
		return !!(el.offsetWidth || el.offsetHeight || el.getClientRects().length);
	}

	/** The visible elements in root that take focus with Tab, in document order. */
	function focusables(root) {
		return Array.prototype.filter.call(root.querySelectorAll(FOCUSABLE), shown);
	}

	function find(container) {
		for (var i = 0; i < stack.length; i++) {
			if (stack[i].container === container) {
				return stack[i];
			}
		}
		return null;
	}

	function release(trap, fallback) {
		var index = stack.indexOf(trap);
		if (index === -1) {
			return;
		}
		stack.splice(index, 1);
		var focus = document.activeElement;
		if (focus && focus !== document.body && !trap.container.contains(focus)) {
			return;
		}
		var opener = trap.returnFocus;
		var target = opener && opener.isConnected && shown(opener) ? opener : fallback;
		if (target && typeof target.focus === 'function') {
			target.focus({ preventScroll: true });
		}
	}

	/**
	 * Traps focus in container until the returned function is called. A container that is already
	 * trapped keeps its trap, and the call returns the same release function.
	 */
	function trap(container, options) {
		if (!container || container.nodeType !== 1) {
			return function () {};
		}
		var existing = find(container);
		if (existing) {
			return existing.release;
		}
		options = options || {};
		var current = document.activeElement;
		var entry = {
			container: container,
			onEscape: typeof options.onEscape === 'function' ? options.onEscape : null,
			returnFocus: options.returnFocus || (current && current !== document.body && !container.contains(current) ? current : null)
		};
		entry.release = function (fallback) {
			release(entry, fallback);
		};
		stack.push(entry);
		if (!container.hasAttribute('tabindex')) {
			container.setAttribute('tabindex', '-1');
		}
		container.focus({ preventScroll: true });
		return entry.release;
	}

	document.addEventListener('keydown', function (event) {
		var active = stack[stack.length - 1];
		if (!active) {
			return;
		}
		if (event.key === 'Escape') {
			if (active.onEscape) {
				event.preventDefault();
				active.onEscape(event);
			}
			return;
		}
		if (event.key !== 'Tab') {
			return;
		}
		var items = focusables(active.container);
		if (!items.length) {
			event.preventDefault();
			active.container.focus();
			return;
		}
		var first = items[0];
		var last = items[items.length - 1];
		var focus = document.activeElement;
		if (!active.container.contains(focus)) {
			event.preventDefault();
			first.focus();
		} else if (event.shiftKey && (focus === first || focus === active.container)) {
			event.preventDefault();
			last.focus();
		} else if (!event.shiftKey && focus === last) {
			event.preventDefault();
			first.focus();
		}
	}, true);

	window.StacklessFocus = {
		trap: trap,
		focusables: focusables
	};
})();
