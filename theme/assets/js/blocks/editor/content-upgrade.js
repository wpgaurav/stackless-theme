/**
 * Stackless block editor script: Content Upgrade.
 *
 * Block names, attributes and save output are a storage contract shared with
 * the Marketers Delight blocks of the same name. Do not rename, add or drop
 * attributes here.
 */
( function ( wp ) {
	var registerBlockType  = wp.blocks.registerBlockType,
		el                 = wp.element.createElement,
		Fragment           = wp.element.Fragment,
		__                 = wp.i18n.__,
		RichText           = wp.blockEditor.RichText,
		PanelBody          = wp.components.PanelBody,
		PanelColorSettings = wp.blockEditor.PanelColorSettings,
		InspectorControls  = wp.blockEditor.InspectorControls,
		BlockControls      = wp.blockEditor.BlockControls,
		AlignmentToolbar   = wp.blockEditor.AlignmentToolbar,
		useBlockProps      = wp.blockEditor.useBlockProps,
		getColorClassName  = wp.blockEditor.getColorClassName,
		MediaUpload        = wp.blockEditor.MediaUpload,
		TextControl        = wp.components.TextControl,
		SelectControl      = wp.components.SelectControl,
		CheckboxControl    = wp.components.CheckboxControl,
		wpButton           = wp.components.Button,
		mdColors           = StacklessBlocks.colors,
		mdIcons            = StacklessBlocks.icons,
		mdPopups           = StacklessBlocks.popups;
	registerBlockType( 'stackless/content-upgrade', {
		apiVersion: 3,
		title: __( 'Content Upgrade', 'stackless-theme' ),
		description: __( 'Display an offer box in your content with a link or popup offer.', 'stackless-theme' ),
		icon: 'awards',
		category: 'stackless',
		attributes: {
			title: { type: 'string' },
			text: { type: 'string' },
			url: { type: 'string' },
			icon: { type: 'string' },
			buttonText: { type: 'string' },
			bgColor: { type: 'string' },
			bgColorClass: { type: 'string' },
			textColor: { type: 'string' },
			textColorClass: { type: 'string' },
			buttonColor: { type: 'string' },
			buttonColorClass: { type: 'string' },
			buttonTextColor: { type: 'string' },
			buttonTextColorClass: { type: 'string' },
			bgImage: { type: 'string' },
			align: { type: 'string' },
			alignment: { type: 'string' },
			popup: { type: 'string' },
			marginBottom: { type: 'string' },
			padding: { type: 'string' },
			shadow: { type: 'boolean' },
			boxLeftRight: { type: 'boolean' }
		},
		edit: function ( props ) {
			var attributes = props.attributes,
				title                = attributes.title,
				text                 = attributes.text,
				url                  = attributes.url,
				icon                 = attributes.icon,
				buttonText           = attributes.buttonText ? attributes.buttonText : __( 'Download now', 'stackless-theme' ),
				popup                = attributes.popup,
				bgColor              = attributes.bgColor,
				bgColorClass         = attributes.bgColorClass,
				textColor            = attributes.textColor,
				textColorClass       = attributes.textColorClass,
				buttonColor          = attributes.buttonColor,
				buttonColorClass     = attributes.buttonColorClass,
				buttonTextColor      = attributes.buttonTextColor,
				buttonTextColorClass = attributes.buttonTextColorClass,
				bgImage              = attributes.bgImage,
				alignment            = attributes.alignment,
				shadow               = attributes.shadow,
				boxLeftRight         = attributes.boxLeftRight,
				marginBottom         = attributes.marginBottom ? attributes.marginBottom : 'mb-mid',
				padding              = attributes.padding ? attributes.padding : 'block-single',
				popups               = mdPopups ? mdPopups : [];

			function mdGetColor( property, val ) {
				var className = getColorClassName( property, mdColors.hex[val] ),
					saved = className ? className : '';
				return className ? className : saved;
			}

			function mdShowColor( property, val ) {
				return ' ' + mdGetColor( property, val );
			}

			var blockProps = useBlockProps({
				className: 'md-content-upgrade ' + marginBottom + ' ' + padding + ( boxLeftRight ? ' box-lr' : '' ) + ( shadow ? ' has-shadow' : '' ) + ( bgColorClass ? ' ' + bgColorClass : '' ) + ( textColorClass ? ' ' + textColorClass : '' ) + ( bgImage ? ' image-overlay' : '' ),
				style: {
					backgroundImage: ( bgImage ? 'url(' + bgImage + ')' : '' ),
					backgroundColor: ! bgColorClass ? ( bgColor || '#1e293b' ) : '',
					color: ! textColorClass ? ( textColor || '#ffffff' ) : '',
					textAlign: alignment
				}
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
							label: __( 'Icon', 'stackless-theme' ),
							options: [ { label: __( 'Select an icon...', 'stackless-theme' ), value: '' } ].concat( mdIcons ),
							value: icon,
							onChange: function( val ) {
								props.setAttributes({ icon: val });
							},
						}),
						el( TextControl, {
							type: 'text',
							label: __( 'Button Text', 'stackless-theme' ),
							value: buttonText,
							onChange: function( val ) {
								props.setAttributes({ buttonText: val });
							}
						}),
						el( TextControl, {
							type: 'text',
							label: __( 'Button URL', 'stackless-theme' ),
							value: url,
							onChange: function( val ) {
								props.setAttributes({ url: val });
							}
						}),
						popups.length > 0 ?
							el( SelectControl, {
								label: __( 'Popup', 'stackless-theme' ),
								options: [ { label: __( 'Select a popup...', 'stackless-theme' ), value: '' } ].concat( popups ),
								value: popup,
								onChange: function( val ) {
									props.setAttributes({ popup: val });
								},
							})
						: ''
					),
					el( PanelColorSettings, {
						title: __( 'Colors', 'stackless-theme' ),
						colorSettings: [
							{
								value: bgColor,
								label: __( 'Background Color', 'stackless-theme' ),
								onChange: function( val ) {
									props.setAttributes({
										bgColor: val,
										bgColorClass: val ? mdGetColor( 'background-color', val ) : ''
									});
								}
							},
							{
								value: textColor,
								label: __( 'Text Color', 'stackless-theme' ),
								onChange: function( val ) {
									props.setAttributes({
										textColor: val,
										textColorClass: val ? mdGetColor( 'color', val ) : ''
									});
								}
							},
							{
								value: buttonColor,
								label: __( 'Button Color', 'stackless-theme' ),
								onChange: function( val ) {
									props.setAttributes({
										buttonColor: val,
										buttonColorClass: val ? mdGetColor( 'background-color', val ) : ''
									});
								}
							},
							{
								value: buttonTextColor,
								label: __( 'Button Text Color', 'stackless-theme' ),
								onChange: function( val ) {
									props.setAttributes({
										buttonTextColor: val,
										buttonTextColorClass: val ? mdGetColor( 'color', val ) : ''
									});
								}
							},
						]
					}),
					el(
						PanelBody, {
							title: __( 'Design', 'stackless-theme' ),
						},
						el( MediaUpload, {
							type: 'image',
							value: bgImage,
							onSelect: function( val ) {
								props.setAttributes({
									bgImage: val.url
								});
							},
							render: function( obj ) {
								return [
									el(
										wpButton, {
											className: 'components-icon-button image-block-btn is-button is-default mb-half is-large',
											onClick: obj.open
										},
										el( 'i', { className: 'dashicons dashicons-format-image', style: { marginRight: '4px' } } ),
										el( 'span', null, __( 'Select background image', 'stackless-theme' ) )
									),
									( bgImage ?
										el(
											wpButton, {
												className: 'components-icon-button image-block-btn is-button is-default is-large mt-half mb-half',
												onClick: function( val ) {
													props.setAttributes({ bgImage: '' });
												}
											},
											el( 'i', { className: 'dashicons dashicons-no-alt', style: { marginRight: '4px' } } ),
											el( 'span', null, __( 'Remove image', 'stackless-theme' ) )
										)
									: '' )
								];
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
								{ label: __( 'Single (default)', 'stackless-theme' ), value: 'block-single' },
								{ label: __( 'Mid', 'stackless-theme' ), value: 'block-mid' },
								{ label: __( 'Double', 'stackless-theme' ), value: 'block-double' },
								{ label: __( 'Triple', 'stackless-theme' ), value: 'block-triple' },
							],
							value: padding,
							onChange: function( val ) {
								props.setAttributes({ padding: val });
							}
						}),
						el( CheckboxControl, {
							label: __( 'Align text left/right', 'stackless-theme' ),
							checked: boxLeftRight,
							onChange: function( val ) {
								props.setAttributes({ boxLeftRight: val });
							}
						}),
						el( CheckboxControl, {
							label: __( 'Add shadow', 'stackless-theme' ),
							checked: shadow,
							onChange: function( val ) {
								props.setAttributes({ shadow: val });
							}
						})
					)
				),
				el(
					'div', blockProps,
					( icon ?
						el(
							'div', {
								className: 'md-content-upgrade-icon large-title mr-single'
							},
							StacklessBlocks.icon( 'i', icon )
						)
					: '' ),
					el(
						'div', {
							className: 'md-content-upgrade-text mb-single'
						},
						el( RichText, {
							key: 'editable',
							tagName: 'p',
							className: 'small-title mb-small',
							value: title,
							placeholder: __( 'Enter Title Here', 'stackless-theme' ),
							onChange: function( val ) {
								props.setAttributes({ title: val });
							}
						}),
						el( RichText, {
							key: 'editable',
							tagName: 'p',
							value: text,
							placeholder: __( 'Enter description text (optional)', 'stackless-theme' ),
							onChange: function( val ) {
								props.setAttributes({ text: val });
							}
						})
					),
					el(
						'div', {
							className: 'md-content-upgrade-action'
						},
						el(
							'button', {
								className: 'md-button md-button-arrow ' + ( buttonColorClass || '' ) + ' ' + ( buttonTextColorClass || '' ),
								disabled: true,
								style: {
									backgroundColor: ! buttonColorClass ? ( buttonColor || '#2563eb' ) : '',
									color: ! buttonTextColorClass ? ( buttonTextColor || '#ffffff' ) : ''
								}
							}, buttonText
						)
					)
				)
			);
		},
		save: function () { return null; }
	});
})( wp );
