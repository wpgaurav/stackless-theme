/**
 * Stackless block editor script: FAQ and FAQ Item.
 *
 * Block names, attributes and save output are a storage contract shared with
 * the Marketers Delight blocks of the same name. Do not rename, add or drop
 * attributes here.
 */
( function ( wp ) {
	var registerBlockType  = wp.blocks.registerBlockType,
		el                 = wp.element.createElement,
		Fragment           = wp.element.Fragment,
		InnerBlocks        = wp.blockEditor.InnerBlocks,
		InspectorControls  = wp.blockEditor.InspectorControls,
		RichText           = wp.blockEditor.RichText,
		useBlockProps      = wp.blockEditor.useBlockProps,
		PanelBody          = wp.components.PanelBody,
		ToggleControl      = wp.components.ToggleControl,
		__                 = wp.i18n.__;

	/* ------------------------------------------------------------------ */
	/*  Parent: FAQ container                                              */
	/* ------------------------------------------------------------------ */

	registerBlockType( 'stackless/faq', {
		apiVersion: 3,
		title: __( 'FAQ', 'stackless-theme' ),
		description: __( 'Create a schema-ready FAQ section with accordions.', 'stackless-theme' ),
		icon: 'editor-help',
		category: 'stackless',
		attributes: {
			schemaEnabled: { type: 'boolean', default: true },
			multiOpen: {
				type: 'boolean',
				default: false
			},
			firstOpen: {
				type: 'boolean',
				default: true
			}
		},
		edit: function ( props ) {
			var a = props.attributes;
			var blockProps = useBlockProps( { className: 'faq-editor' } );

			return el( Fragment, null,
				el( InspectorControls, null,
					el( PanelBody, { title: __( 'Settings', 'stackless-theme' ), initialOpen: true },
					el( ToggleControl, { label: __( 'Include FAQ schema', 'stackless-theme' ), help: __( 'Optional vocabulary for other consumers. Google no longer displays FAQ rich results.', 'stackless-theme' ), checked: a.schemaEnabled, onChange: function(v) { props.setAttributes({schemaEnabled:v}); } } ),
						el( ToggleControl, {
							label: __( 'Allow multiple open', 'stackless-theme' ),
							help: __( 'When enabled, opening one item does not close others.', 'stackless-theme' ),
							checked: a.multiOpen,
							onChange: function ( v ) { props.setAttributes( { multiOpen: v } ); }
						} ),
						el( ToggleControl, {
							label: __( 'First item open by default', 'stackless-theme' ),
							checked: a.firstOpen,
							onChange: function ( v ) { props.setAttributes( { firstOpen: v } ); }
						} )
					)
				),
				el( 'div', blockProps,
					el( InnerBlocks, {
						allowedBlocks: [ 'stackless/faq-item' ],
						template: [ [ 'stackless/faq-item' ] ]
					} )
				)
			);
		},
		save: function () {
			return el( InnerBlocks.Content, {} );
		}
	} );

	/* ------------------------------------------------------------------ */
	/*  Child: FAQ item                                                    */
	/* ------------------------------------------------------------------ */

	registerBlockType( 'stackless/faq-item', {
		apiVersion: 3,
		title: __( 'FAQ Item', 'stackless-theme' ),
		description: __( 'A single question and answer pair.', 'stackless-theme' ),
		parent: [ 'stackless/faq' ],
		icon: 'editor-help',
		attributes: {
			question: { type: 'string' },
			answer: { type: 'string' }
		},
		edit: function ( props ) {
			var a = props.attributes;
			var blockProps = useBlockProps( { className: 'faq-item-editor' } );

			return el( 'div', blockProps,
				el( 'div', { className: 'faq-q' },
					el( RichText, {
						tagName: 'p',
						className: 'faq-q-text',
						value: a.question,
						placeholder: __( 'Write your question…', 'stackless-theme' ),
						onChange: function ( val ) { props.setAttributes( { question: val } ); }
					} )
				),
				el( 'div', { className: 'faq-a' },
					el( RichText, {
						tagName: 'div',
						className: 'faq-a-text',
						value: a.answer,
						placeholder: __( 'Write the answer…', 'stackless-theme' ),
						onChange: function ( val ) { props.setAttributes( { answer: val } ); }
					} )
				)
			);
		},
		save: function () {
			return null;
		}
	} );
} )( window.wp );
