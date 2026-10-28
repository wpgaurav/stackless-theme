/* Stackless theme: mobile menu, full-screen search with live product results. */
(function () {
	'use strict';

	var html = document.documentElement;
	var config = window.stacklessTheme || {};
	html.classList.add('js');

	var lastToggle = null;

	function panelFor(button) {
		return document.getElementById(button.getAttribute('aria-controls'));
	}

	function setOpen(button, open) {
		var target = panelFor(button);
		if (!target) {
			return;
		}
		button.setAttribute('aria-expanded', open ? 'true' : 'false');
		if (button.dataset.stToggle === 'search') {
			target.hidden = !open;
			html.classList.toggle('search-open', open);
			if (open) {
				lastToggle = button;
				var field = target.querySelector('input[type="search"]');
				if (field) {
					field.focus();
					field.select();
				}
			} else if (lastToggle) {
				lastToggle.focus();
			}
		} else {
			html.classList.toggle('nav-open', open);
		}
	}

	function closeAll() {
		document.querySelectorAll('[data-st-toggle][aria-expanded="true"]').forEach(function (button) {
			setOpen(button, false);
		});
	}

	document.addEventListener('click', function (event) {
		var button = event.target.closest('[data-st-toggle]');
		if (button) {
			setOpen(button, button.getAttribute('aria-expanded') !== 'true');
			return;
		}
		if (event.target.closest('[data-st-close]')) {
			closeAll();
			return;
		}
		// A click outside the mobile menu closes it. The search overlay covers the page, so only its close button or Esc close it.
		var navToggle = document.querySelector('[data-st-toggle="nav"][aria-expanded="true"]');
		if (navToggle && !panelFor(navToggle).contains(event.target)) {
			setOpen(navToggle, false);
		}
	});

	document.addEventListener('keydown', function (event) {
		if (event.key === 'Escape') {
			closeAll();
			return;
		}
		// Keep keyboard focus inside the open search overlay.
		if (event.key === 'Tab' && html.classList.contains('search-open')) {
			var overlay = document.querySelector('[data-st-search]');
			var focusable = Array.prototype.filter.call(
				overlay.querySelectorAll('a[href], button, input:not([type="hidden"])'),
				function (el) {
					return el.offsetParent !== null;
				}
			);
			if (!focusable.length) {
				return;
			}
			var first = focusable[0];
			var last = focusable[focusable.length - 1];
			if (event.shiftKey && document.activeElement === first) {
				event.preventDefault();
				last.focus();
			} else if (!event.shiftKey && document.activeElement === last) {
				event.preventDefault();
				first.focus();
			}
		}
	});

	var wide = window.matchMedia('(min-width: 1080px)');
	wide.addEventListener('change', function (query) {
		var toggle = document.querySelector('[data-st-toggle="nav"]');
		if (query.matches && toggle) {
			setOpen(toggle, false);
		}
	});

	/* ---------------------------------------------------------------- Live product search */
	var overlay = document.querySelector('[data-st-search]');
	if (!overlay || !config.productsApi || !window.fetch) {
		return;
	}
	var field = overlay.querySelector('input[type="search"]');
	var results = overlay.querySelector('#search-overlay-results');
	var list = overlay.querySelector('[data-st-results-list]');
	var title = overlay.querySelector('[data-st-results-title]');
	var all = overlay.querySelector('[data-st-results-all]');
	var discover = overlay.querySelector('[data-st-discover]');
	var timer = null;
	var controller = null;
	var i18n = config.i18n || {};

	function format(text, value) {
		return String(text || '').replace('%s', value);
	}

	function item(product) {
		var li = document.createElement('li');
		var link = document.createElement('a');
		link.className = 'search-result';
		link.href = product.permalink;

		var media = document.createElement('span');
		media.className = 'search-result__image';
		if (product.images && product.images[0]) {
			var img = document.createElement('img');
			img.src = product.images[0].thumbnail || product.images[0].src;
			img.alt = '';
			img.loading = 'lazy';
			media.appendChild(img);
		}

		var text = document.createElement('span');
		text.className = 'search-result__text';
		var name = document.createElement('span');
		name.className = 'search-result__name';
		// Store API names are HTML-escaped; decode them as text, never as markup.
		name.textContent = new DOMParser().parseFromString(product.name, 'text/html').documentElement.textContent;
		var price = document.createElement('span');
		price.className = 'search-result__price';
		// price_html is the store's own price markup (sale prices, Hide Price Until Login text).
		price.innerHTML = product.price_html || '';
		// Hide Price Until Login's prompt belongs on the product page only.
		if (price.querySelector('.stackless-login-to-see-price')) {
			price.textContent = '';
		}
		// The whole row is a link; unwrap links inside the price (e.g. "Log in to see prices").
		price.querySelectorAll('a').forEach(function (a) {
			a.replaceWith(document.createTextNode(a.textContent));
		});

		text.appendChild(name);
		// Download results name their category (EDD); Store API products carry none here.
		if (product.category) {
			var meta = document.createElement('span');
			meta.className = 'search-result__meta';
			meta.textContent = new DOMParser().parseFromString(product.category, 'text/html').documentElement.textContent;
			text.appendChild(meta);
		}
		text.appendChild(price);
		link.appendChild(media);
		link.appendChild(text);
		li.appendChild(link);
		return li;
	}

	function render(term, products) {
		list.textContent = '';
		results.hidden = false;
		discover.hidden = true;
		title.textContent = format(i18n.results, term);
		all.href = config.searchUrl + '?s=' + encodeURIComponent(term) + '&post_type=' + encodeURIComponent(config.postType || 'product');
		all.hidden = !products.length;
		if (!products.length) {
			var empty = document.createElement('li');
			empty.className = 'search-results__empty';
			empty.textContent = format(i18n.noResults, term);
			list.appendChild(empty);
			return;
		}
		products.forEach(function (product) {
			list.appendChild(item(product));
		});
	}

	function reset() {
		results.hidden = true;
		discover.hidden = false;
		list.textContent = '';
	}

	function search(term) {
		if (controller) {
			controller.abort();
		}
		controller = new AbortController();
		results.classList.add('is-loading');
		var url = config.productsApi + (config.productsApi.indexOf('?') === -1 ? '?' : '&') + 'per_page=8&search=' + encodeURIComponent(term);
		fetch(url, { signal: controller.signal, credentials: 'same-origin' })
			.then(function (response) {
				return response.ok ? response.json() : [];
			})
			.then(function (products) {
				if (field.value.trim() === term) {
					render(term, Array.isArray(products) ? products : []);
				}
			})
			.catch(function () {})
			.then(function () {
				results.classList.remove('is-loading');
			});
	}

	// Arrow keys move between the field and the results; Escape still closes the overlay.
	overlay.addEventListener('keydown', function (event) {
		if (event.key !== 'ArrowDown' && event.key !== 'ArrowUp') {
			return;
		}
		var scope = results.hidden ? overlay.querySelector('[data-st-discover]') : results;
		var links = Array.prototype.slice.call(scope ? scope.querySelectorAll('a.search-result') : []);
		if (!links.length) {
			return;
		}
		var index = links.indexOf(document.activeElement);
		if (document.activeElement !== field && index === -1) {
			return;
		}
		event.preventDefault();
		if (event.key === 'ArrowDown') {
			links[index === -1 ? 0 : Math.min(index + 1, links.length - 1)].focus();
		} else if (index <= 0) {
			field.focus();
		} else {
			links[index - 1].focus();
		}
	});

	field.addEventListener('input', function () {
		var term = field.value.trim();
		clearTimeout(timer);
		if (term.length < 2) {
			if (controller) {
				controller.abort();
			}
			reset();
			return;
		}
		timer = setTimeout(function () {
			search(term);
		}, 220);
	});
})();
