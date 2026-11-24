/*
 * Stackless blocks: Slider and Carousel.
 *
 * CSS scroll snap does the moving, and the row stays a plain horizontal scroller without this
 * file. This adds the arrows, the dots or progress bar, arrow keys, Home and End, and autoplay
 * with a pause button. Autoplay pauses while the pointer or focus is on the block, while it is
 * off screen or the tab is hidden, and never starts when the visitor prefers reduced motion.
 */
(function () {
	'use strict';

	var motion = window.matchMedia ? window.matchMedia('(prefers-reduced-motion: reduce)') : null;

	function reduced() {
		return !!(motion && motion.matches);
	}

	function format(text, a, b) {
		return String(text || '').replace(/%1\$d|%d/, String(a)).replace(/%2\$d/, String(b));
	}

	// The element in root matching selector that belongs to root, not to a nested scroller.
	function own(root, selector) {
		var found = root.querySelectorAll(selector);
		for (var i = 0; i < found.length; i++) {
			if (found[i].closest('[data-stb-scroller]') === root) {
				return found[i];
			}
		}
		return null;
	}

	function Scroller(root) {
		var track = own(root, '[data-stb-track]');
		if (!track) {
			return;
		}
		var prev = own(root, '.stb-scroller-prev');
		var next = own(root, '.stb-scroller-next');
		var dotsBox = own(root, '.stb-scroller-dots');
		var bar = own(root, '.stb-scroller-progress-bar');
		var pauseButton = own(root, '.stb-scroller-pause');
		var status = own(root, '.stb-scroller-status');
		var loop = root.getAttribute('data-loop') === 'true';
		var delay = parseFloat(root.getAttribute('data-autoplay') || '0') * 1000;
		var rtl = getComputedStyle(track).direction === 'rtl';
		var pages = [0];
		var perView = 1;
		// The dots, progress bar and pause button are only shown while there is more than one page.
		var navBox = own(root, '.stb-scroller-nav');
		var showControls = function (on) {
			if (navBox) {
				navBox.hidden = !on;
			}
		};
		var current = 0;
		var dots = [];
		var ticking = false;
		var announce = false;

		var items = function () {
			return Array.prototype.filter.call(track.children, function (item) {
				return getComputedStyle(item).display !== 'none';
			});
		};

		// How far the row has scrolled from its start edge, positive in either direction.
		var position = function () {
			return Math.abs(track.scrollLeft);
		};

		var maxScroll = function () {
			return Math.max(0, track.scrollWidth - track.clientWidth);
		};

		// The scroll offset that brings an item to the start edge.
		var offsetOf = function (item, base) {
			var box = item.getBoundingClientRect();
			var edge = rtl ? base.right - box.right : box.left - base.left;
			return position() + edge - (parseFloat(getComputedStyle(track).scrollPaddingInlineStart) || 0);
		};

		var measure = function () {
			var list = items();
			var base = track.getBoundingClientRect();
			var max = maxScroll();
			if (list.length < 2 || max < 1) {
				perView = list.length || 1;
				pages = [0];
				showControls(false);
				return;
			}
			showControls(true);
			// The distance from one item to the next is its width plus the gap; the row's width
			// holds one gap fewer than items.
			var step = Math.abs(offsetOf(list[1], base) - offsetOf(list[0], base)) || track.clientWidth;
			var gap = Math.max(0, step - list[0].getBoundingClientRect().width);
			perView = Math.max(1, Math.floor((track.clientWidth + gap + 2) / step));
			pages = [];
			for (var i = 0; i < list.length; i += perView) {
				var target = Math.min(Math.max(0, Math.round(offsetOf(list[i], base))), max);
				pages.push(target);
				if (target >= max - 1) {
					break;
				}
			}
			if (pages[pages.length - 1] < max - 1) {
				pages.push(max);
			}
		};

		var pageNow = function () {
			var at = position();
			if (at >= maxScroll() - 2) {
				return pages.length - 1;
			}
			var best = 0;
			for (var i = 1; i < pages.length; i++) {
				if (Math.abs(pages[i] - at) < Math.abs(pages[best] - at)) {
					best = i;
				}
			}
			return best;
		};

		// Lets the images of the page coming up start loading before it scrolls in.
		var warm = function (page) {
			var list = items();
			var from = page * perView;
			for (var i = from; i < Math.min(list.length, from + perView * 2); i++) {
				var images = list[i].querySelectorAll('img[loading="lazy"]');
				for (var j = 0; j < images.length; j++) {
					images[j].loading = 'eager';
				}
			}
		};

		var goTo = function (page, user) {
			if (!pages.length) {
				return;
			}
			page = Math.max(0, Math.min(pages.length - 1, page));
			warm(page);
			announce = !!user;
			track.scrollTo({ left: rtl ? -pages[page] : pages[page], behavior: reduced() ? 'auto' : 'smooth' });
		};

		var step = function (by, user) {
			var page = pageNow() + by;
			if (page >= pages.length) {
				page = loop || !user ? 0 : pages.length - 1;
			} else if (page < 0) {
				page = loop ? pages.length - 1 : 0;
			}
			goTo(page, user);
		};

		var buildDots = function () {
			if (!dotsBox) {
				return;
			}
			if (dots.length === pages.length) {
				return;
			}
			dotsBox.textContent = '';
			dots = [];
			dotsBox.hidden = pages.length < 2;
			if (pages.length < 2) {
				return;
			}
			dotsBox.setAttribute('role', 'group');
			dotsBox.setAttribute('aria-label', root.getAttribute('data-label-dots') || '');
			pages.forEach(function (offset, index) {
				var dot = document.createElement('button');
				dot.type = 'button';
				dot.className = 'stb-scroller-dot';
				dot.setAttribute('aria-controls', track.id);
				dot.setAttribute('aria-label', format(root.getAttribute('data-label-dot'), index + 1));
				dot.addEventListener('click', function () {
					goTo(index, true);
					restart();
				});
				dotsBox.appendChild(dot);
				dots.push(dot);
			});
		};

		var update = function () {
			ticking = false;
			var page = pageNow();
			var changed = page !== current;
			current = page;
			dots.forEach(function (dot, index) {
				if (index === page) {
					dot.setAttribute('aria-current', 'true');
				} else {
					dot.removeAttribute('aria-current');
				}
			});
			var single = pages.length < 2;
			[prev, next].forEach(function (button) {
				if (button) {
					button.hidden = single;
				}
			});
			if (prev) {
				prev.setAttribute('aria-disabled', !loop && page === 0 ? 'true' : 'false');
			}
			if (next) {
				next.setAttribute('aria-disabled', !loop && page === pages.length - 1 ? 'true' : 'false');
			}
			if (bar) {
				var max = maxScroll();
				var seen = max > 0 ? (track.clientWidth + position()) / track.scrollWidth : 1;
				bar.style.transform = 'scaleX(' + Math.min(1, Math.max(0, seen)).toFixed(4) + ')';
			}
			if (changed && announce && status) {
				status.textContent = format(root.getAttribute('data-label-status'), page + 1, pages.length);
				announce = false;
			}
		};

		var schedule = function () {
			if (!ticking) {
				ticking = true;
				window.requestAnimationFrame(update);
			}
		};

		var refresh = function () {
			rtl = getComputedStyle(track).direction === 'rtl';
			measure();
			buildDots();
			current = -1;
			update();
		};

		/* ------------------------------------------------ Autoplay */
		var timer = 0;
		var playing = delay > 0 && !reduced();
		var held = { hover: false, focus: false, hidden: false, away: false };

		var running = function () {
			return playing && !held.hover && !held.focus && !held.hidden && !held.away && pages.length > 1;
		};

		var stop = function () {
			window.clearTimeout(timer);
			timer = 0;
		};

		var restart = function () {
			stop();
			if (running()) {
				timer = window.setTimeout(function () {
					step(1, false);
					restart();
				}, delay);
			}
		};

		var setPlaying = function (on) {
			playing = on && !reduced();
			if (pauseButton) {
				pauseButton.setAttribute('aria-label', root.getAttribute(playing ? 'data-label-pause' : 'data-label-play') || '');
				var pauseIcon = pauseButton.querySelector('.stb-scroller-pause-icon');
				var playIcon = pauseButton.querySelector('.stb-scroller-play-icon');
				if (pauseIcon) {
					pauseIcon.hidden = !playing;
				}
				if (playIcon) {
					playIcon.hidden = playing;
				}
			}
			root.classList.toggle('is-playing', playing);
			restart();
		};

		/* ------------------------------------------------ Events */
		track.addEventListener('scroll', schedule, { passive: true });

		if (prev) {
			prev.addEventListener('click', function () {
				if (prev.getAttribute('aria-disabled') !== 'true') {
					step(-1, true);
				}
				restart();
			});
		}
		if (next) {
			next.addEventListener('click', function () {
				if (next.getAttribute('aria-disabled') !== 'true') {
					step(1, true);
				}
				restart();
			});
		}

		root.addEventListener('keydown', function (event) {
			var target = event.target;
			if (event.altKey || event.ctrlKey || event.metaKey || !target) {
				return;
			}
			// Keys stay with fields, and with links and buttons inside a slide; the row itself and
			// the block's own controls move it.
			var onControl = target === track || (target.closest && target.closest('.stb-scroller-arrow, .stb-scroller-nav') && target.closest('[data-stb-scroller]') === root);
			if (!onControl) {
				return;
			}
			var forward = rtl ? 'ArrowLeft' : 'ArrowRight';
			var back = rtl ? 'ArrowRight' : 'ArrowLeft';
			if (event.key === forward) {
				step(1, true);
			} else if (event.key === back) {
				step(-1, true);
			} else if (event.key === 'Home') {
				goTo(0, true);
			} else if (event.key === 'End') {
				goTo(pages.length - 1, true);
			} else {
				return;
			}
			event.preventDefault();
			restart();
		});

		if (pauseButton && delay > 0) {
			pauseButton.addEventListener('click', function () {
				// Play restarts rotation at once, even with the pointer or focus on the button,
				// as the carousel pattern asks. Moving onto the slides again pauses it.
				if (!playing) {
					held.hover = false;
					held.focus = false;
				}
				setPlaying(!playing);
			});
			root.addEventListener('mouseenter', function () {
				held.hover = true;
				stop();
			});
			root.addEventListener('mouseleave', function () {
				held.hover = false;
				restart();
			});
			root.addEventListener('focusin', function () {
				held.focus = true;
				stop();
			});
			root.addEventListener('focusout', function (event) {
				if (!event.relatedTarget || !root.contains(event.relatedTarget)) {
					held.focus = false;
					restart();
				}
			});
			document.addEventListener('visibilitychange', function () {
				held.hidden = document.hidden;
				restart();
			});
			if ('IntersectionObserver' in window) {
				new IntersectionObserver(function (entries) {
					held.away = !entries[0].isIntersecting;
					restart();
				}, { threshold: 0.25 }).observe(root);
			}
			var onMotion = function () {
				if (reduced()) {
					setPlaying(false);
				}
				pauseButton.hidden = reduced();
			};
			if (motion) {
				if (motion.addEventListener) {
					motion.addEventListener('change', onMotion);
				} else if (motion.addListener) {
					motion.addListener(onMotion);
				}
			}
			// With reduced motion nothing moves on its own, so there is nothing to pause.
			pauseButton.hidden = reduced();
		}

		if ('ResizeObserver' in window) {
			var lastWidth = 0;
			new ResizeObserver(function () {
				if (track.clientWidth !== lastWidth) {
					lastWidth = track.clientWidth;
					refresh();
				}
			}).observe(track);
		} else {
			window.addEventListener('resize', refresh);
		}

		refresh();
		if (prev) {
			prev.hidden = pages.length < 2;
		}
		if (next) {
			next.hidden = pages.length < 2;
		}
		root.classList.add('is-ready');
		if (delay > 0) {
			setPlaying(playing);
			if (playing) {
				warm(1);
			}
		}
	}

	var roots = document.querySelectorAll('[data-stb-scroller]');
	for (var i = 0; i < roots.length; i++) {
		Scroller(roots[i]);
	}
})();
