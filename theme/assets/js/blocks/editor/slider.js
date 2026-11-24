/**
 * Stackless block editor script: Slider and Slide.
 *
 * Block names, attributes and save output are a storage contract: content
 * saved with them has to keep parsing. Do not rename, add or drop attributes
 * here. Both blocks save only their inner blocks; PHP renders the markup.
 *
 * The canvas shows one slide at a time, with buttons to step through, add a
 * slide, or show every slide stacked for reordering. Selecting a block inside
 * a slide (from List View, say) brings that slide into view.
 */
( function ( wp ) {
	var registerBlockType = wp.blocks.registerBlockType,
		createBlock = wp.blocks.createBlock,
		el = wp.element.createElement,
		Fragment = wp.element.Fragment,
		useState = wp.element.useState,
		useEffect = wp.element.useEffect,
		useSelect = wp.data.useSelect,
		useDispatch = wp.data.useDispatch,
		be = wp.blockEditor,
		InnerBlocks = be.InnerBlocks,
		InspectorControls = be.InspectorControls,
		BlockControls = be.BlockControls,
		MediaUpload = be.MediaUpload,
		MediaUploadCheck = be.MediaUploadCheck,
		MediaReplaceFlow = be.MediaReplaceFlow,
		useBlockProps = be.useBlockProps,
		useInnerBlocksProps = be.useInnerBlocksProps,
		AlignmentMatrix = be.__experimentalBlockAlignmentMatrixControl || be.BlockAlignmentMatrixControl,
		c = wp.components,
		__ = wp.i18n.__,
		sprintf = wp.i18n.sprintf;

	var HEIGHTS = { small: '24rem', medium: '32rem', large: '40rem' };

	var SLIDE_TEMPLATE = [
		[ 'core/heading', { level: 2, placeholder: __( 'Slide heading', 'stackless-theme' ) } ],
		[ 'core/paragraph', { placeholder: __( 'A line or two about this slide.', 'stackless-theme' ) } ],
		[ 'core/buttons', {}, [ [ 'core/button', { placeholder: __( 'Shop now', 'stackless-theme' ) } ] ] ]
	];

	function sliderHeight( a ) {
		if ( a.height === 'viewport' ) {
			var vh = Math.max( 30, Math.min( 100, parseInt( a.viewportHeight, 10 ) || 100 ) );
			return vh + 'svh';
		}
		return HEIGHTS[ a.height ] || HEIGHTS.large;
	}

	/* ------------------------------------------------------------------ */
	/*  Parent: Slider                                                     */
	/* ------------------------------------------------------------------ */

	registerBlockType( 'stackless/slider', {
		apiVersion: 3,
		title: __( 'Slider', 'stackless-theme' ),
		description: __( 'Full-width photo slides with a heading, text and buttons on each.', 'stackless-theme' ),
		icon: 'slides',
		category: 'stackless',
		keywords: [ 'slider', 'slideshow', 'hero', 'banner', 'carousel' ],
		attributes: {
			label: { type: 'string', default: '' },
			height: { type: 'string', default: 'large' },
			viewportHeight: { type: 'number', default: 100 },
			autoplay: { type: 'boolean', default: false },
			interval: { type: 'number', default: 6 },
			arrows: { type: 'boolean', default: true },
			dots: { type: 'boolean', default: true },
			loop: { type: 'boolean', default: true },
			align: { type: 'string', default: 'full' }
		},
		example: {
			innerBlocks: [ { name: 'stackless/slide', innerBlocks: [ { name: 'core/heading', attributes: { content: __( 'New season', 'stackless-theme' ) } } ] } ]
		},
		edit: function ( props ) {
			var a = props.attributes,
				clientId = props.clientId;
			var state = useState( 0 ), active = state[ 0 ], setActive = state[ 1 ];
			var all = useState( false ), showAll = all[ 0 ], setShowAll = all[ 1 ];

			var data = useSelect( function ( select ) {
				var store = select( 'core/block-editor' );
				var order = store.getBlockOrder( clientId );
				var selected = store.getSelectedBlockClientId();
				var path = selected ? [ selected ].concat( store.getBlockParents( selected ) ) : [];
				var holding = -1;
				for ( var i = 0; i < order.length; i++ ) {
					if ( path.indexOf( order[ i ] ) !== -1 ) {
						holding = i;
						break;
					}
				}
				return { order: order, holding: holding };
			}, [ clientId ] );
			var count = data.order.length;
			var dispatch = useDispatch( 'core/block-editor' );

			// Show the slide that holds the selected block.
			useEffect( function () {
				if ( data.holding !== -1 && data.holding !== active ) {
					setActive( data.holding );
				}
			}, [ data.holding ] );
			var current = Math.max( 0, Math.min( active, count - 1 ) );

			var go = function ( by ) {
				if ( count ) {
					setActive( ( current + by + count ) % count );
				}
			};
			var addSlide = function () {
				var block = createBlock( 'stackless/slide', {}, [] );
				dispatch.insertBlock( block, current + 1, clientId );
				setActive( current + 1 );
			};

			var blockProps = useBlockProps( {
				className: 'stb-slider stb-scroller stb-slider-editor stb-slider-h-' + ( a.height || 'large' ) + ( showAll ? ' is-showing-all' : '' ) + ( a.arrows ? '' : ' has-no-arrows' ),
				style: { '--stb-slider-height': sliderHeight( a ) }
			} );
			var innerProps = useInnerBlocksProps( { className: 'stb-scroller-track' }, {
				allowedBlocks: [ 'stackless/slide' ],
				template: [ [ 'stackless/slide' ], [ 'stackless/slide' ] ],
				orientation: showAll ? 'vertical' : 'horizontal',
				renderAppender: false
			} );

			var bar = el( 'div', { className: 'stb-slider-editor-bar', contentEditable: false },
				el( 'span', { className: 'stb-slider-editor-count' },
					showAll
						? sprintf( /* translators: %d: number of slides. */ __( '%d slides', 'stackless-theme' ), count )
						: sprintf( /* translators: 1: slide number, 2: number of slides. */ __( 'Slide %1$d of %2$d', 'stackless-theme' ), count ? current + 1 : 0, count )
				),
				! showAll && el( c.Button, { icon: 'arrow-left-alt2', label: __( 'Previous slide', 'stackless-theme' ), size: 'small', disabled: count < 2, onClick: function () { go( -1 ); } } ),
				! showAll && el( c.Button, { icon: 'arrow-right-alt2', label: __( 'Next slide', 'stackless-theme' ), size: 'small', disabled: count < 2, onClick: function () { go( 1 ); } } ),
				el( c.Button, { icon: 'plus', variant: 'secondary', size: 'small', onClick: addSlide }, __( 'Add slide', 'stackless-theme' ) ),
				el( c.Button, { variant: 'tertiary', size: 'small', isPressed: showAll, onClick: function () { setShowAll( ! showAll ); } },
					showAll ? __( 'One at a time', 'stackless-theme' ) : __( 'Show all slides', 'stackless-theme' ) )
			);

			// One slide in view: the others are hidden by position, so nothing in the slides changes.
			var hide = ! showAll && count > 1 ? el( 'style', null,
				'#block-' + clientId + ' > .stb-scroller-track > .wp-block-stackless-slide:not(:nth-child(' + ( current + 1 ) + ')){display:none}'
			) : null;

			return el( Fragment, null,
				el( BlockControls, { group: 'block' },
					el( c.ToolbarButton, { icon: 'plus', label: __( 'Add slide', 'stackless-theme' ), onClick: addSlide } ),
					el( c.ToolbarButton, { icon: 'excerpt-view', label: showAll ? __( 'Show one slide at a time', 'stackless-theme' ) : __( 'Show all slides', 'stackless-theme' ), isPressed: showAll, onClick: function () { setShowAll( ! showAll ); } } )
				),
				el( InspectorControls, null,
					el( c.PanelBody, { title: __( 'Slider', 'stackless-theme' ), initialOpen: true },
						el( c.TextControl, {
							label: __( 'Label', 'stackless-theme' ),
							help: __( 'Read out by screen readers, as in "Featured collections". Defaults to "Slideshow".', 'stackless-theme' ),
							value: a.label,
							onChange: function ( v ) { props.setAttributes( { label: v } ); }
						} ),
						el( c.SelectControl, {
							label: __( 'Height', 'stackless-theme' ),
							value: a.height,
							options: [
								{ label: __( 'Small', 'stackless-theme' ), value: 'small' },
								{ label: __( 'Medium', 'stackless-theme' ), value: 'medium' },
								{ label: __( 'Large', 'stackless-theme' ), value: 'large' },
								{ label: __( 'Screen height', 'stackless-theme' ), value: 'viewport' }
							],
							help: __( 'Slides grow taller when their content needs the room.', 'stackless-theme' ),
							onChange: function ( v ) { props.setAttributes( { height: v } ); }
						} ),
						a.height === 'viewport' && el( c.RangeControl, {
							label: __( 'Share of the screen height (%)', 'stackless-theme' ),
							min: 30, max: 100, step: 5,
							value: a.viewportHeight,
							onChange: function ( v ) { props.setAttributes( { viewportHeight: v || 100 } ); }
						} ),
						el( c.ToggleControl, { label: __( 'Arrows', 'stackless-theme' ), checked: a.arrows, onChange: function ( v ) { props.setAttributes( { arrows: v } ); } } ),
						el( c.ToggleControl, { label: __( 'Dots', 'stackless-theme' ), checked: a.dots, onChange: function ( v ) { props.setAttributes( { dots: v } ); } } ),
						el( c.ToggleControl, { label: __( 'Loop', 'stackless-theme' ), help: __( 'The arrows go from the last slide back to the first.', 'stackless-theme' ), checked: a.loop, onChange: function ( v ) { props.setAttributes( { loop: v } ); } } )
					),
					el( c.PanelBody, { title: __( 'Autoplay', 'stackless-theme' ), initialOpen: false },
						el( c.ToggleControl, {
							label: __( 'Play automatically', 'stackless-theme' ),
							help: __( 'Adds a pause button. Pauses while the pointer or keyboard is on the slider, and never plays for visitors who ask for reduced motion.', 'stackless-theme' ),
							checked: a.autoplay,
							onChange: function ( v ) { props.setAttributes( { autoplay: v } ); }
						} ),
						a.autoplay && el( c.RangeControl, {
							label: __( 'Seconds per slide', 'stackless-theme' ),
							min: 2, max: 30, step: 1,
							value: a.interval,
							onChange: function ( v ) { props.setAttributes( { interval: v || 6 } ); }
						} )
					)
				),
				el( 'div', blockProps, hide, bar, el( 'div', innerProps ) )
			);
		},
		save: function () {
			return el( InnerBlocks.Content, {} );
		}
	} );

	/* ------------------------------------------------------------------ */
	/*  Child: Slide                                                       */
	/* ------------------------------------------------------------------ */

	registerBlockType( 'stackless/slide', {
		apiVersion: 3,
		title: __( 'Slide', 'stackless-theme' ),
		description: __( 'One slide: a photo, an overlay, and blocks laid over it.', 'stackless-theme' ),
		icon: 'format-image',
		category: 'stackless',
		parent: [ 'stackless/slider' ],
		attributes: {
			mediaId: { type: 'number', default: 0 },
			mediaUrl: { type: 'string', default: '' },
			focalPoint: { type: 'object', default: { x: 0.5, y: 0.5 } },
			dimRatio: { type: 'number', default: 40 },
			contentPosition: { type: 'string', default: 'center center' }
		},
		edit: function ( props ) {
			var a = props.attributes;
			var focal = a.focalPoint || { x: 0.5, y: 0.5 };
			var parts = ( a.contentPosition || 'center center' ).split( ' ' );
			var vertical = [ 'top', 'center', 'bottom' ].indexOf( parts[ 0 ] ) !== -1 ? parts[ 0 ] : 'center';
			var horizontal = [ 'left', 'center', 'right' ].indexOf( parts[ 1 ] ) !== -1 ? parts[ 1 ] : 'center';
			var hasImage = !! a.mediaUrl;

			var onSelect = function ( media ) {
				if ( ! media || ! media.url ) {
					return;
				}
				var sizes = media.sizes || ( media.media_details && media.media_details.sizes ) || {};
				var large = sizes.large || sizes.full;
				props.setAttributes( { mediaId: media.id || 0, mediaUrl: ( large && ( large.url || large.source_url ) ) || media.url } );
			};
			var removeImage = function () {
				props.setAttributes( { mediaId: 0, mediaUrl: '', focalPoint: { x: 0.5, y: 0.5 } } );
			};
			var setPosition = function ( v, h ) {
				props.setAttributes( { contentPosition: v + ' ' + h } );
			};

			var blockProps = useBlockProps( {
				className: 'stb-slide is-v-' + vertical + ' is-h-' + horizontal + ( hasImage ? ' has-image' : '' )
			} );
			var innerProps = useInnerBlocksProps( { className: 'stb-slide-inner' }, {
				template: SLIDE_TEMPLATE,
				templateLock: false
			} );

			var picker = function ( label, variant ) {
				return el( MediaUploadCheck, null,
					el( MediaUpload, {
						onSelect: onSelect,
						allowedTypes: [ 'image' ],
						value: a.mediaId,
						render: function ( o ) {
							return el( c.Button, { variant: variant, onClick: o.open }, label );
						}
					} )
				);
			};

			return el( Fragment, null,
				el( BlockControls, { group: 'other' },
					AlignmentMatrix && el( AlignmentMatrix, {
						label: __( 'Content position', 'stackless-theme' ),
						value: a.contentPosition || 'center center',
						onChange: function ( v ) { props.setAttributes( { contentPosition: v || 'center center' } ); }
					} ),
					MediaReplaceFlow && el( MediaReplaceFlow, {
						mediaId: a.mediaId,
						mediaURL: a.mediaUrl,
						allowedTypes: [ 'image' ],
						accept: 'image/*',
						name: hasImage ? __( 'Replace image', 'stackless-theme' ) : __( 'Add image', 'stackless-theme' ),
						onSelect: onSelect,
						onReset: hasImage ? removeImage : undefined
					} )
				),
				el( InspectorControls, null,
					el( c.PanelBody, { title: __( 'Slide image', 'stackless-theme' ), initialOpen: true },
						hasImage && c.FocalPointPicker && el( c.FocalPointPicker, {
							label: __( 'Focal point', 'stackless-theme' ),
							help: __( 'The part of the photo that stays in view when the slide is cropped.', 'stackless-theme' ),
							url: a.mediaUrl,
							value: focal,
							onChange: function ( v ) { props.setAttributes( { focalPoint: { x: Math.round( v.x * 100 ) / 100, y: Math.round( v.y * 100 ) / 100 } } ); }
						} ),
						el( 'div', { className: 'stb-slide-editor-media' },
							picker( hasImage ? __( 'Replace image', 'stackless-theme' ) : __( 'Choose image', 'stackless-theme' ), 'secondary' ),
							hasImage && el( c.Button, { variant: 'tertiary', isDestructive: true, onClick: removeImage }, __( 'Remove image', 'stackless-theme' ) )
						),
						hasImage && el( c.RangeControl, {
							label: __( 'Overlay opacity (%)', 'stackless-theme' ),
							help: __( 'Darkens the photo so light text stays readable.', 'stackless-theme' ),
							min: 0, max: 100, step: 5,
							value: a.dimRatio,
							onChange: function ( v ) { props.setAttributes( { dimRatio: v === undefined ? 40 : v } ); }
						} )
					),
					el( c.PanelBody, { title: __( 'Content position', 'stackless-theme' ), initialOpen: false },
						el( c.SelectControl, {
							label: __( 'Horizontal', 'stackless-theme' ),
							value: horizontal,
							options: [
								{ label: __( 'Left', 'stackless-theme' ), value: 'left' },
								{ label: __( 'Center', 'stackless-theme' ), value: 'center' },
								{ label: __( 'Right', 'stackless-theme' ), value: 'right' }
							],
							onChange: function ( v ) { setPosition( vertical, v ); }
						} ),
						el( c.SelectControl, {
							label: __( 'Vertical', 'stackless-theme' ),
							value: vertical,
							options: [
								{ label: __( 'Top', 'stackless-theme' ), value: 'top' },
								{ label: __( 'Middle', 'stackless-theme' ), value: 'center' },
								{ label: __( 'Bottom', 'stackless-theme' ), value: 'bottom' }
							],
							onChange: function ( v ) { setPosition( v, horizontal ); }
						} )
					)
				),
				el( 'div', blockProps,
					hasImage && el( 'img', {
						className: 'stb-slide-image',
						src: a.mediaUrl,
						alt: '',
						style: { objectPosition: ( focal.x * 100 ) + '% ' + ( focal.y * 100 ) + '%' }
					} ),
					hasImage && a.dimRatio > 0 && el( 'span', { className: 'stb-slide-overlay', 'aria-hidden': 'true', style: { opacity: a.dimRatio / 100 } } ),
					! hasImage && el( 'div', { className: 'stb-slide-editor-empty', contentEditable: false },
						picker( __( 'Add a slide image', 'stackless-theme' ), 'secondary' )
					),
					el( 'div', { className: 'stb-slide-content' }, el( 'div', innerProps ) )
				)
			);
		},
		save: function () {
			return el( InnerBlocks.Content, {} );
		}
	} );
} )( window.wp );
