/*
 * Front-end test for the Slider and Carousel blocks.
 *
 * Needs a page built from tests/blocks/fixtures/ (slider, carousel, carousel-products,
 * carousel-query, in that order) with the clear header and no title, and a page without the
 * blocks. Checks, at 1280px and 375px: controls, arrows, dots, keys, autoplay and its pause button,
 * reduced motion, no JavaScript, the dark colors, layout shift, image loading, console errors, and
 * that the page without the blocks loads none of their files. Screenshots go to SHOTS.
 *
 * Usage:
 *   WP_URL=http://localhost:8976 PAGE=/slider-qa/ PLAIN=/plain-qa/ SHOTS=/tmp/shots \
 *   node tests/blocks/front.test.mjs
 */
import { createRequire } from 'node:module';
import { mkdirSync } from 'node:fs';
import { homedir } from 'node:os';

const require = createRequire(import.meta.url);
const expand = (p) => p.replace(/^~/, homedir());
const { chromium } = require(expand(process.env.PLAYWRIGHT_CORE || '~/Development/alpha-chat/node_modules/playwright-core'));
const CHROME = expand(process.env.CHROME || '~/Library/Caches/ms-playwright/chromium_headless_shell-1243/chrome-headless-shell-mac-arm64/chrome-headless-shell');
const URL = process.env.WP_URL || 'http://localhost:8976';
const PAGE = URL + (process.env.PAGE || '/slider-qa/');
const PLAIN = URL + (process.env.PLAIN || '/plain-qa/');
const SHOTS = process.env.SHOTS || '';
if (SHOTS) mkdirSync(SHOTS, { recursive: true });

const failures = [];
const check = (ok, message) => {
	console.log(`${ok ? 'PASS' : 'FAIL'}  ${message}`);
	if (!ok) failures.push(message);
};
const wait = (ms) => new Promise((r) => setTimeout(r, ms));

const browser = await chromium.launch({ executablePath: CHROME });

async function open(url, options = {}) {
	const context = await browser.newContext({ viewport: options.viewport || { width: 1280, height: 900 }, reducedMotion: options.reducedMotion || 'no-preference', javaScriptEnabled: options.js !== false });
	const page = await context.newPage();
	const errors = [];
	page.on('console', (m) => m.type() === 'error' && !/^Failed to load resource/.test(m.text()) && errors.push(m.text()));
	page.on('pageerror', (e) => errors.push(String(e)));
	page.on('response', (r) => r.status() >= 400 && !/favicon/.test(r.url()) && errors.push(`${r.status()} ${r.url()}`));
	if (options.js !== false) {
		await page.addInitScript(() => {
			window.__cls = 0;
			new PerformanceObserver((list) => {
				for (const e of list.getEntries()) if (!e.hadRecentInput) window.__cls += e.value;
			}).observe({ type: 'layout-shift', buffered: true });
		});
	}
	await page.goto(url, { waitUntil: 'load' });
	await wait(600);
	return { context, page, errors };
}

const slider = '.stb-slider';
const products = '.stb-carousel.is-list >> nth=0';

/* ------------------------------------------------------------------ Desktop 1280 */
{
	const { context, page, errors } = await open(PAGE);
	// The script lets the next slide's photo load early, so read loading from the served HTML.
	const served = await page.evaluate(async () => {
		const doc = new DOMParser().parseFromString(await (await fetch(location.href)).text(), 'text/html');
		const imgs = doc.querySelectorAll('.stb-slider .stb-slide-image');
		return { loading: Array.from(imgs).map((i) => i.getAttribute('loading')), fetchpriority: imgs[0].getAttribute('fetchpriority') };
	});
	const state = await page.evaluate(() => {
		const s = document.querySelector('.stb-slider');
		const slides = s.querySelectorAll('.stb-slide');
		const imgs = s.querySelectorAll('.stb-slide-image');
		return {
			ready: s.classList.contains('is-ready'),
			arrows: Array.from(s.querySelectorAll('.stb-scroller-arrow')).filter((b) => !b.hidden).length,
			dots: s.querySelectorAll('.stb-scroller-dot').length,
			current: s.querySelector('.stb-scroller-dot[aria-current="true"]')?.getAttribute('aria-label'),
			labels: Array.from(slides).map((n) => n.getAttribute('aria-label')),
			role: s.getAttribute('aria-roledescription') + '/' + slides[0].getAttribute('aria-roledescription') + '/' + slides[0].getAttribute('role'),
			pause: !s.querySelector('.stb-scroller-pause').hidden,
			headerDark: document.body.classList.contains('has-header-on-dark'),
			contentTop: slides[0].querySelector('.stb-slide-inner').getBoundingClientRect().top,
			header: document.querySelector('.site-header')?.getBoundingClientRect().bottom || 0,
			height: s.getBoundingClientRect().height,
			vh: window.innerHeight,
		};
	});
	check(state.ready, 'slider: script ran (is-ready)');
	check(state.arrows === 2, 'slider: both arrows shown');
	check(state.dots === 3, `slider: one dot per slide (${state.dots})`);
	check(state.current === 'Go to slide 1', 'slider: first dot is current');
	check(state.labels.join('|') === '1 of 3|2 of 3|3 of 3', 'slider: slides labelled "x of n"');
	check(state.role === 'carousel/slide/group', 'slider: carousel and slide roles');
	check(served.loading.join() === 'eager,lazy,lazy' && served.fetchpriority === 'high', `slider: first photo eager with high priority, the rest lazy (${served.loading})`);
	check(state.pause, 'slider: pause button shown with autoplay on');
	check(state.headerDark, 'slider: a slider that opens the page sets has-header-on-dark');
	check(state.contentTop >= state.header, `slider: content starts below the clear header (${Math.round(state.contentTop)} >= ${Math.round(state.header)})`);
	check(Math.abs(state.height - state.vh) < 2, `slider: viewport height fills the screen (${state.height} vs ${state.vh})`);
	if (SHOTS) await page.screenshot({ path: `${SHOTS}/1280-slider.png` });

	// Next arrow, then the dot and the status line.
	await page.hover('.stb-carousel'); // keep the pointer off the slider, but the timer reset by click
	await page.click(`${slider} .stb-scroller-next`);
	await wait(900);
	const afterNext = await page.evaluate(() => {
		const s = document.querySelector('.stb-slider');
		const t = s.querySelector('[data-stb-track]');
		return { left: Math.round(t.scrollLeft), width: t.clientWidth, current: s.querySelector('.stb-scroller-dot[aria-current="true"]')?.getAttribute('aria-label'), status: s.querySelector('.stb-scroller-status').textContent };
	});
	check(Math.abs(afterNext.left - afterNext.width) < 3 && afterNext.current === 'Go to slide 2', `slider: next arrow moves one slide (${afterNext.left}/${afterNext.width}, ${afterNext.current})`);
	check(afterNext.status === 'Slide 2 of 3', `slider: status line announces "${afterNext.status}"`);

	// Keyboard on the focused row.
	await page.focus(`${slider} [data-stb-track]`);
	await page.keyboard.press('End');
	await wait(900);
	const end = await page.evaluate(() => document.querySelector('.stb-slider .stb-scroller-dot[aria-current="true"]')?.getAttribute('aria-label'));
	await page.keyboard.press('ArrowRight');
	await wait(900);
	const wrapped = await page.evaluate(() => document.querySelector('.stb-slider .stb-scroller-dot[aria-current="true"]')?.getAttribute('aria-label'));
	await page.keyboard.press('ArrowLeft');
	await wait(900);
	const back = await page.evaluate(() => document.querySelector('.stb-slider .stb-scroller-dot[aria-current="true"]')?.getAttribute('aria-label'));
	check(end === 'Go to slide 3' && wrapped === 'Go to slide 1' && back === 'Go to slide 3', `slider: End, ArrowRight (loops), ArrowLeft (${end}, ${wrapped}, ${back})`);
	const ring = await page.evaluate(() => getComputedStyle(document.activeElement).outlineStyle);
	check(ring === 'solid', 'slider: focused row shows a focus ring');

	// Shift+Tab from the row reaches the arrows (they come first), then the pause button follows
	// the slides; each shows a ring.
	await page.keyboard.press('Shift+Tab');
	const tabbed = await page.evaluate(() => ({ label: document.activeElement.getAttribute('aria-label'), outline: getComputedStyle(document.activeElement).outlineStyle }));
	check(tabbed.label === 'Next slide' && tabbed.outline === 'solid', `slider: arrows come before the slides in tab order ("${tabbed.label}", ring ${tabbed.outline})`);
	const dotRing = await page.evaluate(() => { const d = document.querySelector('.stb-slider .stb-scroller-dot'); d.focus({ focusVisible: true }); return d.matches(':focus') ; });
	check(dotRing, 'slider: dots take focus');

	// Autoplay: while focus is inside, nothing moves; pause button toggles.
	// The slider fills the screen: scroll it partly up and park the pointer on the page below it.
	await page.evaluate(() => document.activeElement.blur());
	await page.evaluate(() => window.scrollTo({ top: 400, behavior: 'instant' }));
	await page.mouse.move(640, 880);
	await wait(200);
	const before = await page.evaluate(() => document.querySelector('.stb-slider .stb-scroller-dot[aria-current="true"]')?.getAttribute('aria-label'));
	await wait(5800);
	const after = await page.evaluate(() => document.querySelector('.stb-slider .stb-scroller-dot[aria-current="true"]')?.getAttribute('aria-label'));
	check(before !== after, `slider: autoplay advances (${before} -> ${after})`);
	await page.hover(slider);
	const held1 = await page.evaluate(() => document.querySelector('.stb-slider .stb-scroller-dot[aria-current="true"]')?.getAttribute('aria-label'));
	await wait(5800);
	const held2 = await page.evaluate(() => document.querySelector('.stb-slider .stb-scroller-dot[aria-current="true"]')?.getAttribute('aria-label'));
	check(held1 === held2, 'slider: autoplay pauses while hovered');
	await page.click(`${slider} .stb-scroller-pause`);
	const label = await page.getAttribute(`${slider} .stb-scroller-pause`, 'aria-label');
	check(label === 'Play the slideshow', `slider: pause button switches to "${label}"`);
	await page.mouse.move(640, 880);
	await page.evaluate(() => document.activeElement.blur());
	const p1 = await page.evaluate(() => document.querySelector('.stb-slider .stb-scroller-dot[aria-current="true"]')?.getAttribute('aria-label'));
	await wait(5800);
	const p2 = await page.evaluate(() => document.querySelector('.stb-slider .stb-scroller-dot[aria-current="true"]')?.getAttribute('aria-label'));
	check(p1 === p2, 'slider: paused slider stays put');

	// Carousels.
	const carousels = await page.evaluate(() => Array.from(document.querySelectorAll('.stb-carousel')).map((c) => {
		const track = c.querySelector('[data-stb-track]');
		const items = Array.from(track.children);
		const gap = parseFloat(getComputedStyle(track).columnGap) || 0;
		return {
			label: c.getAttribute('aria-label'),
			list: c.classList.contains('is-list'),
			tag: track.tagName,
			items: items.length,
			perView: Math.round(((track.clientWidth + gap) / (items[0].getBoundingClientRect().width + gap)) * 10) / 10,
			dots: c.querySelectorAll('.stb-scroller-dot').length,
			progress: !!c.querySelector('.stb-scroller-progress-bar'),
			arrows: Array.from(c.querySelectorAll('.stb-scroller-arrow')).filter((b) => !b.hidden).length,
			itemLabel: items[0].getAttribute('aria-label'),
			oneRow: new Set(items.map((i) => Math.round(i.getBoundingClientRect().top))).size === 1,
		};
	}));
	const [cards, prods, posts] = carousels;
	check(cards && cards.perView === 3 && cards.dots === 2 && cards.itemLabel === '1 of 5' && cards.oneRow, `carousel (blocks): 3 in view, 2 pages, items "x of n" ${JSON.stringify(cards)}`);
	check(prods && prods.list && prods.tag === 'UL' && prods.perView === 4 && prods.progress && prods.oneRow, `carousel (Product Collection): scrolls the product list, 4 in view, progress bar ${JSON.stringify(prods)}`);
	check(posts && posts.list && posts.perView === 3 && posts.dots === 2 && posts.oneRow, `carousel (Query Loop): scrolls the post list, 3 in view ${JSON.stringify(posts)}`);

	const prevDisabled = await page.evaluate(() => document.querySelectorAll('.stb-carousel')[0].querySelector('.stb-scroller-prev').getAttribute('aria-disabled'));
	check(prevDisabled === 'true', 'carousel: previous arrow is aria-disabled at the start without loop');
	await page.click('.stb-carousel >> nth=0 >> .stb-scroller-next');
	await wait(900);
	const moved = await page.evaluate(() => {
		const c = document.querySelectorAll('.stb-carousel')[0];
		const t = c.querySelector('[data-stb-track]');
		return { left: Math.round(t.scrollLeft), max: t.scrollWidth - t.clientWidth, next: c.querySelector('.stb-scroller-next').getAttribute('aria-disabled') };
	});
	check(Math.abs(moved.left - moved.max) < 3 && moved.next === 'true', `carousel: next moves a page and disables at the end ${JSON.stringify(moved)}`);
	await page.click('.stb-carousel.is-list >> nth=0 >> .stb-scroller-next');
	await wait(900);
	const bar = await page.evaluate(() => document.querySelector('.stb-carousel.is-list .stb-scroller-progress-bar').style.transform);
	check(/scaleX\((0\.[5-9]|1)/.test(bar), `carousel: progress bar follows the scroll (${bar})`);
	if (SHOTS) {
		await page.locator('.stb-carousel').first().scrollIntoViewIfNeeded();
		await page.screenshot({ path: `${SHOTS}/1280-carousels.png`, fullPage: true });
	}

	// Dark colors.
	await page.evaluate(() => document.documentElement.classList.add('stackless-dark'));
	await page.mouse.move(5, 5);
	await wait(400);
	const dark = await page.evaluate(() => {
		const b = document.querySelectorAll('.stb-carousel')[0].querySelector('.stb-scroller-arrow');
		return getComputedStyle(b).backgroundColor;
	});
	check(dark === 'rgb(27, 27, 27)', `dark: arrows take the dark surface (${dark})`);
	if (SHOTS) {
		await page.locator('.stb-carousel').first().scrollIntoViewIfNeeded();
		await page.screenshot({ path: `${SHOTS}/1280-dark.png` });
	}

	const cls = await page.evaluate(() => window.__cls);
	check(cls < 0.02, `no layout shift (CLS ${cls.toFixed(4)})`);
	check(errors.length === 0, `no console errors at 1280 ${errors.length ? JSON.stringify(errors) : ''}`);
	await context.close();
}

/* ------------------------------------------------------------------ Phone 375 */
{
	const { context, page, errors } = await open(PAGE, { viewport: { width: 375, height: 760 } });
	const phone = await page.evaluate(() => {
		const s = document.querySelector('.stb-slider');
		const inner = s.querySelector('.stb-slide-inner').getBoundingClientRect();
		const cs = Array.from(document.querySelectorAll('.stb-carousel')).map((c) => {
			const track = c.querySelector('[data-stb-track]');
			const items = Array.from(track.children);
			const gap = parseFloat(getComputedStyle(track).columnGap) || 0;
			return Math.round(((track.clientWidth + gap) / (items[0].getBoundingClientRect().width + gap)) * 10) / 10;
		});
		return { overflow: document.documentElement.scrollWidth > window.innerWidth, innerLeft: inner.left, innerRight: inner.right, perView: cs };
	});
	check(!phone.overflow, 'phone: no horizontal page scroll');
	check(phone.innerLeft >= 0 && phone.innerRight <= 375, 'phone: slide content fits the screen');
	check(phone.perView[0] === 1.2 && phone.perView[1] === 1.5 && phone.perView[2] === 1, `phone: items per view follow the phone setting (${phone.perView})`);
	// A swipe: scroll the row by hand, the dots follow.
	await page.evaluate(() => { const t = document.querySelector('.stb-slider [data-stb-track]'); t.scrollTo({ left: t.clientWidth, behavior: 'auto' }); });
	await wait(500);
	const dot = await page.evaluate(() => document.querySelector('.stb-slider .stb-scroller-dot[aria-current="true"]')?.getAttribute('aria-label'));
	check(dot === 'Go to slide 2', `phone: dots follow a swipe (${dot})`);
	if (SHOTS) {
		await page.evaluate(() => { const t = document.querySelector('.stb-slider [data-stb-track]'); t.scrollTo({ left: 0, behavior: 'auto' }); });
		await page.screenshot({ path: `${SHOTS}/375-slider.png` });
		await page.locator('.stb-carousel').first().scrollIntoViewIfNeeded();
		await page.screenshot({ path: `${SHOTS}/375-carousel.png` });
	}
	check(errors.length === 0, `no console errors at 375 ${errors.length ? JSON.stringify(errors) : ''}`);
	await context.close();
}

/* ------------------------------------------------------------------ Reduced motion */
{
	const { context, page } = await open(PAGE, { reducedMotion: 'reduce' });
	await page.mouse.move(5, 890);
	const a = await page.evaluate(() => ({ dot: document.querySelector('.stb-slider .stb-scroller-dot[aria-current="true"]')?.getAttribute('aria-label'), pause: document.querySelector('.stb-slider .stb-scroller-pause').hidden, behavior: getComputedStyle(document.querySelector('.stb-slider [data-stb-track]')).scrollBehavior }));
	await wait(5800);
	const b = await page.evaluate(() => document.querySelector('.stb-slider .stb-scroller-dot[aria-current="true"]')?.getAttribute('aria-label'));
	check(a.dot === b, 'reduced motion: no autoplay');
	check(a.pause, 'reduced motion: no pause button (nothing moves)');
	check(a.behavior === 'auto', 'reduced motion: no smooth scrolling');
	await context.close();
}

/* ------------------------------------------------------------------ No JavaScript */
{
	const { context, page } = await open(PAGE, { js: false });
	const nojs = await page.evaluate(() => {
		const s = document.querySelector('.stb-slider');
		const t = s.querySelector('[data-stb-track]');
		const c = document.querySelector('.stb-carousel [data-stb-track]');
		const before = t.scrollLeft;
		t.scrollTo({ left: t.clientWidth, behavior: 'instant' });
		return {
			arrows: Array.from(document.querySelectorAll('.stb-scroller-arrow')).every((b) => getComputedStyle(b).display === 'none'),
			// display: none where the browser reports (scripting: none); hidden otherwise.
			nav: Array.from(document.querySelectorAll('.stb-scroller-nav')).every((n) => getComputedStyle(n).display === 'none' || getComputedStyle(n).visibility === 'hidden'),
			scrolls: t.scrollWidth > t.clientWidth && c.scrollWidth > c.clientWidth,
			moved: t.scrollLeft > before,
			snap: getComputedStyle(t).scrollSnapType,
			focusable: t.tabIndex === 0,
		};
	}).catch((e) => ({ error: String(e) }));
	check(nojs.arrows && nojs.nav, `no JS: arrows and dots stay hidden ${JSON.stringify(nojs)}`);
	check(nojs.scrolls && nojs.moved && /x mandatory/.test(nojs.snap) && nojs.focusable, 'no JS: rows are native scroll-snap scrollers reachable by keyboard');
	if (SHOTS) await page.screenshot({ path: `${SHOTS}/nojs.png` });
	await context.close();
}

/* ------------------------------------------------------------------ A page without the blocks */
{
	const { context, page, errors } = await open(PLAIN);
	const assets = await page.evaluate(() => Array.from(document.querySelectorAll('link[rel="stylesheet"], script[src]')).map((n) => n.href || n.src).filter((u) => /blocks\/(scroller|slider|carousel|bridge)/.test(u)));
	check(assets.length === 0, `plain page: no Slider or Carousel files ${assets.length ? JSON.stringify(assets) : ''}`);
	check(errors.length === 0, 'plain page: no console errors');
	await context.close();
}

await browser.close();
console.log(failures.length ? `\n${failures.length} failed` : '\nAll passed');
process.exit(failures.length ? 1 : 0);
