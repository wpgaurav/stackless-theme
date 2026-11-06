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

	function LayoutPanel() {
		var meta = useSelect(function (select) {
			return select('core/editor').getEditedPostAttribute('meta') || {};
		}, []);
		var editPost = useDispatch('core/editor').editPost;

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
					var next = {};
					next[key] = value;
					editPost({ meta: next });
				}
			});
		});

		return el(wp.editor.PluginDocumentSettingPanel, {
			name: 'stackless-theme-layout',
			title: config.title,
			className: 'stackless-theme-layout-panel'
		}, el('div', { style: { display: 'grid', gap: '12px' } }, toggles));
	}

	wp.plugins.registerPlugin('stackless-theme-layout', { render: LayoutPanel });
})(window.wp, window.stacklessThemeLayout);
