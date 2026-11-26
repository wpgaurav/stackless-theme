/**
 * Stackless block editor script: Callout.
 *
 * Block names, attributes and save output are a storage contract: content
 * saved with them has to keep parsing. Do not rename, add or drop attributes
 * here.
 */
( function ( wp ) {
	var registerBlockType  = wp.blocks.registerBlockType,
		el                 = wp.element.createElement,
		Fragment           = wp.element.Fragment,
		__                 = wp.i18n.__,
		PanelBody          = wp.components.PanelBody,
		PanelColorSettings = wp.blockEditor.PanelColorSettings,
		InspectorControls  = wp.blockEditor.InspectorControls,
		BlockControls      = wp.blockEditor.BlockControls,
		AlignmentToolbar   = wp.blockEditor.AlignmentToolbar,
		useBlockProps      = wp.blockEditor.useBlockProps,
		getColorClassName  = wp.blockEditor.getColorClassName,
		MediaUpload        = wp.blockEditor.MediaUpload,
		RichText           = wp.blockEditor.RichText,
		TextControl        = wp.components.TextControl,
		SelectControl      = wp.components.SelectControl,
		CheckboxControl    = wp.components.CheckboxControl,
		wpButton           = wp.components.Button,
		stbColors           = StacklessBlocks.colors,
		stbIcons            = StacklessBlocks.icons,
		stbPopups           = StacklessBlocks.popups;

	registerBlockType( 'stackless/callout', {
		apiVersion: 3,
		title: __( 'Callout', 'stackless-theme' ),
		description: __( 'Create a helpful callout with icon, bullet list, and text.', 'stackless-theme' ),
		icon: 'megaphone',
		category: 'stackless',
		attributes: {
			title: { type: 'string' },
			text: { type: 'string' },
			list: { type: 'string' },
			icon: { type: 'string' },
			iconImage: { type: 'string' },
			url: { type: 'string' },
			buttonText: { type: 'string' },
			bgColor: { type: 'string' },
			bgColorClass: { type: 'string' },
			borderColor: { type: 'string' },
			iconColor: { type: 'string' },
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
			preset: { type: 'string', default: '' },
			layoutStyle: { type: 'string', default: 'tinted' }
		},
		edit: function ( props ) {
			var a = props.attributes,
				preset       = a.preset || '',
				borderColor  = a.borderColor,
				bgColor      = a.bgColor,
				bgColorClass = a.bgColorClass,
				iconColor    = a.iconColor || '',
				textColor    = a.textColor,
				textColorClass = a.textColorClass,
				buttonColor  = a.buttonColor,
				buttonColorClass = ! bgColor ? a.buttonColorClass : '',
				buttonTextColor = a.buttonTextColor,
				buttonTextColorClass = a.buttonTextColorClass,
				marginBottom = a.marginBottom || 'mb-double',
				padding      = a.padding || 'block-mid',
				popups       = stbPopups || [];

			function stbGetColor( property, val ) {
				var className = getColorClassName( property, stbColors.hex[val] );
				return className || '';
			}

			// A preset is drawn by its callout-{preset} class from the theme's color tokens.
			var effectiveBorder = preset ? '' : borderColor;
			var effectiveBg = preset ? '' : ( ! bgColorClass ? bgColor : '' );

			var blockProps = useBlockProps({
				className: 'callout ' + marginBottom + ' ' + padding
					+ ( a.shadow ? ' has-shadow' : '' )
					+ ( ! preset && bgColorClass ? ' ' + bgColorClass : '' )
					+ ( textColorClass ? ' ' + textColorClass : '' )
					+ ( a.bgImage ? ' image-overlay' : '' )
					+ ( a.icon || a.iconImage ? ' has-icon' : '' )
					+ ( preset ? ' callout-' + preset : '' )
					+ ( a.layoutStyle && a.layoutStyle !== 'tinted' ? ' callout-layout-' + a.layoutStyle : '' )
					+ ' mt-double',
				style: {
					backgroundImage: a.bgImage ? 'url(' + a.bgImage + ')' : '',
					border: effectiveBorder ? '4px solid ' + effectiveBorder : '',
					backgroundColor: effectiveBg || '',
					color: ! textColorClass ? textColor : '',
					textAlign: a.alignment
				}
			});

			return el(Fragment, null,
				el( BlockControls, null,
					el( AlignmentToolbar, {
						value: a.alignment,
						onChange: function( val ) { props.setAttributes({ alignment: val }); }
					})
				),
				el( InspectorControls, null,
					el( PanelBody, { title: __( 'Settings', 'stackless-theme' ) },
						el( SelectControl, {
							label: __( 'Preset Style', 'stackless-theme' ),
							options: [
								{ label: __( 'Default (Custom Colors)', 'stackless-theme' ), value: '' },
								{ label: __( 'Tip (Green)', 'stackless-theme' ), value: 'tip' },
								{ label: __( 'Info (Blue)', 'stackless-theme' ), value: 'info' },
								{ label: __( 'Warning (Amber)', 'stackless-theme' ), value: 'warning' },
								{ label: __( 'Success (Emerald)', 'stackless-theme' ), value: 'success' },
								{ label: __( 'Error (Red)', 'stackless-theme' ), value: 'error' }
							],
							value: preset,
							onChange: function( val ) { props.setAttributes({ preset: val }); }
						}),
						el( SelectControl, {
							label: __( 'Layout Style', 'stackless-theme' ),
							options: [
								{ label: __( 'Tinted Surface (default)', 'stackless-theme' ), value: 'tinted' },
								{ label: __( 'Left Accent Bar', 'stackless-theme' ), value: 'accent-bar' },
								{ label: __( 'Minimal Inline', 'stackless-theme' ), value: 'minimal' }
							],
							value: a.layoutStyle || 'tinted',
							onChange: function( val ) { props.setAttributes({ layoutStyle: val }); }
						}),
						el( SelectControl, {
							label: __( 'Icon', 'stackless-theme' ),
							options: [ { label: __( 'Select an icon...', 'stackless-theme' ), value: '' } ].concat( stbIcons ),
							value: a.icon,
							onChange: function( val ) { props.setAttributes({ icon: val }); }
						}),
						! a.icon ?
							el( MediaUpload, {
								type: 'image',
								value: a.iconImage,
								onSelect: function( val ) { props.setAttributes({ iconImage: val.url }); },
								render: function( obj ) {
									return [
										el( wpButton, {
											className: 'components-icon-button image-block-btn is-button is-default mb-half is-large',
											onClick: obj.open
										},
											el( 'i', { className: 'dashicons dashicons-format-image', style: { marginRight: '4px' } } ),
											el( 'span', null, __( 'Select icon image', 'stackless-theme' ) )
										),
										a.iconImage ? el( wpButton, {
											className: 'components-icon-button image-block-btn is-button is-default is-large mt-half mb-half',
											onClick: function() { props.setAttributes({ iconImage: '' }); }
										},
											el( 'i', { className: 'dashicons dashicons-no-alt', style: { marginRight: '4px' } } ),
											el( 'span', null, __( 'Remove image', 'stackless-theme' ) )
										) : ''
									];
								}
							})
						: '',
						el( TextControl, { label: __( 'Button Text', 'stackless-theme' ), value: a.buttonText, onChange: function( val ) { props.setAttributes({ buttonText: val }); } }),
						el( TextControl, { label: __( 'Button URL', 'stackless-theme' ), value: a.url, onChange: function( val ) { props.setAttributes({ url: val }); } }),
						popups.length > 0 ?
							el( SelectControl, {
								label: __( 'Popup', 'stackless-theme' ),
								options: [ { label: __( 'Select a popup...', 'stackless-theme' ), value: '' } ].concat( popups ),
								value: a.popup,
								onChange: function( val ) { props.setAttributes({ popup: val }); }
							})
						: ''
					),
					! preset ?
						el( PanelColorSettings, {
							title: __( 'Colors', 'stackless-theme' ),
							colorSettings: [
								{ value: bgColor, label: __( 'Background Color', 'stackless-theme' ), onChange: function( val ) { props.setAttributes({ bgColor: val, bgColorClass: val ? stbGetColor( 'background-color', val ) : '' }); } },
								{ value: borderColor, label: __( 'Border Color', 'stackless-theme' ), onChange: function( val ) { props.setAttributes({ borderColor: val || 'rgba(0, 0, 0, 0.1)' }); } },
								{ value: a.iconColor, label: __( 'Icon Color', 'stackless-theme' ), onChange: function( val ) { props.setAttributes({ iconColor: val }); } },
								{ value: textColor, label: __( 'Text Color', 'stackless-theme' ), onChange: function( val ) { props.setAttributes({ textColor: val, textColorClass: val ? stbGetColor( 'color', val ) : '' }); } },
								{ value: buttonColor, label: __( 'Button Color', 'stackless-theme' ), onChange: function( val ) { props.setAttributes({ buttonColor: val, buttonColorClass: val ? stbGetColor( 'background-color', val ) : '' }); } },
								{ value: buttonTextColor, label: __( 'Button Text Color', 'stackless-theme' ), onChange: function( val ) { props.setAttributes({ buttonTextColor: val, buttonTextColorClass: val ? stbGetColor( 'color', val ) : '' }); } }
							]
						})
					: '',
					el( PanelBody, { title: __( 'Design', 'stackless-theme' ) },
						el( MediaUpload, {
							type: 'image',
							value: a.bgImage,
							onSelect: function( val ) { props.setAttributes({ bgImage: val.url }); },
							render: function( obj ) {
								return [
									el( wpButton, {
										className: 'components-icon-button image-block-btn is-button is-default mb-half is-large',
										onClick: obj.open
									},
										el( 'i', { className: 'dashicons dashicons-format-image', style: { marginRight: '4px' } } ),
										el( 'span', null, __( 'Select background image', 'stackless-theme' ) )
									),
									a.bgImage ? el( wpButton, {
										className: 'components-icon-button image-block-btn is-button is-default is-large mt-half mb-half',
										onClick: function() { props.setAttributes({ bgImage: '' }); }
									},
										el( 'i', { className: 'dashicons dashicons-no-alt', style: { marginRight: '4px' } } ),
										el( 'span', null, __( 'Remove image', 'stackless-theme' ) )
									) : ''
								];
							}
						}),
						el( SelectControl, {
							label: __( 'Margin Bottom', 'stackless-theme' ),
							options: [
								{ label: __( 'None', 'stackless-theme' ), value: 'mb-none' },
								{ label: __( 'Half', 'stackless-theme' ), value: 'mb-half' },
								{ label: __( 'Single', 'stackless-theme' ), value: 'mb-single' },
								{ label: __( 'Mid', 'stackless-theme' ), value: 'mb-mid' },
								{ label: __( 'Double (default)', 'stackless-theme' ), value: 'mb-double' },
								{ label: __( 'Triple', 'stackless-theme' ), value: 'mb-triple' }
							],
							value: marginBottom,
							onChange: function( val ) { props.setAttributes({ marginBottom: val }); }
						}),
						el( SelectControl, {
							label: __( 'Padding', 'stackless-theme' ),
							options: [
								{ label: __( 'None', 'stackless-theme' ), value: 'mb-none' },
								{ label: __( 'Half', 'stackless-theme' ), value: 'block-half' },
								{ label: __( 'Single', 'stackless-theme' ), value: 'block-single' },
								{ label: __( 'Mid (default)', 'stackless-theme' ), value: 'block-mid' },
								{ label: __( 'Double', 'stackless-theme' ), value: 'block-double' },
								{ label: __( 'Triple', 'stackless-theme' ), value: 'block-triple' }
							],
							value: padding,
							onChange: function( val ) { props.setAttributes({ padding: val }); }
						}),
						el( CheckboxControl, {
							label: __( 'Add shadow', 'stackless-theme' ),
							checked: a.shadow,
							onChange: function( val ) { props.setAttributes({ shadow: val }); }
						})
					)
				),
				el( 'aside', blockProps,
					( a.icon || a.iconImage ?
						el( 'div', {
							className: 'callout-icon mb-single' + ( a.icon ? ' icon' : '' ) + ( a.iconImage && ! a.icon ? ' image' : '' ),
							style: iconColor ? { backgroundColor: iconColor } : {}
						},
							a.icon ? StacklessBlocks.icon( 'i', a.icon ) : ( a.iconImage ? el( 'img', { src: a.iconImage } ) : '' )
						)
					: '' ),
					el( 'div', { className: 'callout-title mb-single' },
						el( RichText, {
							tagName: 'p',
							className: 'med-title',
							value: a.title,
							placeholder: __( 'Enter Title Here...', 'stackless-theme' ),
							onChange: function( val ) { props.setAttributes({ title: val }); }
						})
					),
					el( 'div', { className: 'callout-text mb-single' },
						el( RichText, {
							tagName: 'p',
							value: a.text,
							placeholder: __( 'Enter text here...', 'stackless-theme' ),
							onChange: function( val ) { props.setAttributes({ text: val }); }
						})
					),
					el( 'div', { className: 'callout-list-wrap' },
						el( RichText, {
							tagName: 'ul',
							className: 'callout-list mb-single',
							multiline: 'li',
							value: a.list,
							placeholder: __( 'Enter list item here...', 'stackless-theme' ),
							onChange: function( val ) { props.setAttributes({ list: val }); }
						})
					),
					a.buttonText ?
						el( 'div', { className: 'callout-action' },
							el( 'span', {
								className: 'callout-button button button-arrow ' + ( buttonColorClass || '' ) + ' ' + ( buttonTextColorClass || '' ),
								style: {
									backgroundColor: ! buttonColorClass ? buttonColor : '',
									color: ! buttonTextColorClass ? buttonTextColor : ''
								}
							}, a.buttonText )
						)
					: ''
				)
			);
		},
		save: function() { return null; }
	});
})( wp );
