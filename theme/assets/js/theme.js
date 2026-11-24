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
			var wasOpen = html.classList.contains('nav-open');
			html.classList.toggle('nav-open', open);
			// The menu sits before its toggle in the source, so move focus into it on open and back to Menu on close.
			if (open) {
				var first = navLinks(target)[0];
				if (first) {
					first.focus();
				}
			} else if (wasOpen && target.contains(document.activeElement)) {
				button.focus();
			}
		}
	}

	function navLinks(nav) {
		return Array.prototype.filter.call(nav.querySelectorAll('a[href], button, select, input:not([type="hidden"])'), function (el) {
			return el.offsetParent !== null;
		});
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
			var openNav = document.querySelector('[data-st-toggle="nav"][aria-expanded="true"]');
			closeAll();
			if (openNav) {
				openNav.focus();
			}
			return;
		}
		// In the open mobile menu, Tab past the last link or Shift+Tab before the first returns to Menu.
		var navToggle = event.key === 'Tab' && document.querySelector('[data-st-toggle="nav"][aria-expanded="true"]');
		if (navToggle) {
			var links = navLinks(panelFor(navToggle));
			if (links.length) {
				if (event.shiftKey && document.activeElement === links[0]) {
					event.preventDefault();
					navToggle.focus();
					return;
				}
				if (!event.shiftKey && document.activeElement === links[links.length - 1]) {
					event.preventDefault();
					navToggle.focus();
					return;
				}
				if (!event.shiftKey && document.activeElement === navToggle) {
					// From Menu, Tab re-enters the list rather than jumping to the page behind it.
					event.preventDefault();
					links[0].focus();
					return;
				}
			}
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

	// A sticky post sidebar only stays pinned while it fits on screen; a taller one scrolls with the page.
	var stickyAside = document.querySelector('.single-layout__aside.is-sticky');
	if (stickyAside) {
		var fitSidebar = function () {
			stickyAside.classList.remove('is-too-tall');
			var top = parseFloat(getComputedStyle(stickyAside).top) || 0;
			stickyAside.classList.toggle('is-too-tall', stickyAside.offsetHeight + top > window.innerHeight);
		};
		fitSidebar();
		window.addEventListener('resize', fitSidebar, { passive: true });
		window.addEventListener('load', fitSidebar);
	}

	// A header laid over the first section turns solid once the page scrolls.
	if (document.body && document.body.classList.contains('has-transparent-header')) {
		var ticking = false;
		var onScroll = function () {
			ticking = false;
			html.classList.toggle('is-scrolled', window.scrollY > 8);
		};
		window.addEventListener('scroll', function () {
			if (!ticking) {
				ticking = true;
				window.requestAnimationFrame(onScroll);
			}
		}, { passive: true });
		onScroll();
	}

	// --st-header-height follows the header's real height when it differs from the CSS value
	// (a wrapping menu, an announcement bar). Nothing is written while they agree.
	var siteHeader = document.querySelector('.site-header');
	if (siteHeader && window.ResizeObserver && document.body) {
		var probe = document.createElement('div');
		probe.style.cssText = 'position:absolute;visibility:hidden;height:var(--st-header-height)';
		document.body.appendChild(probe);
		var cssHeight = probe.offsetHeight;
		probe.remove();
		new window.ResizeObserver(function () {
			var height = siteHeader.offsetHeight;
			if (height && Math.abs(height - cssHeight) > 0.5) {
				html.style.setProperty('--st-header-height', height + 'px');
			} else {
				html.style.removeProperty('--st-header-height');
			}
		}).observe(siteHeader);
	}

	// Header and menu > Hide the header while scrolling down: it comes back on any scroll up, near
	// the top, and whenever focus enters it. CSS also keeps it while the menu or search is open.
	var autohide = document.body && (document.body.classList.contains('has-header-autohide') ? document.querySelector('.site-header') : document.body.classList.contains('has-bar-autohide') && document.querySelector('.site-bar'));
	if (autohide) {
		var lastY = window.scrollY;
		var hidePending = false;
		var updateHidden = function () {
			hidePending = false;
			var y = Math.max(window.scrollY, 0);
			if (Math.abs(y - lastY) < 6) {
				return;
			}
			var down = y > lastY;
			lastY = y;
			var keep = y <= autohide.offsetHeight || html.classList.contains('nav-open') || html.classList.contains('search-open') || autohide.contains(document.activeElement);
			html.classList.toggle('is-header-hidden', down && !keep);
		};
		window.addEventListener('scroll', function () {
			if (!hidePending) {
				hidePending = true;
				window.requestAnimationFrame(updateHidden);
			}
		}, { passive: true });
		autohide.addEventListener('focusin', function () {
			html.classList.remove('is-header-hidden');
		});
	}

	/* ---------------------------------------------------------------- Sidebar layout */
	// The rail collapses on desktops (remembered per browser) and is a focus-trapped drawer below 1080px.
	var rail = document.getElementById('site-rail');
	// Set by the account menu below: closes it and returns true when it was open.
	var closeAccount = function () {
		return false;
	};
	if (rail) {
		var body = document.body;
		var railToggles = document.querySelectorAll('[data-st-rail-toggle]');
		var railRelease = null;
		var railSync = function () {
			var open = wide.matches ? !body.classList.contains('rail-collapsed') : html.classList.contains('rail-open');
			railToggles.forEach(function (button) {
				button.setAttribute('aria-expanded', open ? 'true' : 'false');
			});
		};
		var railDrawer = function (open, from) {
			html.classList.toggle('rail-open', open);
			if (open && window.StacklessFocus) {
				railRelease = window.StacklessFocus.trap(rail, { returnFocus: from, onEscape: function () {
					// Escape closes an open account menu first, then the drawer.
					if (!closeAccount()) {
						railDrawer(false);
					}
				} });
			} else if (!open && railRelease) {
				var release = railRelease;
				railRelease = null;
				release(railToggles[0]);
			}
			railSync();
		};
		document.addEventListener('click', function (event) {
			var button = event.target.closest('[data-st-rail-toggle]');
			if (button && wide.matches) {
				var collapsed = !body.classList.contains('rail-collapsed');
				body.classList.toggle('rail-collapsed', collapsed);
				try {
					window.localStorage.setItem('stacklessRail', collapsed ? 'collapsed' : 'expanded');
				} catch (e) {}
				railSync();
				// The pressed button can vanish with the hidden rail, or the bar's button once it is
				// back; focus moves to the toggle that stays in view.
				if (collapsed && body.classList.contains('rail-collapse-hidden')) {
					document.querySelector('.site-rail-open').focus();
				} else if (!collapsed && !rail.contains(button)) {
					// The rail turns visible a frame later (its visibility transition).
					window.requestAnimationFrame(function () {
						window.requestAnimationFrame(function () {
							rail.querySelector('[data-st-rail-toggle]').focus();
						});
					});
				}
			} else if (button) {
				railDrawer(!html.classList.contains('rail-open'), button);
			} else if (html.classList.contains('rail-open') && !rail.contains(event.target)) {
				railDrawer(false);
			}
		});
		wide.addEventListener('change', function () {
			if (html.classList.contains('rail-open')) {
				railDrawer(false);
			}
			railSync();
		});
		railSync();

		// The rail's search field hands a product search to the live results overlay, when there is one.
		var railSearch = rail.querySelector('[data-st-rail-search]');
		var overlayToggle = document.querySelector('[data-st-toggle="search"]');
		var overlayField = document.querySelector('[data-st-search] input[type="search"]');
		if (railSearch && overlayToggle && overlayField && config.productsApi) {
			railSearch.addEventListener('input', function () {
				if (railSearch.value.trim().length < 2) {
					return;
				}
				if (html.classList.contains('rail-open')) {
					railDrawer(false);
				}
				setOpen(overlayToggle, true);
				overlayField.value = railSearch.value;
				railSearch.value = '';
				overlayField.dispatchEvent(new Event('input'));
			});
		}
	}

	// Account menu (sidebar layout): opens above its button, closes on Escape or a click elsewhere.
	var account = document.querySelector('[data-st-account]');
	if (account) {
		var accountMenu = document.getElementById(account.getAttribute('aria-controls'));
		var accountOpen = function (open) {
			account.setAttribute('aria-expanded', open ? 'true' : 'false');
			accountMenu.hidden = !open;
		};
		document.addEventListener('click', function (event) {
			if (account.contains(event.target)) {
				accountOpen(accountMenu.hidden);
			} else if (!accountMenu.hidden && !accountMenu.contains(event.target)) {
				accountOpen(false);
			}
		});
		closeAccount = function () {
			if (accountMenu.hidden) {
				return false;
			}
			accountOpen(false);
			account.focus();
			return true;
		};
		account.parentNode.addEventListener('keydown', function (event) {
			if (event.key === 'Escape' && closeAccount()) {
				event.stopPropagation();
			}
		});
	}

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
