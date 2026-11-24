/*
 * Editor test for the Slider and Carousel blocks.
 *
 * In a real block editor it:
 *   1. parses every fixture in tests/blocks/fixtures/ and checks each block is valid and that
 *      serializing the parsed blocks gives the fixture back (stable save output), then saves them
 *      all in a page and opens it in the editor;
 *   2. inserts each block from the inserter, applies typical settings, saves, reloads, and checks
 *      every block is still valid with no "unexpected or invalid content" warning;
 *   3. fails on console errors.
 *
 * Usage:
 *   WP_URL=http://localhost:8976 WP_USER=admin WP_PASS=... \
 *   PLAYWRIGHT_CORE=~/Development/alpha-chat/node_modules/playwright-core \
 *   CHROME=~/Library/Caches/ms-playwright/chromium_headless_shell-1243/chrome-headless-shell-mac-arm64/chrome-headless-shell \
 *   node tests/blocks/editor.test.mjs
 */
import { createRequire } from 'node:module';
import { readFileSync, readdirSync, writeFileSync } from 'node:fs';
import { dirname, join } from 'node:path';
import { fileURLToPath } from 'node:url';
import { homedir } from 'node:os';

const here = dirname(fileURLToPath(import.meta.url));
const require = createRequire(import.meta.url);
const expand = (p) => p.replace(/^~/, homedir());
const { chromium } = require(expand(process.env.PLAYWRIGHT_CORE || '~/Development/alpha-chat/node_modules/playwright-core'));
const CHROME = expand(process.env.CHROME || '~/Library/Caches/ms-playwright/chromium_headless_shell-1243/chrome-headless-shell-mac-arm64/chrome-headless-shell');
const URL = process.env.WP_URL || 'http://localhost:8976';

const failures = [];
const check = (ok, message) => {
	console.log(`${ok ? 'PASS' : 'FAIL'}  ${message}`);
	if (!ok) failures.push(message);
};

const browser = await chromium.launch({ executablePath: CHROME });
const page = await browser.newPage({ viewport: { width: 1440, height: 1000 } });
const consoleErrors = [];
page.on('console', (msg) => {
	if (msg.type() === 'error') consoleErrors.push(msg.text());
});
page.on('pageerror', (err) => consoleErrors.push(String(err)));
page.on('response', (res) => {
	if (res.status() >= 400) consoleErrors.push(`${res.status()} ${res.url()}`);
});

await page.goto(`${URL}/wp-login.php`);
await page.fill('#user_login', process.env.WP_USER || 'admin');
await page.fill('#user_pass', process.env.WP_PASS || '');
await Promise.all([page.waitForNavigation(), page.click('#wp-submit')]);

async function openEditor(url) {
	await page.goto(url);
	await page.waitForFunction(() => window.wp && wp.data && wp.data.select('core/block-editor') && wp.blocks.getBlockType('stackless/slider') && wp.blocks.getBlockType('stackless/carousel'), null, { timeout: 60000 });
	await page.evaluate(() => {
		const prefs = wp.data.dispatch('core/preferences');
		if (prefs) prefs.set('core/edit-post', 'welcomeGuide', false);
	});
	await page.waitForTimeout(500);
}

// Every block, nested ones too, with its validity.
const invalidBlocks = () => page.evaluate(() => {
	const bad = [];
	const walk = (blocks) => blocks.forEach((b) => {
		if (b.isValid === false) bad.push(b.name);
		walk(b.innerBlocks || []);
	});
	walk(wp.data.select('core/block-editor').getBlocks());
	return bad;
});

const invalidWarnings = () => page.evaluate(() => {
	const doc = document.querySelector('iframe[name="editor-canvas"]')?.contentDocument || document;
	return Array.from(doc.querySelectorAll('.block-editor-warning')).map((n) => n.textContent.trim());
});

/* ------------------------------------------------------------------ 1. Fixtures */
await openEditor(`${URL}/wp-admin/post-new.php?post_type=page`);
const fixtures = readdirSync(join(here, 'fixtures')).filter((f) => f.endsWith('.html'));
for (const file of fixtures) {
	const html = readFileSync(join(here, 'fixtures', file), 'utf8');
	const result = await page.evaluate((source) => {
		const blocks = wp.blocks.parse(source);
		const bad = [];
		const names = [];
		const walk = (list) => list.forEach((b) => {
			names.push(b.name);
			if (!b.isValid) bad.push(b.name + ': ' + (b.validationIssues || []).map((i) => i.args && i.args[0]).join(' '));
			walk(b.innerBlocks || []);
		});
		walk(blocks);
		const norm = (s) => s.replace(/\s+/g, ' ').trim();
		return { bad, names, stable: norm(wp.blocks.serialize(blocks)) === norm(source), out: wp.blocks.serialize(blocks) };
	}, html);
	check(result.bad.length === 0, `${file}: every block parses as valid ${result.bad.length ? JSON.stringify(result.bad) : ''}`);
	check(result.stable, `${file}: serializing the parsed blocks gives the fixture back`);
	if (!result.stable && process.env.DUMP) writeFileSync(join(process.env.DUMP, file), result.out);
}

// All fixtures in one saved page, opened in the editor as a stored post would be.
{
	const content = fixtures.map((f) => readFileSync(join(here, 'fixtures', f), 'utf8')).join('\n');
	const id = await page.evaluate((c) => wp.apiFetch({ path: '/wp/v2/pages', method: 'POST', data: { title: 'Fixture test', content: c, status: 'draft' } }).then((r) => r.id), content);
	await openEditor(`${URL}/wp-admin/post.php?post=${id}&action=edit`);
	await page.waitForTimeout(2000);
	const bad = await invalidBlocks();
	check(bad.length === 0, `fixtures saved in a page: every block valid in the editor ${bad.length ? JSON.stringify(bad) : ''}`);
	const warnings = await invalidWarnings();
	check(warnings.length === 0, `fixtures saved in a page: no invalid-block warning ${warnings.length ? JSON.stringify(warnings) : ''}`);
	await page.evaluate((i) => wp.apiFetch({ path: `/wp/v2/pages/${i}?force=true`, method: 'DELETE' }), id);
}

/* ------------------------------------------------------------------ 2. Insert, save, reload */
async function insertFromInserter(search, title) {
	await page.click('button.editor-document-tools__inserter-toggle, button[aria-label="Block Inserter"], button[aria-label="Toggle block inserter"]');
	const field = page.locator('.block-editor-inserter__search input, input[type="search"][placeholder*="Search"]').first();
	await field.fill(search);
	await page.locator('.block-editor-block-types-list__item', { hasText: title }).first().click();
	await page.waitForTimeout(800);
}

const cases = [
	{
		name: 'stackless/slider',
		search: 'Slider',
		title: 'Slider',
		settings: { label: 'New season', height: 'viewport', viewportHeight: 90, autoplay: true, interval: 5, loop: true },
		child: async () => page.evaluate(() => {
			// Give the first slide an image, a focal point and an overlay, as the inspector would.
			const slider = wp.data.select('core/block-editor').getBlocks().find((b) => b.name === 'stackless/slider');
			const slide = slider.innerBlocks[0];
			wp.data.dispatch('core/block-editor').updateBlockAttributes(slide.clientId, { mediaId: 11, mediaUrl: '/wp-content/uploads/2026/11/slide-1.jpg', focalPoint: { x: 0.4, y: 0.3 }, dimRatio: 50, contentPosition: 'bottom left' });
			const inner = slide.innerBlocks.find((b) => b.name === 'core/heading');
			if (inner) wp.data.dispatch('core/block-editor').updateBlockAttributes(inner.clientId, { content: 'Linen for long days' });
			return slider.innerBlocks.length;
		}),
	},
	{
		name: 'stackless/carousel',
		search: 'Carousel',
		title: 'Carousel',
		settings: { label: 'Collections', perView: 4, perViewTablet: 2.5, perViewPhone: 1.2, gap: 'small', pagination: 'progress', loop: true },
		child: async () => page.evaluate(() => {
			const carousel = wp.data.select('core/block-editor').getBlocks().find((b) => b.name === 'stackless/carousel');
			return carousel.innerBlocks.length;
		}),
	},
];

for (const item of cases) {
	await openEditor(`${URL}/wp-admin/post-new.php?post_type=page`);
	await page.evaluate(() => wp.data.dispatch('core/editor').editPost({ title: 'Editor test' }));
	await insertFromInserter(item.search, item.title);
	const inserted = await page.evaluate((name) => wp.data.select('core/block-editor').getBlocks().filter((b) => b.name === name).map((b) => b.clientId), item.name);
	check(inserted.length === 1, `${item.name}: inserted from the inserter`);
	if (!inserted.length) continue;
	await page.evaluate(([id, settings]) => wp.data.dispatch('core/block-editor').updateBlockAttributes(id, settings), [inserted[0], item.settings]);
	const children = await item.child();
	check(children >= 2, `${item.name}: starts with ${children} inner items`);
	await page.waitForTimeout(500);
	check((await invalidBlocks()).length === 0, `${item.name}: valid before saving`);

	await page.evaluate(() => wp.data.dispatch('core/editor').savePost());
	await page.waitForFunction(() => !wp.data.select('core/editor').isSavingPost() && wp.data.select('core/editor').getCurrentPostId() && !wp.data.select('core/editor').isEditedPostDirty(), null, { timeout: 30000 });
	const postId = await page.evaluate(() => wp.data.select('core/editor').getCurrentPostId());
	const saved = await page.evaluate(() => wp.data.select('core/editor').getEditedPostContent());

	await openEditor(`${URL}/wp-admin/post.php?post=${postId}&action=edit`);
	await page.waitForTimeout(1500);
	const bad = await invalidBlocks();
	check(bad.length === 0, `${item.name}: every block valid after reload ${bad.length ? JSON.stringify(bad) : ''}`);
	const warnings = await invalidWarnings();
	check(warnings.length === 0, `${item.name}: no invalid-block warning after reload ${warnings.length ? JSON.stringify(warnings) : ''}`);
	const reloaded = await page.evaluate(() => wp.data.select('core/editor').getEditedPostContent());
	check(reloaded === saved, `${item.name}: content unchanged by the reload`);
	const attrs = await page.evaluate((name) => wp.data.select('core/block-editor').getBlocks().find((b) => b.name === name)?.attributes, item.name);
	check(Object.entries(item.settings).every(([k, v]) => attrs && attrs[k] === v), `${item.name}: settings kept after reload`);

	// The one-at-a-time / all-slides view works on the slider.
	if (item.name === 'stackless/slider') {
		const counts = await page.evaluate(async () => {
			const doc = document.querySelector('iframe[name="editor-canvas"]')?.contentDocument || document;
			const visible = () => Array.from(doc.querySelectorAll('.wp-block-stackless-slide')).filter((n) => n.offsetParent !== null).length;
			const one = visible();
			const toggle = Array.from(doc.querySelectorAll('.stb-slider-editor-bar button')).find((b) => /Show all/.test(b.textContent));
			toggle && toggle.click();
			await new Promise((r) => setTimeout(r, 300));
			const all = visible();
			return { one, all };
		});
		check(counts.one === 1 && counts.all >= 2, `stackless/slider: shows one slide at a time (${counts.one}) or all (${counts.all})`);
		const added = await page.evaluate(async () => {
			const doc = document.querySelector('iframe[name="editor-canvas"]')?.contentDocument || document;
			const before = wp.data.select('core/block-editor').getBlocks()[0].innerBlocks.length;
			const add = Array.from(doc.querySelectorAll('.stb-slider-editor-bar button')).find((b) => /Add slide/.test(b.textContent));
			add && add.click();
			await new Promise((r) => setTimeout(r, 300));
			return [before, wp.data.select('core/block-editor').getBlocks()[0].innerBlocks.length];
		});
		check(added[1] === added[0] + 1, `stackless/slider: Add slide adds a slide (${added.join(' -> ')})`);
	}

	await page.evaluate((id) => wp.apiFetch({ path: `/wp/v2/pages/${id}?force=true`, method: 'DELETE' }), postId);
}

// The browser's own "Failed to load resource" line is listed with its URL by the response listener.
const relevant = consoleErrors.filter((e) => !/favicon|net::ERR_|^Failed to load resource/.test(e));
check(relevant.length === 0, `no console errors ${relevant.length ? JSON.stringify(relevant.slice(0, 5)) : ''}`);

await browser.close();
console.log(failures.length ? `\n${failures.length} failed` : '\nAll passed');
process.exit(failures.length ? 1 : 0);
