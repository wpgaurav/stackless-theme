/**
 * Stackless block editor script: Share Notice.
 *
 * Block names, attributes and save output are a storage contract shared with
 * the Marketers Delight blocks of the same name. Do not rename, add or drop
 * attributes here.
 */
( function ( wp ) {
	var registerBlockType = wp.blocks.registerBlockType,
		el = wp.element.createElement,
		Fragment = wp.element.Fragment,
		__ = wp.i18n.__,
		PanelBody = wp.components.PanelBody,
		InspectorControls  = wp.blockEditor.InspectorControls,
		BlockControls = wp.blockEditor.BlockControls,
		useBlockProps = wp.blockEditor.useBlockProps,
		AlignmentToolbar   = wp.blockEditor.AlignmentToolbar,
		RichText           = wp.blockEditor.RichText,
		SelectControl = wp.components.SelectControl;
	registerBlockType( 'stackless/share-notice', {
		apiVersion: 3,
		title: __( 'Share Notice', 'stackless-theme' ),
		description: __( 'Display quoteable text with social media share buttons.', 'stackless-theme' ),
		icon: 'networking',
		category: 'stackless',
		deprecated: [
			{
				// Saved blocks validate (save is null), so without this the editor
				// never runs the migration and the old string `style` is dropped.
				isEligible: function ( attributes ) { return typeof attributes.style === 'string'; },
				attributes: {
					text: { type: 'string' },
					socialMedia: { type: 'string' },
					style: { type: 'string' },
					boxStyle: { type: 'string' },
					buttonText: { type: 'string' },
					buttonStyle: { type: 'string' },
					align: { type: 'string' },
					alignment: { type: 'string' },
					marginBottom: { type: 'string' },
					padding: { type: 'string' },
				},
				save: function () { return null; },
				migrate: function ( attributes ) {
					var newAttrs = Object.assign( {}, attributes, { blockStyle: attributes.style || '' } );
					delete newAttrs.style;
					return newAttrs;
				}
			}
		],
		attributes: {
			text: { type: 'string' },
			socialMedia: { type: 'string' },
			blockStyle: { type: 'string' },
			boxStyle: { type: 'string' },
			buttonText: { type: 'string' },
			buttonStyle: { type: 'string' },
			align: { type: 'string' },
			alignment: { type: 'string' },
			marginBottom: { type: 'string' },
			padding: { type: 'string' },
		},
		edit: function ( props ) {
			var attributes = props.attributes,
				text = attributes.text,
				socialMedia = attributes.socialMedia ? attributes.socialMedia : 'twitter',
				boxStyle = attributes.boxStyle ? attributes.boxStyle : 'outline',
				buttonStyle = attributes.buttonStyle ? attributes.buttonStyle : 'outline',
				alignment = attributes.alignment,
				marginBottom = attributes.marginBottom ? attributes.marginBottom : 'mb-mid',
				padding = attributes.padding ? attributes.padding : 'block-mid',
				buttonText;

			if ( socialMedia == 'twitter' || socialMedia == 'x' )
				buttonText = __( 'Tweet This', 'stackless-theme' );
			else if ( socialMedia == 'facebook' )
				buttonText = __( 'Post to Facebook', 'stackless-theme' );
			else if ( socialMedia == 'pinterest' )
				buttonText = __( 'Pin This', 'stackless-theme' );
			else if ( socialMedia == 'linkedin' )
				buttonText = __( 'Share on LinkedIn', 'stackless-theme' );
			else if ( socialMedia == 'reddit' )
				buttonText = __( 'Share on Reddit', 'stackless-theme' );
			else if ( socialMedia == 'whatsapp' )
				buttonText = __( 'Send via WhatsApp', 'stackless-theme' );
			else if ( socialMedia == 'telegram' )
				buttonText = __( 'Send via Telegram', 'stackless-theme' );
			else
				buttonText = __( 'Share This', 'stackless-theme' );

			var blockProps = useBlockProps({
				className: 'share-notice share-notice-' + socialMedia + ' share-notice-' + boxStyle + ' ' + marginBottom + ' ' + padding,
				style: { textAlign: alignment }
			});

			return el(Fragment, null,
				el( BlockControls, null,
					el( AlignmentToolbar, {
						value: alignment,
						onChange: function( val ) {
							props.setAttributes({ alignment: val });
						}
					})
				),
				el(
					InspectorControls, {
						key: 'inspector'
					},
					el(
						PanelBody, {
							title: __( 'Settings', 'stackless-theme' )
						},
						el( SelectControl, {
							label: __( 'Social Media', 'stackless-theme' ),
							options: [
								{ value: '', label: __( 'Select network to share to...', 'stackless-theme' ) },
								{ value: 'twitter', label: __( 'X', 'stackless-theme' ) },
								{ value: 'facebook', label: __( 'Facebook', 'stackless-theme' ) },
								{ value: 'linkedin', label: __( 'LinkedIn', 'stackless-theme' ) },
								{ value: 'pinterest', label: __( 'Pinterest', 'stackless-theme' ) },
								{ value: 'reddit', label: __( 'Reddit', 'stackless-theme' ) },
								{ value: 'whatsapp', label: __( 'WhatsApp', 'stackless-theme' ) },
								{ value: 'telegram', label: __( 'Telegram', 'stackless-theme' ) }
							],
							value: socialMedia,
							onChange: function( val ) {
								props.setAttributes({ socialMedia: val });
							},
						}),
						el( SelectControl, {
							label: __( 'Box Style', 'stackless-theme' ),
							options: [
								{ value: '', label: __( 'Select box style...', 'stackless-theme' ) },
								{ value: 'outline', label: __( 'Outline (default)', 'stackless-theme' ) },
								{ value: 'full', label: __( 'Full Color', 'stackless-theme' ) },
								{ value: 'minimal', label: __( 'Minimal', 'stackless-theme' ) },
								{ value: 'pill', label: __( 'Pill', 'stackless-theme' ) }
							],
							value: boxStyle,
							onChange: function( val ) {
								props.setAttributes({ boxStyle: val });
							}
						}),
						el( SelectControl, {
							label: __( 'Button Style', 'stackless-theme' ),
							options: [
								{ value: '', label: __( 'Select button style...', 'stackless-theme' ) },
								{ value: 'outline', label: __( 'Outline (default)', 'stackless-theme' ) },
								{ value: 'full', label: __( 'Full Color', 'stackless-theme' ) },
								{ value: 'pill', label: __( 'Pill', 'stackless-theme' ) }
							],
							value: buttonStyle,
							onChange: function( val ) {
								props.setAttributes({ buttonStyle: val });
							}
						}),
						el( SelectControl, {
							label: __( 'Margin Bottom', 'stackless-theme' ),
							options: [
								{ label: __( 'Select margin...', 'stackless-theme' ), value: '' },
								{ label: __( 'None', 'stackless-theme' ), value: 'mb-none' },
								{ label: __( 'Half', 'stackless-theme' ), value: 'mb-half' },
								{ label: __( 'Single', 'stackless-theme' ), value: 'mb-single' },
								{ label: __( 'Mid (default)', 'stackless-theme' ), value: 'mb-mid' },
								{ label: __( 'Double', 'stackless-theme' ), value: 'mb-double' },
								{ label: __( 'Triple', 'stackless-theme' ), value: 'mb-triple' },
							],
							value: marginBottom,
							onChange: function( val ) {
								props.setAttributes({ marginBottom: val });
							}
						}),
						el( SelectControl, {
							label: __( 'Padding', 'stackless-theme' ),
							options: [
								{ label: __( 'Select padding...', 'stackless-theme' ), value: '' },
								{ label: __( 'None', 'stackless-theme' ), value: 'mb-none' },
								{ label: __( 'Half', 'stackless-theme' ), value: 'block-half' },
								{ label: __( 'Single', 'stackless-theme' ), value: 'block-single' },
								{ label: __( 'Mid (default)', 'stackless-theme' ), value: 'block-mid' },
								{ label: __( 'Double', 'stackless-theme' ), value: 'block-double' },
								{ label: __( 'Triple', 'stackless-theme' ), value: 'block-triple' },
							],
							value: padding,
							onChange: function( val ) {
								props.setAttributes({ padding: val });
							}
						})
					)
				),
				el(
					'div', blockProps,
					el( RichText, {
						key: 'editable-text',
						tagName: 'p',
						className: 'share-notice-text small-text mb-single',
						value: text,
						placeholder: __( 'Enter share text here...', 'stackless-theme' ),
						onChange: function( val ) {
							props.setAttributes({ text: val });
						}
					}),
					StacklessBlocks.icon( 'i', 'md-icon-' + socialMedia, 'share-notice-icon' ),
					el( RichText, {
						key: 'editable-button',
						tagName: 'button',
						className: 'share-notice-button md-submit md-submit-' + buttonStyle,
						value: buttonText,
						onChange: function( val ) {
							props.setAttributes({ buttonText: val });
						}
					})
				)
			);
		},
		save: function () { return null; }
	});
})( wp );
