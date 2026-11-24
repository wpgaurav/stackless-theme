/**
 * Stackless block editor script: Key Takeaway.
 *
 * Block names, attributes and save output are a storage contract: content
 * saved with them has to keep parsing. Do not rename, add or drop attributes
 * here.
 */
(function (wp) {
	var registerBlockType = wp.blocks.registerBlockType,
		el = wp.element.createElement,
		Fragment = wp.element.Fragment,
		RichText = wp.blockEditor.RichText,
		InspectorControls = wp.blockEditor.InspectorControls,
		useBlockProps = wp.blockEditor.useBlockProps,
		PanelColorSettings = wp.blockEditor.PanelColorSettings,
		PanelBody = wp.components.PanelBody,
		SelectControl = wp.components.SelectControl,
		TextControl = wp.components.TextControl,
		__ = wp.i18n.__;

	var iconMap = {
		'bolt': 'stb-icon-bolt',
		'fire': 'stb-icon-fire',
		'star': 'stb-icon-star',
		'info': 'stb-icon-info',
		'warning': 'stb-icon-warning',
		'ok': 'stb-icon-ok',
		'gift': 'stb-icon-gift',
		'trophy': 'stb-icon-trophy',
		'rocket': 'stb-icon-rocket',
		'heart': 'stb-icon-heart',
		'bell': 'stb-icon-bell',
		'gem': 'stb-icon-gem'
	};

	registerBlockType('stackless/key-takeaway', {
		apiVersion: 3,
		title: __( 'Key Takeaway', 'stackless-theme' ),
		description: __( 'Highlight important points or TL;DR summaries.', 'stackless-theme' ),
		icon: 'lightbulb',
		category: 'stackless',
		deprecated: [
			{
				// Saved blocks validate (save is null), so without this the editor
				// never runs the migration and the old string `style` is dropped.
				isEligible: function (attributes) { return typeof attributes.style === 'string'; },
				attributes: {
					label: { type: 'string', default: 'Key Takeaway' },
					content: { type: 'string' },
					style: { type: 'string', default: 'highlight' },
					icon: { type: 'string', default: 'bolt' },
					bgColor: { type: 'string' },
					borderColor: { type: 'string' },
					textColor: { type: 'string' },
					iconColor: { type: 'string' },
					marginBottom: { type: 'string', default: 'mb-double' },
					shadow: { type: 'boolean', default: false }
				},
				save: function () { return null; },
				migrate: function (attributes) {
					var newAttrs = Object.assign({}, attributes, { blockStyle: attributes.style || 'highlight' });
					delete newAttrs.style;
					return newAttrs;
				}
			}
		],
		attributes: {
			label: { type: 'string', default: 'Key Takeaway' },
			content: { type: 'string' },
			blockStyle: { type: 'string', default: 'highlight' },
			icon: { type: 'string', default: 'bolt' },
			bgColor: { type: 'string' },
			borderColor: { type: 'string' },
			textColor: { type: 'string' },
			iconColor: { type: 'string' },
			marginBottom: { type: 'string', default: 'mb-double' },
			shadow: { type: 'boolean', default: false }
		},
		edit: function (props) {
			var attributes = props.attributes;

			var iconClass = iconMap[attributes.icon] || '';

			// Only the author's own overrides go inline. This used to hardcode a
			// flat pastel per style, which beat the editor stylesheet on
			// specificity — so the preview showed a look the front end never
			// rendered, and never followed dark mode or the theme palette.
			var inlineStyle = {};
			if (attributes.bgColor) inlineStyle.background = attributes.bgColor;
			if (attributes.borderColor) inlineStyle['--kt-accent'] = attributes.borderColor;
			if (attributes.textColor) inlineStyle.color = attributes.textColor;

			var blockProps = useBlockProps({
				className: 'stb-key-takeaway stb-key-takeaway-' + attributes.blockStyle + ' ' + attributes.marginBottom,
				style: inlineStyle
			});

			return el(Fragment, null,
				el(InspectorControls, { key: 'inspector' },
					el(PanelBody, { title: __( 'Settings', 'stackless-theme' ), initialOpen: true },
						el(TextControl, {
							label: __( 'Label', 'stackless-theme' ),
							value: attributes.label,
							onChange: function (val) { props.setAttributes({ label: val }); }
						}),
						el(SelectControl, {
							label: __( 'Style', 'stackless-theme' ),
							options: [
								{ label: __( 'Highlight (Yellow)', 'stackless-theme' ), value: 'highlight' },
								{ label: __( 'Info (Blue)', 'stackless-theme' ), value: 'info' },
								{ label: __( 'Success (Green)', 'stackless-theme' ), value: 'success' },
								{ label: __( 'Minimal', 'stackless-theme' ), value: 'minimal' }
							],
							value: attributes.blockStyle,
							onChange: function (val) { props.setAttributes({ blockStyle: val }); }
						}),
						el(SelectControl, {
							label: __( 'Icon', 'stackless-theme' ),
							options: [
								{ label: __( 'Bolt', 'stackless-theme' ), value: 'bolt' },
								{ label: __( 'Fire', 'stackless-theme' ), value: 'fire' },
								{ label: __( 'Star', 'stackless-theme' ), value: 'star' },
								{ label: __( 'Info', 'stackless-theme' ), value: 'info' },
								{ label: __( 'Warning', 'stackless-theme' ), value: 'warning' },
								{ label: __( 'Checkmark', 'stackless-theme' ), value: 'ok' },
								{ label: __( 'Gift', 'stackless-theme' ), value: 'gift' },
								{ label: __( 'Trophy', 'stackless-theme' ), value: 'trophy' },
								{ label: __( 'Rocket', 'stackless-theme' ), value: 'rocket' },
								{ label: __( 'Heart', 'stackless-theme' ), value: 'heart' },
								{ label: __( 'Bell', 'stackless-theme' ), value: 'bell' },
								{ label: __( 'Gem', 'stackless-theme' ), value: 'gem' },
								{ label: __( 'None', 'stackless-theme' ), value: 'none' }
							],
							value: attributes.icon,
							onChange: function (val) { props.setAttributes({ icon: val }); }
						})
					),
					el(PanelColorSettings, {
						title: __( 'Colors', 'stackless-theme' ),
						initialOpen: false,
						colorSettings: [
							{
								value: attributes.bgColor,
								label: __( 'Background Color', 'stackless-theme' ),
								onChange: function (val) { props.setAttributes({ bgColor: val }); }
							},
							{
								value: attributes.borderColor,
								label: __( 'Accent Color', 'stackless-theme' ),
								onChange: function (val) { props.setAttributes({ borderColor: val }); }
							},
							{
								value: attributes.iconColor,
								label: __( 'Icon Color', 'stackless-theme' ),
								onChange: function (val) { props.setAttributes({ iconColor: val }); }
							},
							{
								value: attributes.textColor,
								label: __( 'Text Color', 'stackless-theme' ),
								onChange: function (val) { props.setAttributes({ textColor: val }); }
							}
						]
					}),
					el(PanelBody, { title: __( 'Design', 'stackless-theme' ), initialOpen: false },
						el(SelectControl, {
							label: __( 'Margin Bottom', 'stackless-theme' ),
							options: [
								{ label: __( 'None', 'stackless-theme' ), value: 'mb-none' },
								{ label: __( 'Half', 'stackless-theme' ), value: 'mb-half' },
								{ label: __( 'Single', 'stackless-theme' ), value: 'mb-single' },
								{ label: __( 'Double (default)', 'stackless-theme' ), value: 'mb-double' },
								{ label: __( 'Triple', 'stackless-theme' ), value: 'mb-triple' }
							],
							value: attributes.marginBottom,
							onChange: function (val) { props.setAttributes({ marginBottom: val }); }
						})
					)
				),
				el('div', blockProps,
					el('div', { className: 'stb-key-takeaway-header' },
						attributes.icon !== 'none' && iconClass && StacklessBlocks.icon('span', iconClass, 'stb-key-takeaway-icon', {
							style: attributes.iconColor ? { color: attributes.iconColor } : {}
						}),
						el('span', { className: 'stb-key-takeaway-label' }, attributes.label)
					),
					el(RichText, {
						tagName: 'div',
						className: 'stb-key-takeaway-content',
						value: attributes.content,
						placeholder: __( 'Write your key takeaway or summary here...', 'stackless-theme' ),
						onChange: function (val) { props.setAttributes({ content: val }); }
					})
				)
			);
		},
		save: function () {
			return null;
		}
	});
})(window.wp);
