/**
 * Stackless block editor script: Side Promotion and Promotion Item.
 *
 * Block names, attributes and save output are a storage contract shared with
 * the Marketers Delight blocks of the same name. Do not rename, add or drop
 * attributes here.
 */
( function ( wp ) {
	var registerBlockType = wp.blocks.registerBlockType,
		el = wp.element.createElement,
		Fragment = wp.element.Fragment,
		InnerBlocks = wp.blockEditor.InnerBlocks,
		InspectorControls = wp.blockEditor.InspectorControls,
		useBlockProps = wp.blockEditor.useBlockProps,
		PanelBody = wp.components.PanelBody,
		SelectControl = wp.components.SelectControl,
		RangeControl = wp.components.RangeControl,
		TextControl = wp.components.TextControl,
		CheckboxControl = wp.components.CheckboxControl,
		MediaUpload = wp.blockEditor.MediaUpload,
		MediaUploadCheck = wp.blockEditor.MediaUploadCheck,
		Button = wp.components.Button,
		__ = wp.i18n.__;

	registerBlockType( 'stackless/side-promotion', {
		apiVersion: 3,
		title: __( 'Side Promotion', 'stackless-theme' ),
		description: __( 'Display promotional panels in the sidebar or content margins.', 'stackless-theme' ),
		icon: 'megaphone',
		category: 'stackless',
		attributes: {
			position: { type: 'string', default: 'right' },
			width: { type: 'number', default: 200 }
		},
		edit: function ( props ) {
			var attributes = props.attributes;

			var blockProps = useBlockProps({
				className: 'md-side-promotion-editor md-side-promotion-' + attributes.position,
				style: { maxWidth: attributes.width + 'px' }
			});

			return el(Fragment, null,
				el( InspectorControls, { key: 'inspector' },
					el( PanelBody, { title: __( 'Display Settings', 'stackless-theme' ), initialOpen: true },
						el( SelectControl, {
							label: __( 'Position', 'stackless-theme' ),
							help: __( 'Right/Left positions float outside content on wide screens (1200px+). Inline displays within content flow.', 'stackless-theme' ),
							options: [
								{ label: __( 'Right Side (floating)', 'stackless-theme' ), value: 'right' },
								{ label: __( 'Left Side (floating)', 'stackless-theme' ), value: 'left' },
								{ label: __( 'Inline (within content)', 'stackless-theme' ), value: 'inline' }
							],
							value: attributes.position,
							onChange: function( val ) { props.setAttributes({ position: val }); }
						}),
						el( RangeControl, {
							label: __( 'Panel Width', 'stackless-theme' ),
							help: __( 'Width in pixels for promotion panels.', 'stackless-theme' ),
							value: attributes.width,
							onChange: function( val ) { props.setAttributes({ width: val }); },
							min: 150,
							max: 300,
							step: 10
						})
					),
					el( PanelBody, { title: __( 'Usage Tips', 'stackless-theme' ), initialOpen: false },
						el( 'p', { style: { marginTop: 0 } }, __( 'Add promotion items inside this block. Each item can have:', 'stackless-theme' ) ),
						el( 'ul', { style: { marginLeft: '1.2em', marginBottom: 0 } },
							el( 'li', null, __( 'An image (recommended: 200-250px wide)', 'stackless-theme' ) ),
							el( 'li', null, __( 'Optional title text', 'stackless-theme' ) ),
							el( 'li', null, __( 'Link URL', 'stackless-theme' ) ),
							el( 'li', null, __( 'Option to open in new tab', 'stackless-theme' ) )
						)
					)
				),
				el(
					'div', blockProps,
					el( 'div', { className: 'md-side-promotion-label' },
						__( 'Side Promotion', 'stackless-theme' ) + ' (' + attributes.position + ')'
					),
					el( InnerBlocks, {
						allowedBlocks: [ 'stackless/side-promotion-item' ],
						template: [ [ 'stackless/side-promotion-item' ] ],
						renderAppender: InnerBlocks.ButtonBlockAppender
					})
				)
			);
		},
		save: function () {
			return el( InnerBlocks.Content, {} );
		}
	});

	registerBlockType( 'stackless/side-promotion-item', {
		apiVersion: 3,
		title: __( 'Promotion Item', 'stackless-theme' ),
		description: __( 'A single promotional panel with image, title, and link.', 'stackless-theme' ),
		parent: [ 'stackless/side-promotion' ],
		icon: 'format-image',
		attributes: {
			image: { type: 'string' },
			imageId: { type: 'number' },
			title: { type: 'string' },
			url: { type: 'string' },
			newTab: { type: 'boolean', default: false }
		},
		edit: function ( props ) {
			var attributes = props.attributes;

			var blockProps = useBlockProps({ className: 'md-side-promotion-item-editor' });

			return el(Fragment, null,
				el( InspectorControls, { key: 'inspector' },
					el( PanelBody, { title: __( 'Promotion Settings', 'stackless-theme' ), initialOpen: true },
						el( MediaUploadCheck, null,
							el( MediaUpload, {
								onSelect: function( media ) {
									props.setAttributes({
										image: media.url,
										imageId: media.id
									});
								},
								allowedTypes: [ 'image' ],
								value: attributes.imageId,
								render: function( obj ) {
									return el( 'div', { style: { marginBottom: '16px' } },
										attributes.image
											? el( 'div', null,
												el( 'img', {
													src: attributes.image,
													style: { maxWidth: '100%', marginBottom: '8px', borderRadius: '4px' }
												}),
												el( Button, {
													isSecondary: true,
													isSmall: true,
													onClick: obj.open,
													style: { marginRight: '8px' }
												}, __( 'Replace', 'stackless-theme' ) ),
												el( Button, {
													isDestructive: true,
													isSmall: true,
													onClick: function() {
														props.setAttributes({ image: '', imageId: null });
													}
												}, __( 'Remove', 'stackless-theme' ) )
											)
											: el( Button, {
												isSecondary: true,
												onClick: obj.open
											}, __( 'Select Image', 'stackless-theme' ) )
									);
								}
							})
						),
						el( TextControl, {
							label: __( 'Title', 'stackless-theme' ),
							help: __( 'Optional text shown below image.', 'stackless-theme' ),
							value: attributes.title,
							onChange: function( val ) { props.setAttributes({ title: val }); },
							placeholder: __( 'Promotion title...', 'stackless-theme' )
						}),
						el( TextControl, {
							label: __( 'URL', 'stackless-theme' ),
							value: attributes.url,
							onChange: function( val ) { props.setAttributes({ url: val }); },
							placeholder: 'https://example.com/offer'
						}),
						el( CheckboxControl, {
							label: __( 'Open link in new tab', 'stackless-theme' ),
							checked: attributes.newTab,
							onChange: function( val ) { props.setAttributes({ newTab: val }); }
						})
					)
				),
				el(
					'div', blockProps,
					el( MediaUploadCheck, null,
						el( MediaUpload, {
							onSelect: function( media ) {
								props.setAttributes({
									image: media.url,
									imageId: media.id
								});
							},
							allowedTypes: [ 'image' ],
							value: attributes.imageId,
							render: function( obj ) {
								return el(
									'div', { className: 'md-side-promotion-image-wrap' },
									attributes.image
										? [
											el( 'img', {
												key: 'img',
												src: attributes.image,
												className: 'md-side-promotion-image'
											}),
											el( Button, {
												key: 'remove',
												className: 'md-side-promotion-remove',
												isDestructive: true,
												isSmall: true,
												onClick: function() {
													props.setAttributes({ image: '', imageId: null });
												}
											}, el( 'span', { className: 'dashicons dashicons-no-alt' } ) )
										]
										: el( Button, {
											className: 'md-side-promotion-upload',
											onClick: obj.open
										},
											el( 'span', { className: 'dashicons dashicons-format-image' } ),
											__( ' Select Image', 'stackless-theme' )
										)
								);
							}
						})
					),
					el( TextControl, {
						label: __( 'Title (optional)', 'stackless-theme' ),
						value: attributes.title,
						onChange: function( val ) { props.setAttributes({ title: val }); },
						placeholder: __( 'Promotion title...', 'stackless-theme' )
					}),
					el( TextControl, {
						label: __( 'URL', 'stackless-theme' ),
						value: attributes.url,
						onChange: function( val ) { props.setAttributes({ url: val }); },
						placeholder: 'https://example.com/offer'
					}),
					el( CheckboxControl, {
						label: __( 'Open in new tab', 'stackless-theme' ),
						checked: attributes.newTab,
						onChange: function( val ) { props.setAttributes({ newTab: val }); }
					})
				)
			);
		},
		save: function () {
			return null;
		}
	});
})( window.wp );
