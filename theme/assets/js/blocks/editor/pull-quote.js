/**
 * Stackless block editor script: Pull Quote.
 *
 * Block names, attributes and save output are a storage contract shared with
 * the Marketers Delight blocks of the same name. Do not rename, add or drop
 * attributes here.
 */
(function (wp) {
	var registerBlockType = wp.blocks.registerBlockType,
		el = wp.element.createElement,
		Fragment = wp.element.Fragment,
		RichText = wp.blockEditor.RichText,
		InspectorControls = wp.blockEditor.InspectorControls,
		BlockControls = wp.blockEditor.BlockControls,
		AlignmentToolbar = wp.blockEditor.AlignmentToolbar,
		useBlockProps = wp.blockEditor.useBlockProps,
		PanelColorSettings = wp.blockEditor.PanelColorSettings,
		PanelBody = wp.components.PanelBody,
		SelectControl = wp.components.SelectControl,
		RangeControl = wp.components.RangeControl,
		CheckboxControl = wp.components.CheckboxControl,
		__ = wp.i18n.__;

	registerBlockType('stackless/pull-quote', {
		apiVersion: 3,
		title: __( 'Pull Quote', 'stackless-theme' ),
		description: __( 'Highlight a key quote or statement from your content.', 'stackless-theme' ),
		icon: 'format-quote',
		category: 'stackless',
		deprecated: [
			{
				// Saved blocks validate (save is null), so without this the editor
				// never runs the migration and the old string `style` is dropped.
				isEligible: function (attributes) { return typeof attributes.style === 'string'; },
				attributes: {
					quote: { type: 'string' },
					citation: { type: 'string' },
					style: { type: 'string', default: 'border' },
					alignment: { type: 'string', default: 'left' },
					blockWidth: { type: 'string', default: '' },
					quoteColor: { type: 'string' },
					citationColor: { type: 'string' },
					borderColor: { type: 'string' },
					bgColor: { type: 'string' },
					fontSize: { type: 'number', default: 135 },
					marginBottom: { type: 'string', default: 'mb-double' },
					shadow: { type: 'boolean', default: false }
				},
				save: function () { return null; },
				migrate: function (attributes) {
					var newAttrs = Object.assign({}, attributes, { quoteStyle: attributes.style || 'border' });
					delete newAttrs.style;
					return newAttrs;
				}
			}
		],
		attributes: {
			quote: { type: 'string' },
			citation: { type: 'string' },
			quoteStyle: { type: 'string', default: 'border' },
			alignment: { type: 'string', default: 'left' },
			blockWidth: { type: 'string', default: '' },
			quoteColor: { type: 'string' },
			citationColor: { type: 'string' },
			borderColor: { type: 'string' },
			bgColor: { type: 'string' },
			fontSize: { type: 'number', default: 135 },
			marginBottom: { type: 'string', default: 'mb-double' },
			shadow: { type: 'boolean', default: false }
		},
		// "Transform to" from the core Pullquote.
		transforms: {
			from: [ {
				type: 'block',
				blocks: [ 'core/pullquote' ],
				transform: function ( attributes ) {
					return wp.blocks.createBlock( 'stackless/pull-quote', { quote: String( attributes.value || '' ), citation: attributes.citation ? String( attributes.citation ) : '' } );
				}
			} ]
		},
		edit: function (props) {
			var attributes = props.attributes;

			var boxedStyles = attributes.quoteStyle === 'boxed' ? {
				background: attributes.bgColor || '#f9f9f9',
				border: '1px solid ' + (attributes.borderColor || '#ddd'),
				borderRadius: '8px',
				padding: '1.5rem 2rem'
			} : {};

			var borderStyles = attributes.quoteStyle === 'border' ? {
				borderLeft: '4px solid ' + (attributes.borderColor || '#c0392b'),
				paddingLeft: '1.5rem'
			} : {};

			var typographicStyles = attributes.quoteStyle === 'typographic' ? {
				textAlign: 'center',
				maxWidth: '32ch',
				marginLeft: 'auto',
				marginRight: 'auto'
			} : {};

			var sidebarStyles = attributes.quoteStyle === 'sidebar' ? {
				borderLeft: '2px solid ' + (attributes.borderColor || 'var(--color-primary)'),
				paddingLeft: '2rem',
				maxWidth: '45%',
				float: 'right',
				marginLeft: '2.5rem'
			} : {};

			var bezelStyles = attributes.quoteStyle === 'bezel' ? {
				background: 'rgba(0,0,0,0.03)',
				borderRadius: '20px',
				padding: '6px'
			} : {};

			var darkStyles = attributes.quoteStyle === 'dark' ? {
				background: attributes.bgColor || '#1a1a2e',
				borderRadius: '16px',
				padding: '3.5rem',
				textAlign: 'center',
				color: '#f0f0f0'
			} : {};

			var blockWidthClass = attributes.blockWidth ? ' ' + attributes.blockWidth : '';

			var blockProps = useBlockProps({
				className: 'md-pull-quote md-pull-quote-' + attributes.quoteStyle + ' ' + attributes.marginBottom + (attributes.shadow ? ' has-shadow' : '') + blockWidthClass,
				style: Object.assign({
					textAlign: attributes.alignment
				}, boxedStyles, borderStyles, typographicStyles, sidebarStyles, bezelStyles, darkStyles)
			});

			return el(Fragment, null,
				el(BlockControls, { key: 'controls' },
					el(AlignmentToolbar, {
						value: attributes.alignment,
						onChange: function (val) { props.setAttributes({ alignment: val }); }
					})
				),
				el(InspectorControls, { key: 'inspector' },
					el(PanelBody, { title: __( 'Settings', 'stackless-theme' ), initialOpen: true },
						el(SelectControl, {
							label: __( 'Style', 'stackless-theme' ),
							options: [
								{ label: __( 'Border Left', 'stackless-theme' ), value: 'border' },
								{ label: __( 'Large Quote Marks', 'stackless-theme' ), value: 'quote-marks' },
								{ label: __( 'Boxed', 'stackless-theme' ), value: 'boxed' },
								{ label: __( 'Minimal', 'stackless-theme' ), value: 'minimal' },
								{ label: __( 'Typographic Center', 'stackless-theme' ), value: 'typographic' },
								{ label: __( 'Floating Sidebar', 'stackless-theme' ), value: 'sidebar' },
								{ label: __( 'Double Bezel', 'stackless-theme' ), value: 'bezel' },
								{ label: __( 'Dark Highlight', 'stackless-theme' ), value: 'dark' }
							],
							value: attributes.quoteStyle,
							onChange: function (val) { props.setAttributes({ quoteStyle: val }); }
						}),
						el(RangeControl, {
							label: __( 'Font Size (%)', 'stackless-theme' ),
							value: attributes.fontSize,
							onChange: function (val) { props.setAttributes({ fontSize: val }); },
							min: 100,
							max: 200,
							step: 5
						})
					),
					el(PanelColorSettings, {
						title: __( 'Colors', 'stackless-theme' ),
						initialOpen: false,
						colorSettings: [
							{
								value: attributes.quoteColor,
								label: __( 'Quote Text Color', 'stackless-theme' ),
								onChange: function (val) { props.setAttributes({ quoteColor: val }); }
							},
							{
								value: attributes.citationColor,
								label: __( 'Citation Color', 'stackless-theme' ),
								onChange: function (val) { props.setAttributes({ citationColor: val }); }
							},
							{
								value: attributes.borderColor,
								label: __( 'Border/Accent Color', 'stackless-theme' ),
								onChange: function (val) { props.setAttributes({ borderColor: val }); }
							},
							{
								value: attributes.bgColor,
								label: __( 'Background Color (Boxed)', 'stackless-theme' ),
								onChange: function (val) { props.setAttributes({ bgColor: val }); }
							}
						]
					}),
					el(PanelBody, { title: __( 'Layout & Design', 'stackless-theme' ), initialOpen: false },
						el(SelectControl, {
							label: __( 'Block Width', 'stackless-theme' ),
							help: __( 'Control how the block extends beyond the content area.', 'stackless-theme' ),
							options: [
								{ label: __( 'Default (content width)', 'stackless-theme' ), value: '' },
								{ label: __( 'Wide', 'stackless-theme' ), value: 'alignwide' },
								{ label: __( 'Full Width', 'stackless-theme' ), value: 'alignfull' },
								{ label: __( 'Bleed Full', 'stackless-theme' ), value: 'bleed-full' },
								{ label: __( 'Float Left', 'stackless-theme' ), value: 'float-left' },
								{ label: __( 'Float Right', 'stackless-theme' ), value: 'float-right' }
							],
							value: attributes.blockWidth,
							onChange: function (val) { props.setAttributes({ blockWidth: val }); }
						}),
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
						}),
						el(CheckboxControl, {
							label: __( 'Add shadow (Boxed style)', 'stackless-theme' ),
							checked: attributes.shadow,
							onChange: function (val) { props.setAttributes({ shadow: val }); }
						})
					)
				),
				el('figure', blockProps,
					attributes.quoteStyle === 'bezel' && el('div', { className: 'md-pull-quote-bezel-inner', style: { background: '#fff', borderRadius: '15px', padding: '2.5rem', boxShadow: 'inset 0 1px 0 rgba(255,255,255,0.6)' } },
						el('span', { className: 'md-pull-quote-bezel-mark', style: { fontSize: '3.5rem', lineHeight: 1, opacity: 0.15, display: 'block', marginBottom: '0.5rem' } }, '\u201C'),
						el(RichText, {
							tagName: 'blockquote',
							className: 'md-pull-quote-text',
							style: { fontSize: (attributes.fontSize / 100) + 'em', color: attributes.quoteColor || '' },
							value: attributes.quote,
							placeholder: __( 'Write a compelling quote...', 'stackless-theme' ),
							onChange: function (val) { props.setAttributes({ quote: val }); }
						}),
						el(RichText, {
							tagName: 'figcaption',
							className: 'md-pull-quote-citation',
							style: { color: attributes.citationColor || '' },
							value: attributes.citation,
							placeholder: __( 'Citation (optional)', 'stackless-theme' ),
							onChange: function (val) { props.setAttributes({ citation: val }); }
						})
					),
					attributes.quoteStyle !== 'bezel' && el(Fragment, null,
						(attributes.quoteStyle === 'quote-marks' || attributes.quoteStyle === 'dark') && el('span', {
							className: 'md-pull-quote-mark',
							style: { color: attributes.quoteStyle === 'dark' ? 'rgba(255,255,255,0.08)' : (attributes.borderColor || '#c0392b') }
						}, '\u201C'),
						el(RichText, {
							tagName: 'blockquote',
							className: 'md-pull-quote-text',
							style: {
								fontSize: (attributes.fontSize / 100) + 'em',
								color: attributes.quoteStyle === 'dark' ? '#f0f0f0' : (attributes.quoteColor || '')
							},
							value: attributes.quote,
							placeholder: __( 'Write a compelling quote...', 'stackless-theme' ),
							onChange: function (val) { props.setAttributes({ quote: val }); }
						}),
						el(RichText, {
							tagName: 'figcaption',
							className: 'md-pull-quote-citation',
							style: { color: attributes.quoteStyle === 'dark' ? 'rgba(255,255,255,0.6)' : (attributes.citationColor || '') },
							value: attributes.citation,
							placeholder: __( 'Citation (optional)', 'stackless-theme' ),
							onChange: function (val) { props.setAttributes({ citation: val }); }
						})
					)
				)
			);
		},
		save: function () {
			return null;
		}
	});
})(window.wp);
