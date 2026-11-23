/**
 * Stackless block editor script: Arrow.
 *
 * Block names, attributes and save output are a storage contract shared with
 * the Marketers Delight blocks of the same name. Do not rename, add or drop
 * attributes here.
 */
( function ( wp ) {
	var registerBlockType = wp.blocks.registerBlockType,
		el                = wp.element.createElement,
		InspectorControls = wp.blockEditor.InspectorControls,
		useBlockProps     = wp.blockEditor.useBlockProps,
		PanelBody         = wp.components.PanelBody,
		SelectControl     = wp.components.SelectControl,
		__                = wp.i18n.__,
		mdIcons           = StacklessBlocks.icons;

	registerBlockType( 'stackless/arrow', {
		apiVersion: 3,
		title: __( 'Arrow', 'stackless-theme' ),
		description: __( 'Show an icon to encourage scrolling or draw attention.', 'stackless-theme' ),
		category: 'stackless',
		icon: 'arrow-down-alt2',
		attributes: {
			icon: {
				type: 'string',
				default: 'md-icon-angle-down'
			}
		},
		edit: function ( props ) {
			var icon = props.attributes.icon;
			var blockProps = useBlockProps({ className: 'arrow-down text-center' });
			return el(
				wp.element.Fragment, null,
				el( InspectorControls, null,
					el( PanelBody, { title: __( 'Icon Settings', 'stackless-theme' ) },
						el( SelectControl, {
							label: __( 'Icon', 'stackless-theme' ),
							options: mdIcons,
							value: icon,
							onChange: function( val ) { props.setAttributes({ icon: val }); }
						})
					)
				),
				el( 'p', blockProps,
					StacklessBlocks.icon( 'span', icon, 'large-title' )
				)
			);
		},
		save: function () {
			return null;
		}
	});
})( window.wp );
