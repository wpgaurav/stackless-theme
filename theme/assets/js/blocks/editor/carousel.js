/**
 * Stackless block editor script: Carousel.
 *
 * Block names, attributes and save output are a storage contract: content
 * saved with them has to keep parsing. Do not rename, add or drop attributes
 * here. The block saves only its inner blocks; PHP renders the markup.
 *
 * Each inner block is one item. A single Query Loop, Product Collection,
 * product grid, Columns or Gallery block scrolls its own list instead.
 */
( function ( wp ) {
	var registerBlockType = wp.blocks.registerBlockType,
		el = wp.element.createElement,
		Fragment = wp.element.Fragment,
		useSelect = wp.data.useSelect,
		be = wp.blockEditor,
		InnerBlocks = be.InnerBlocks,
		InspectorControls = be.InspectorControls,
		InspectorAdvancedControls = be.InspectorAdvancedControls,
		useBlockProps = be.useBlockProps,
		useInnerBlocksProps = be.useInnerBlocksProps,
		c = wp.components,
		__ = wp.i18n.__;

	var GAPS = { none: '0px', small: '.75rem', medium: '1.5rem', large: '2.5rem' };

	// Blocks whose own list becomes the row when one of them is the only block inside.
	var LISTS = /^(core\/(query|columns|gallery)|woocommerce\/|edd\/|fluent-cart\/|stackless\/query-loop)/;

	registerBlockType( 'stackless/carousel', {
		apiVersion: 3,
		title: __( 'Carousel', 'stackless-theme' ),
		description: __( 'A row of cards, images, products or posts that scrolls sideways.', 'stackless-theme' ),
		icon: 'images-alt2',
		category: 'stackless',
		keywords: [ 'carousel', 'scroller', 'products', 'cards', 'gallery' ],
		attributes: {
			label: { type: 'string', default: '' },
			perView: { type: 'number', default: 3 },
			perViewTablet: { type: 'number', default: 2 },
			perViewPhone: { type: 'number', default: 1 },
			gap: { type: 'string', default: 'medium' },
			arrows: { type: 'boolean', default: true },
			pagination: { type: 'string', default: 'dots' },
			snap: { type: 'boolean', default: true },
			loop: { type: 'boolean', default: false },
			autoplay: { type: 'boolean', default: false },
			interval: { type: 'number', default: 6 },
			listClass: { type: 'string', default: '' }
		},
		edit: function ( props ) {
			var a = props.attributes;
			var only = useSelect( function ( select ) {
				var blocks = select( 'core/block-editor' ).getBlocks( props.clientId );
				return blocks.length === 1 ? blocks[ 0 ].name : '';
			}, [ props.clientId ] );
			var isList = !! only && ( LISTS.test( only ) || !! a.listClass );

			var blockProps = useBlockProps( {
				className: 'stb-carousel stb-scroller stb-carousel-editor' + ( isList ? ' is-list' : '' ) + ( a.snap ? '' : ' has-no-snap' ),
				style: {
					'--stb-per-view': a.perView || 3,
					'--stb-per-view-tablet': a.perViewTablet || 2,
					'--stb-per-view-phone': a.perViewPhone || 1,
					'--stb-gap': GAPS[ a.gap ] || GAPS.medium
				}
			} );
			var innerProps = useInnerBlocksProps( { className: isList ? 'stb-carousel-body' : 'stb-scroller-track' }, {
				orientation: isList ? 'vertical' : 'horizontal',
				template: [
					[ 'core/group', { layout: { type: 'constrained' } }, [ [ 'core/image', {} ], [ 'core/heading', { level: 3, placeholder: __( 'Card title', 'stackless-theme' ) } ] ] ],
					[ 'core/group', { layout: { type: 'constrained' } }, [ [ 'core/image', {} ], [ 'core/heading', { level: 3, placeholder: __( 'Card title', 'stackless-theme' ) } ] ] ],
					[ 'core/group', { layout: { type: 'constrained' } }, [ [ 'core/image', {} ], [ 'core/heading', { level: 3, placeholder: __( 'Card title', 'stackless-theme' ) } ] ] ]
				],
				renderAppender: InnerBlocks.ButtonBlockAppender
			} );

			var perView = function ( key, label ) {
				return el( c.RangeControl, {
					label: label,
					min: 1, max: 8, step: 0.1,
					value: a[ key ],
					onChange: function ( v ) {
						var next = {};
						next[ key ] = v || 1;
						props.setAttributes( next );
					}
				} );
			};
			var toggle = function ( key, label, help ) {
				return el( c.ToggleControl, {
					label: label,
					help: help,
					checked: !! a[ key ],
					onChange: function ( v ) {
						var next = {};
						next[ key ] = v;
						props.setAttributes( next );
					}
				} );
			};

			return el( Fragment, null,
				el( InspectorControls, null,
					el( c.PanelBody, { title: __( 'Items in view', 'stackless-theme' ), initialOpen: true },
						perView( 'perView', __( 'Desktop', 'stackless-theme' ) ),
						perView( 'perViewTablet', __( 'Tablet (under 1024px)', 'stackless-theme' ) ),
						perView( 'perViewPhone', __( 'Phone (under 600px)', 'stackless-theme' ) ),
						el( 'p', { className: 'components-base-control__help' }, __( 'A fraction such as 1.2 lets the next item peek in.', 'stackless-theme' ) ),
						el( c.SelectControl, {
							label: __( 'Gap', 'stackless-theme' ),
							value: a.gap,
							options: [
								{ label: __( 'None', 'stackless-theme' ), value: 'none' },
								{ label: __( 'Small', 'stackless-theme' ), value: 'small' },
								{ label: __( 'Medium', 'stackless-theme' ), value: 'medium' },
								{ label: __( 'Large', 'stackless-theme' ), value: 'large' }
							],
							onChange: function ( v ) { props.setAttributes( { gap: v } ); }
						} )
					),
					el( c.PanelBody, { title: __( 'Controls', 'stackless-theme' ), initialOpen: true },
						el( c.TextControl, {
							label: __( 'Label', 'stackless-theme' ),
							help: __( 'Read out by screen readers, as in "New arrivals". Defaults to "Carousel".', 'stackless-theme' ),
							value: a.label,
							onChange: function ( v ) { props.setAttributes( { label: v } ); }
						} ),
						toggle( 'arrows', __( 'Arrows', 'stackless-theme' ) ),
						el( c.SelectControl, {
							label: __( 'Below the row', 'stackless-theme' ),
							value: a.pagination,
							options: [
								{ label: __( 'Dots', 'stackless-theme' ), value: 'dots' },
								{ label: __( 'Progress bar', 'stackless-theme' ), value: 'progress' },
								{ label: __( 'Nothing', 'stackless-theme' ), value: 'none' }
							],
							onChange: function ( v ) { props.setAttributes( { pagination: v } ); }
						} ),
						toggle( 'snap', __( 'Snap to items', 'stackless-theme' ), __( 'Scrolling settles with an item at the edge.', 'stackless-theme' ) ),
						toggle( 'loop', __( 'Loop', 'stackless-theme' ), __( 'The arrows go from the end back to the start.', 'stackless-theme' ) ),
						toggle( 'autoplay', __( 'Play automatically', 'stackless-theme' ), __( 'Adds a pause button. Pauses while the pointer or keyboard is on the carousel, and never plays for visitors who ask for reduced motion.', 'stackless-theme' ) ),
						a.autoplay && el( c.RangeControl, {
							label: __( 'Seconds per step', 'stackless-theme' ),
							min: 2, max: 30, step: 1,
							value: a.interval,
							onChange: function ( v ) { props.setAttributes( { interval: v || 6 } ); }
						} )
					)
				),
				el( InspectorAdvancedControls, null,
					el( c.TextControl, {
						label: __( 'List class to scroll', 'stackless-theme' ),
						help: __( 'With a single block inside, the class of its list to scroll, if it is not found on its own.', 'stackless-theme' ),
						value: a.listClass,
						onChange: function ( v ) { props.setAttributes( { listClass: v } ); }
					} )
				),
				el( 'div', blockProps, el( 'div', innerProps ) )
			);
		},
		save: function () {
			return el( InnerBlocks.Content, {} );
		}
	} );
} )( window.wp );
