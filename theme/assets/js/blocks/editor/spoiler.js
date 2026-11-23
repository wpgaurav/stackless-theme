/**
 * Stackless block editor script: Spoiler.
 *
 * Block names, attributes and save output are a storage contract shared with
 * the Marketers Delight blocks of the same name. Do not rename, add or drop
 * attributes here.
 */
(function (wp) {
	var registerBlockType = wp.blocks.registerBlockType,
		el = wp.element.createElement,
		Fragment = wp.element.Fragment,
		InnerBlocks = wp.blockEditor.InnerBlocks,
		InspectorControls = wp.blockEditor.InspectorControls,
		useBlockProps = wp.blockEditor.useBlockProps,
		PanelColorSettings = wp.blockEditor.PanelColorSettings,
		PanelBody = wp.components.PanelBody,
		SelectControl = wp.components.SelectControl,
		TextControl = wp.components.TextControl,
		__ = wp.i18n.__;

	registerBlockType('stackless/spoiler', {
		apiVersion: 3,
		title: __( 'Spoiler', 'stackless-theme' ),
		description: __( 'Click to reveal hidden content.', 'stackless-theme' ),
		icon: 'hidden',
		category: 'stackless',
		deprecated: [
			{
				// Saved blocks validate (save is null), so without this the editor
				// never runs the migration and the old string `style` is dropped.
				isEligible: function (attributes) { return typeof attributes.style === 'string'; },
				attributes: {
					label: { type: 'string', default: 'Click to reveal' },
					revealedLabel: { type: 'string', default: 'Hide' },
					style: { type: 'string', default: 'default' },
					bgColor: { type: 'string' },
					textColor: { type: 'string' },
					marginBottom: { type: 'string', default: 'mb-double' }
				},
				save: function () {
					return wp.element.createElement(wp.blockEditor.InnerBlocks.Content);
				},
				migrate: function (attributes) {
					var newAttrs = Object.assign({}, attributes, { blockStyle: attributes.style || 'default' });
					delete newAttrs.style;
					return newAttrs;
				}
			}
		],
		attributes: {
			label: { type: 'string', default: 'Click to reveal' },
			revealedLabel: { type: 'string', default: 'Hide' },
			blockStyle: { type: 'string', default: 'default' },
			bgColor: { type: 'string' },
			textColor: { type: 'string' },
			marginBottom: { type: 'string', default: 'mb-double' }
		},
		// "Transform to" from the core Details block: its summary becomes the
		// toggle label and its inner blocks move inside unchanged.
		transforms: {
			from: [ {
				type: 'block',
				blocks: [ 'core/details' ],
				transform: function ( attributes, innerBlocks ) {
					var label = attributes.summary ? String( attributes.summary ).replace( /<[^>]+>/g, '' ) : '';
					return wp.blocks.createBlock( 'stackless/spoiler', label ? { label: label } : {}, ( innerBlocks || [] ).map( function ( b ) { return wp.blocks.cloneBlock( b ); } ) );
				}
			} ]
		},
		edit: function (props) {
			var attributes = props.attributes;

			var blockProps = useBlockProps({
				className: 'md-spoiler md-spoiler-' + attributes.blockStyle + ' ' + attributes.marginBottom,
				style: {
					backgroundColor: attributes.bgColor || '',
					color: attributes.textColor || ''
				}
			});

			return el(Fragment, null,
				el(InspectorControls, { key: 'inspector' },
					el(PanelBody, { title: __( 'Settings', 'stackless-theme' ), initialOpen: true },
						el(TextControl, {
							label: __( 'Button Label', 'stackless-theme' ),
							value: attributes.label,
							onChange: function (val) { props.setAttributes({ label: val }); }
						}),
						el(TextControl, {
							label: __( 'Revealed Label', 'stackless-theme' ),
							help: __( 'Text shown when content is visible', 'stackless-theme' ),
							value: attributes.revealedLabel,
							onChange: function (val) { props.setAttributes({ revealedLabel: val }); }
						}),
						el(SelectControl, {
							label: __( 'Style', 'stackless-theme' ),
							options: [
								{ label: __( 'Default', 'stackless-theme' ), value: 'default' },
								{ label: __( 'Boxed', 'stackless-theme' ), value: 'boxed' },
								{ label: __( 'Minimal', 'stackless-theme' ), value: 'minimal' }
							],
							value: attributes.blockStyle,
							onChange: function (val) { props.setAttributes({ blockStyle: val }); }
						}),
						el(SelectControl, {
							label: __( 'Margin Bottom', 'stackless-theme' ),
							options: [
								{ label: __( 'None', 'stackless-theme' ), value: 'mb-none' },
								{ label: __( 'Half', 'stackless-theme' ), value: 'mb-half' },
								{ label: __( 'Single', 'stackless-theme' ), value: 'mb-single' },
								{ label: __( 'Double (default)', 'stackless-theme' ), value: 'mb-double' }
							],
							value: attributes.marginBottom,
							onChange: function (val) { props.setAttributes({ marginBottom: val }); }
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
								value: attributes.textColor,
								label: __( 'Text Color', 'stackless-theme' ),
								onChange: function (val) { props.setAttributes({ textColor: val }); }
							}
						]
					})
				),
				el('div', blockProps,
					el('div', { className: 'md-spoiler-toggle-preview' },
						StacklessBlocks.icon('span', 'md-icon-eye', 'md-spoiler-icon'),
						el('span', { className: 'md-spoiler-label' }, attributes.label)
					),
					el('div', { className: 'md-spoiler-content' },
						el(InnerBlocks, {
							templateLock: false
						})
					)
				)
			);
		},
		save: function () {
			return el(InnerBlocks.Content);
		}
	});
})(window.wp);
