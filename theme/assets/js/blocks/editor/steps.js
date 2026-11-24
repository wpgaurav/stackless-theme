/**
 * Stackless block editor script: Steps and Step.
 *
 * Block names, attributes and save output are a storage contract: content
 * saved with them has to keep parsing. Do not rename, add or drop attributes
 * here.
 */
( function ( wp ) {
	var registerBlockType = wp.blocks.registerBlockType,
		el = wp.element.createElement,
		InnerBlocks = wp.blockEditor.InnerBlocks,
		useBlockProps = wp.blockEditor.useBlockProps,
		__ = wp.i18n.__;

	registerBlockType( 'stackless/steps', {
		apiVersion: 3,
		title: __( 'Steps', 'stackless-theme' ),
		description: __( 'Create a sequence of steps.', 'stackless-theme' ),
		icon: 'editor-ol',
		category: 'stackless',
		// "Transform to" from an ordered core List: each item becomes a Step.
		transforms: {
			from: [ {
				type: 'block',
				blocks: [ 'core/list' ],
				isMatch: function ( attributes ) { return !! attributes.ordered; },
				transform: function ( attributes, innerBlocks ) {
					var steps = ( innerBlocks || [] ).map( function ( b ) {
						return wp.blocks.createBlock( 'stackless/step', { text: b.attributes && b.attributes.content ? String( b.attributes.content ) : '' } );
					} );
					return wp.blocks.createBlock( 'stackless/steps', {}, steps );
				}
			} ]
		},
		edit: function ( props ) {
			var blockProps = useBlockProps({ className: 'stb-steps-editor' });
			return el(
				'div', blockProps,
				el( InnerBlocks, {
					allowedBlocks: [ 'stackless/step' ],
					template: [
						[ 'stackless/step', { number: '1' } ],
						[ 'stackless/step', { number: '2' } ]
					]
				} )
			);
		},
		save: function ( props ) {
			return el( InnerBlocks.Content, {} );
		}
	});

	registerBlockType( 'stackless/step', {
		apiVersion: 3,
		title: __( 'Step', 'stackless-theme' ),
		parent: [ 'stackless/steps' ],
		icon: 'editor-ol',
		attributes: {
			number: { type: 'string' },
			title: { type: 'string' },
			text: { type: 'string' }
		},
		edit: function ( props ) {
			var RichText = wp.blockEditor.RichText;
			var blockProps = useBlockProps({ className: 'stb-step-editor' });
			return el(
				'div', blockProps,
				el( 'div', { className: 'stb-step-number-editor' },
					el( RichText, {
						tagName: 'span',
						value: props.attributes.number,
						placeholder: '1',
						onChange: function( val ) { props.setAttributes({ number: val }); }
					})
				),
				el( 'div', { className: 'stb-step-content-editor' },
					el( RichText, {
						tagName: 'h3',
						value: props.attributes.title,
						placeholder: __( 'Step Title', 'stackless-theme' ),
						onChange: function( val ) { props.setAttributes({ title: val }); }
					}),
					el( RichText, {
						tagName: 'p',
						value: props.attributes.text,
						placeholder: __( 'Step description...', 'stackless-theme' ),
						onChange: function( val ) { props.setAttributes({ text: val }); }
					})
				)
			);
		},
		save: function ( props ) {
			return null;
		}
	});
})( window.wp );
