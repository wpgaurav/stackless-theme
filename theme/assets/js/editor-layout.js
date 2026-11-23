/* Stackless theme: the "Layout" panel in the block editor's document sidebar. */
(function (wp, config) {
	'use strict';

	if (!wp || !wp.plugins || !wp.editor || !wp.editor.PluginDocumentSettingPanel || !config) {
		return;
	}

	var el = wp.element.createElement;
	var useSelect = wp.data.useSelect;
	var useDispatch = wp.data.useDispatch;
	var ToggleControl = wp.components.ToggleControl;
	var SelectControl = wp.components.SelectControl;
	var Notice = wp.components.Notice;
	var choices = config.choices || {};
	var owning = config.owningTemplates || [];

	function LayoutPanel() {
		var state = useSelect(function (select) {
			var editor = select('core/editor');
			return {
				meta: editor.getEditedPostAttribute('meta') || {},
				template: editor.getEditedPostAttribute('template') || ''
			};
		}, []);
		var meta = state.meta;
		var editPost = useDispatch('core/editor').editPost;

		function set(key, value) {
			var next = {};
			next[key] = value;
			editPost({ meta: next });
		}

		var toggles = Object.keys(config.options).map(function (key) {
			if (!(key in meta)) {
				return null;
			}
			return el(ToggleControl, {
				key: key,
				label: config.options[key],
				help: key === '_stackless_transparent_header' ? config.transparentHelp : undefined,
				checked: !!meta[key],
				__nextHasNoMarginBottom: true,
				onChange: function (value) {
					set(key, value);
				}
			});
		});

		// Post layout and header style: per-post overrides of the Customizer. A page template with
		// its own layout (Full width, Canvas, Page Blocks Builder) replaces them with a note.
		var choiceKeys = Object.keys(choices).filter(function (key) {
			return key in meta;
		});
		var selects = [];
		if (choiceKeys.length && owning.indexOf(state.template) !== -1) {
			selects.push(el(Notice, { key: 'owning', status: 'info', isDismissible: false }, config.owningHelp));
		} else {
			selects = choiceKeys.map(function (key) {
				return el(SelectControl, {
					key: key,
					label: choices[key].label,
					value: meta[key] || '',
					options: choices[key].options,
					__nextHasNoMarginBottom: true,
					__next40pxDefaultSize: true,
					onChange: function (value) {
						set(key, value);
					}
				});
			});
		}

		return el(wp.editor.PluginDocumentSettingPanel, {
			name: 'stackless-theme-layout',
			title: config.title,
			className: 'stackless-theme-layout-panel'
		}, el('div', { style: { display: 'grid', gap: '12px' } }, selects.concat(toggles)));
	}

	wp.plugins.registerPlugin('stackless-theme-layout', { render: LayoutPanel });
})(window.wp, window.stacklessThemeLayout);
