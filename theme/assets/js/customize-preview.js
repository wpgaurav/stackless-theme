/**
 * Customizer preview: redraws the color and font custom properties as their settings change, so
 * those settings skip the preview reload, and swaps the clear header's text color the same way. Mirrors stackless_theme_design_vars() in inc/design.php
 * for the properties in stackless_theme_design_live_properties(). Bundled fonts get their
 * @font-face rules only once picked, so nothing loads that is not in use.
 */
(function (api, data) {
	'use strict';

	if (!api || !data) {
		return;
	}

	var ids = ['stackless_font_body', 'stackless_font_body_custom', 'stackless_font_heading', 'stackless_font_heading_custom', 'stackless_heading_case'];
	Object.keys(data.colors).forEach(function (key) {
		ids.push('stackless_color_' + key);
	});

	function value(id) {
		var setting = api(id);
		var current = setting ? setting.get() : '';
		return current === null || current === undefined ? '' : String(current);
	}

	function hex(color) {
		return /^#([0-9a-f]{3}){1,2}$/i.test(color) ? color : '';
	}

	function rgb(color) {
		var h = color.replace('#', '');
		if (h.length === 3) {
			h = h[0] + h[0] + h[1] + h[1] + h[2] + h[2];
		}
		return [parseInt(h.slice(0, 2), 16), parseInt(h.slice(2, 4), 16), parseInt(h.slice(4, 6), 16)];
	}

	/* Mixes two hex colors; weight is the share of a (0-1). Same as stackless_theme_mix_hex(). */
	function mix(a, b, weight) {
		var x = rgb(a);
		var y = rgb(b);
		return '#' + x.map(function (channel, i) {
			return ('0' + Math.round(channel * weight + y[i] * (1 - weight)).toString(16)).slice(-2);
		}).join('');
	}

	/* The bundled font picked for 'body' or 'heading', or ''. Display faces never set body text. */
	function bundled(where) {
		var choice = value('stackless_font_' + where);
		if (choice.indexOf('font-') !== 0) {
			return '';
		}
		var slug = choice.slice(5);
		var font = data.fonts[slug];
		return font && (where === 'heading' || font.use === 'both') ? slug : '';
	}

	function stack(where) {
		var choice = value('stackless_font_' + where);
		if (!Object.prototype.hasOwnProperty.call(data.stacks, choice)) {
			return '';
		}
		if (choice === 'custom') {
			return value('stackless_font_' + where + '_custom').replace(/[^A-Za-z0-9 ,"'_-]/g, '').trim().slice(0, 300);
		}
		if (choice.indexOf('font-') === 0 && bundled(where) === '') {
			return '';
		}
		return data.stacks[choice] || '';
	}

	function vars() {
		var out = {};
		Object.keys(data.colors).forEach(function (key) {
			var property = data.colors[key][0];
			var color = hex(value('stackless_color_' + key));
			if (color !== '' && color.toLowerCase() !== data.colors[key][1]) {
				out[property] = color;
			}
		});
		var primary = out['--st-primary'];
		if (primary) {
			var channels = rgb(primary);
			out['--st-primary-hover'] = mix(primary, '#000000', 0.85);
			out['--st-primary-light'] = mix(primary, '#ffffff', 0.1);
			out['--st-primary-text'] = mix(primary, '#000000', 0.65);
			out['--st-ring'] = '0 0 0 3px rgba(' + channels.join(', ') + ', .25)';
		}
		if (out['--st-surface']) {
			out['--st-surface-muted'] = mix(out['--st-surface'], '#000000', 0.965);
		}
		var body = stack('body');
		if (body !== '') {
			out['--st-font'] = body;
		}
		var heading = stack('heading');
		if (heading !== '') {
			out['--st-font-heading'] = heading;
		}
		var slug = bundled('heading');
		var tuning = slug !== '' ? data.fonts[slug].heading || {} : {};
		if (tuning.weight !== undefined) {
			out['--st-heading-weight'] = String(parseInt(tuning.weight, 10));
		}
		if (tuning.tracking !== undefined) {
			out['--st-heading-tracking'] = String(tuning.tracking).replace(/[^0-9.em-]/g, '');
		}
		if (value('stackless_heading_case') === 'uppercase') {
			out['--st-heading-transform'] = 'uppercase';
		}
		return out;
	}

	function root(properties) {
		var keys = Object.keys(properties);
		return keys.length ? ':root{' + keys.map(function (property) {
			return property + ':' + properties[property] + ';';
		}).join('') + '}' : '';
	}

	function faces() {
		var css = '';
		[bundled('body'), bundled('heading')].filter(function (slug, i, list) {
			return slug !== '' && list.indexOf(slug) === i;
		}).forEach(function (slug) {
			var font = data.fonts[slug];
			font.faces.forEach(function (face) {
				css += '@font-face{font-family:"' + font.name + '";src:url("' + face[0] + '") format("woff2");font-weight:' + face[1] + ';font-style:' + face[2] + ';font-display:swap}';
			});
		});
		return css;
	}

	function element() {
		var style = document.getElementById('stackless-theme-live-design-inline-css');
		if (!style) {
			style = document.createElement('style');
			style.id = 'stackless-theme-live-design-inline-css';
			document.head.appendChild(style);
		}
		return style;
	}

	var queued = false;
	function render() {
		if (queued) {
			return;
		}
		queued = true;
		window.requestAnimationFrame(function () {
			queued = false;
			var palette = {};
			Object.keys(data.palette).forEach(function (property) {
				palette['--wp--preset--color--' + data.palette[property]] = 'var(' + property + ')';
			});
			element().textContent = (faces() + root(vars()) + root(palette)).replace(/[<>]/g, '');
		});
	}

	ids.forEach(function (id) {
		api(id, function (setting) {
			setting.bind(render);
		});
	});

	// Header text over the first section: swaps the clear header's text color on views that
	// Header over the first section lays the header over (window.stacklessThemeHeaderAuto).
	api('stackless_header_transparent_text', function (setting) {
		setting.bind(function (text) {
			if (!window.stacklessThemeHeaderAuto || !document.body.classList.contains('has-transparent-header')) {
				return;
			}
			document.body.classList.toggle('has-header-on-light', text === 'dark');
			document.body.classList.toggle('has-header-on-dark', text !== 'dark');
		});
	});
})(window.wp && window.wp.customize, window.stacklessThemePreview);
