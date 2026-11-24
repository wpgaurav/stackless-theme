/**
 * Stackless block editor script: Related Content.
 *
 * Block names, attributes and save output are a storage contract: content
 * saved with them has to keep parsing. Do not rename, add or drop attributes
 * here.
 */
( function ( wp ) {
	var registerBlockType = wp.blocks.registerBlockType,
		el = wp.element.createElement,
		Fragment = wp.element.Fragment,
		useState = wp.element.useState,
		useEffect = wp.element.useEffect,
		useRef = wp.element.useRef,
		InspectorControls = wp.blockEditor.InspectorControls,
		useBlockProps = wp.blockEditor.useBlockProps,
		PanelColorSettings = wp.blockEditor.PanelColorSettings,
		PanelBody = wp.components.PanelBody,
		TextControl = wp.components.TextControl,
		TextareaControl = wp.components.TextareaControl,
		SelectControl = wp.components.SelectControl,
		CheckboxControl = wp.components.CheckboxControl,
		MediaUpload = wp.blockEditor.MediaUpload,
		MediaUploadCheck = wp.blockEditor.MediaUploadCheck,
		Button = wp.components.Button,
		Spinner = wp.components.Spinner,
		__ = wp.i18n.__;

	registerBlockType( 'stackless/related-content', {
		apiVersion: 3,
		title: __( 'Related Content', 'stackless-theme' ),
		description: __( 'Link to related articles to keep readers engaged.', 'stackless-theme' ),
		icon: 'admin-links',
		category: 'stackless',
		deprecated: [
			{
				// Saved blocks validate (save is null), so without this the editor
				// never runs the migration and the old string `style` is dropped.
				isEligible: function ( attributes ) { return typeof attributes.style === 'string'; },
				attributes: {
					label: { type: 'string', default: 'Recommended Reading' },
					title: { type: 'string' },
					description: { type: 'string' },
					url: { type: 'string' },
					image: { type: 'string' },
					imageId: { type: 'number' },
					postId: { type: 'number', default: 0 },
					style: { type: 'string', default: 'card' },
					ctaText: { type: 'string', default: 'Read More' },
					labelBgColor: { type: 'string' },
					labelTextColor: { type: 'string' },
					bgColor: { type: 'string' },
					borderColor: { type: 'string' },
					titleColor: { type: 'string' },
					descColor: { type: 'string' },
					ctaColor: { type: 'string' },
					marginBottom: { type: 'string', default: 'mb-double' },
					shadow: { type: 'boolean', default: false },
					newTab: { type: 'boolean', default: false }
				},
				save: function () { return null; },
				migrate: function ( attributes ) {
					var newAttrs = Object.assign( {}, attributes, { cardStyle: attributes.style || 'card' } );
					delete newAttrs.style;
					return newAttrs;
				}
			}
		],
		attributes: {
			label: { type: 'string', default: 'Recommended Reading' },
			title: { type: 'string' },
			description: { type: 'string' },
			url: { type: 'string' },
			image: { type: 'string' },
			imageId: { type: 'number' },
			postId: { type: 'number', default: 0 },
			cardStyle: { type: 'string', default: 'card' },
			ctaText: { type: 'string', default: 'Read More' },
			labelBgColor: { type: 'string' },
			labelTextColor: { type: 'string' },
			bgColor: { type: 'string' },
			borderColor: { type: 'string' },
			titleColor: { type: 'string' },
			descColor: { type: 'string' },
			ctaColor: { type: 'string' },
			marginBottom: { type: 'string', default: 'mb-double' },
			shadow: { type: 'boolean', default: false },
			newTab: { type: 'boolean', default: false },
			// Multi-post + variations
			posts: { type: 'array', default: [] },
			layout: { type: 'string', default: '' },
			columns: { type: 'number', default: 3 },
			showExcerpts: { type: 'boolean', default: true },
			showCta: { type: 'boolean', default: true }
		},
		edit: function ( props ) {
			var attributes = props.attributes;
			var searchState = useState( '' );
			var searchTerm = searchState[0];
			var setSearchTerm = searchState[1];
			var resultsState = useState( [] );
			var searchResults = resultsState[0];
			var setSearchResults = resultsState[1];
			var loadingState = useState( false );
			var isSearching = loadingState[0];
			var setIsSearching = loadingState[1];
			var timerRef = useRef( null );

			useEffect( function () {
				if ( timerRef.current ) clearTimeout( timerRef.current );

				if ( searchTerm.length < 2 ) {
					setSearchResults( [] );
					setIsSearching( false );
					return;
				}

				setIsSearching( true );

				timerRef.current = setTimeout( function () {
					var url = StacklessRelatedContent.ajaxUrl +
						'?action=stackless_search_posts&nonce=' + StacklessRelatedContent.nonce +
						'&search=' + encodeURIComponent( searchTerm );

					fetch( url )
						.then( function ( r ) { return r.json(); } )
						.then( function ( data ) {
							setSearchResults( data.success ? data.data : [] );
							setIsSearching( false );
						} )
						.catch( function () {
							setSearchResults( [] );
							setIsSearching( false );
						} );
				}, 1000 );

				return function () {
					if ( timerRef.current ) clearTimeout( timerRef.current );
				};
			}, [ searchTerm ] );

			// Append a search result to the posts array. If the array is
			// empty AND no legacy single-post attrs are set, this is the
			// natural multi-post path. Selecting a post when a legacy
			// title/url is already set also migrates that legacy entry
			// into the posts array so both end up in the same place.
			function selectPost( result ) {
				var existing = ( attributes.posts || [] ).slice();
				// Promote legacy single-post to posts[0] on first add.
				if ( existing.length === 0 && attributes.title && attributes.url ) {
					existing.push( {
						postId: attributes.postId || 0,
						title: attributes.title,
						description: attributes.description || '',
						url: attributes.url,
						image: attributes.image || ''
					} );
				}
				// Skip duplicates by id or url.
				var dup = existing.some( function ( p ) {
					return ( p.postId && p.postId === result.id ) || ( p.url && p.url === result.url );
				} );
				if ( ! dup ) {
					existing.push( {
						postId: result.id,
						title: result.title,
						description: result.excerpt || '',
						url: result.url,
						image: result.thumbnail || ''
					} );
				}
				var patch = { posts: existing };
				// When multi-post is active, default layout to 'grid' if unset.
				if ( existing.length > 1 && ! attributes.layout ) patch.layout = 'grid';
				// Clear legacy single-post fields so they don't render twice.
				if ( existing.length >= 1 ) {
					patch.title = '';
					patch.description = '';
					patch.url = '';
					patch.image = '';
					patch.postId = 0;
				}
				props.setAttributes( patch );
				setSearchTerm( '' );
				setSearchResults( [] );
			}

			function removePost( idx ) {
				var next = ( attributes.posts || [] ).slice();
				next.splice( idx, 1 );
				props.setAttributes( { posts: next } );
			}

			function movePost( idx, delta ) {
				var next = ( attributes.posts || [] ).slice();
				var target = idx + delta;
				if ( target < 0 || target >= next.length ) return;
				var tmp = next[ idx ];
				next[ idx ] = next[ target ];
				next[ target ] = tmp;
				props.setAttributes( { posts: next } );
			}

			// Effective layout: explicit value wins; else fall back to legacy
			// cardStyle for the editor preview only (PHP does the same).
			function effectiveLayout() {
				if ( attributes.layout ) return attributes.layout;
				if ( ( attributes.posts || [] ).length > 0 ) return 'grid';
				return attributes.cardStyle || 'card';
			}
			var layout = effectiveLayout();
			var hasMulti = ( attributes.posts || [] ).length > 0;

			var blockProps = useBlockProps( {
				className: 'stb-related-content-editor stb-related-content--' + layout
					+ ' stb-related-content-' + layout
					+ ( layout === 'grid' ? ' stb-related-content--cols-' + ( attributes.columns || 3 ) : '' )
					+ ' ' + attributes.marginBottom
					+ ( attributes.shadow ? ' has-shadow' : '' ),
				style: {
					backgroundColor: attributes.bgColor || '',
					borderColor: attributes.borderColor || ''
				}
			} );

			return el( Fragment, null,
				el( InspectorControls, null,
					el( PanelBody, { title: __( 'Search Posts', 'stackless-theme' ), initialOpen: true },
						el( TextControl, {
							label: __( 'Search', 'stackless-theme' ),
							value: searchTerm,
							onChange: setSearchTerm,
							placeholder: __( 'Type to search posts...', 'stackless-theme' )
						} ),
						isSearching && el( 'div', { style: { display: 'flex', alignItems: 'center', gap: '8px', marginBottom: '8px' } },
							el( Spinner, null ),
							el( 'span', null, __( 'Searching...', 'stackless-theme' ) )
						),
						searchResults.length > 0 && el( 'div', {
							style: { border: '1px solid #ddd', borderRadius: '4px', maxHeight: '300px', overflow: 'auto' }
						},
							searchResults.map( function ( result ) {
								return el( 'button', {
									key: result.id,
									type: 'button',
									onClick: function () { selectPost( result ); },
									style: {
										display: 'flex',
										alignItems: 'center',
										gap: '10px',
										width: '100%',
										padding: '8px 10px',
										border: 'none',
										borderBottom: '1px solid #eee',
										background: 'none',
										cursor: 'pointer',
										textAlign: 'left',
										fontSize: '13px'
									}
								},
									result.thumbnail
										? el( 'img', {
											src: result.thumbnail,
											style: { width: '40px', height: '40px', objectFit: 'cover', borderRadius: '3px', flexShrink: 0 }
										} )
										: el( 'span', {
											className: 'dashicons dashicons-format-aside',
											style: { width: '40px', height: '40px', lineHeight: '40px', textAlign: 'center', flexShrink: 0, color: '#999' }
										} ),
									el( 'span', { style: { flex: 1, minWidth: 0 } },
										el( 'strong', { style: { display: 'block', whiteSpace: 'nowrap', overflow: 'hidden', textOverflow: 'ellipsis' } }, result.title ),
										el( 'span', { style: { color: '#757575', fontSize: '11px' } }, result.type )
									)
								);
							} )
						),
						// Selected posts list (multi-post mode)
						hasMulti && el( 'div', { style: { marginTop: '10px' } },
							el( 'p', { className: 'components-base-control__help', style: { fontWeight: 600, marginBottom: '6px' } },
								__( 'Selected posts (', 'stackless-theme' ) + ( attributes.posts || [] ).length + ')'
							),
							( attributes.posts || [] ).map( function ( p, idx ) {
								return el( 'div', {
									key: idx,
									style: {
										display: 'flex', alignItems: 'center', gap: '6px',
										padding: '6px', border: '1px solid #ddd', borderRadius: '4px',
										marginBottom: '4px', background: '#fff', fontSize: '12px'
									}
								},
									p.image
										? el( 'img', { src: p.image, style: { width: '32px', height: '32px', objectFit: 'cover', borderRadius: '3px', flexShrink: 0 } } )
										: el( 'span', { className: 'dashicons dashicons-format-aside', style: { width: '32px', height: '32px', lineHeight: '32px', textAlign: 'center', flexShrink: 0, color: '#999' } } ),
									el( 'span', { style: { flex: 1, minWidth: 0, overflow: 'hidden', textOverflow: 'ellipsis', whiteSpace: 'nowrap' } }, p.title ),
									el( 'button', { type: 'button', className: 'components-button is-tertiary is-small', onClick: function () { movePost( idx, -1 ); }, disabled: idx === 0, title: __( 'Move up', 'stackless-theme' ), style: { padding: '0 4px' } }, '↑' ),
									el( 'button', { type: 'button', className: 'components-button is-tertiary is-small', onClick: function () { movePost( idx, 1 ); }, disabled: idx === ( attributes.posts || [] ).length - 1, title: __( 'Move down', 'stackless-theme' ), style: { padding: '0 4px' } }, '↓' ),
									el( 'button', { type: 'button', className: 'components-button is-tertiary is-small is-destructive', onClick: function () { removePost( idx ); }, title: __( 'Remove', 'stackless-theme' ), style: { padding: '0 4px' } }, '×' )
								);
							} )
						),
						// Legacy single-post indicator (only when posts[] is empty)
						! hasMulti && attributes.postId ? el( 'p', { className: 'components-base-control__help' },
							__( 'Linked to post #', 'stackless-theme' ) + attributes.postId + '. ',
							el( 'button', {
								type: 'button',
								className: 'components-button is-link is-destructive',
								onClick: function () {
									props.setAttributes( { postId: 0, title: '', description: '', url: '', image: '', imageId: null } );
								}
							}, __( 'Clear', 'stackless-theme' ) )
						) : null
					),
					el( PanelBody, { title: __( 'Content', 'stackless-theme' ), initialOpen: false },
						el( TextControl, {
							label: __( 'Label', 'stackless-theme' ),
							value: attributes.label,
							onChange: function ( val ) { props.setAttributes( { label: val } ); }
						} ),
						el( TextControl, {
							label: __( 'Title', 'stackless-theme' ),
							value: attributes.title,
							onChange: function ( val ) { props.setAttributes( { title: val } ); },
							placeholder: __( 'Article title...', 'stackless-theme' )
						} ),
						el( TextareaControl, {
							label: __( 'Description', 'stackless-theme' ),
							value: attributes.description,
							onChange: function ( val ) { props.setAttributes( { description: val } ); },
							placeholder: __( 'Brief description...', 'stackless-theme' ),
							rows: 2
						} ),
						el( TextControl, {
							label: __( 'URL', 'stackless-theme' ),
							value: attributes.url,
							onChange: function ( val ) { props.setAttributes( { url: val } ); },
							placeholder: 'https://...'
						} ),
						el( TextControl, {
							label: __( 'CTA Text', 'stackless-theme' ),
							value: attributes.ctaText,
							onChange: function ( val ) { props.setAttributes( { ctaText: val } ); }
						} ),
						el( CheckboxControl, {
							label: __( 'Open in new tab', 'stackless-theme' ),
							checked: attributes.newTab,
							onChange: function ( val ) { props.setAttributes( { newTab: val } ); }
						} )
					),
					el( PanelBody, { title: __( 'Image', 'stackless-theme' ), initialOpen: false },
						el( MediaUploadCheck, null,
							el( MediaUpload, {
								onSelect: function ( media ) {
									props.setAttributes( {
										image: media.url,
										imageId: media.id
									} );
								},
								allowedTypes: [ 'image' ],
								value: attributes.imageId,
								render: function ( obj ) {
									return el( 'div', null,
										attributes.image
											? el( 'div', null,
												el( 'img', {
													src: attributes.image,
													style: { maxWidth: '100%', marginBottom: '8px', borderRadius: '4px' }
												} ),
												el( 'div', { style: { display: 'flex', gap: '8px' } },
													el( Button, {
														isSecondary: true,
														isSmall: true,
														onClick: obj.open
													}, __( 'Replace', 'stackless-theme' ) ),
													el( Button, {
														isDestructive: true,
														isSmall: true,
														onClick: function () {
															props.setAttributes( { image: '', imageId: null } );
														}
													}, __( 'Remove', 'stackless-theme' ) )
												)
											)
											: el( Button, {
												isSecondary: true,
												onClick: obj.open
											}, __( 'Select Image', 'stackless-theme' ) )
									);
								}
							} )
						),
						el( 'p', { className: 'components-base-control__help' },
							__( 'Image is shown in Card style only. Recommended: 16:9 aspect ratio.', 'stackless-theme' )
						)
					),
					el( PanelBody, { title: __( 'Layout', 'stackless-theme' ), initialOpen: true },
						el( SelectControl, {
							label: __( 'Variation', 'stackless-theme' ),
							help: __( 'Card / Spotlight = single post. Grid / List / Inline = multi-post.', 'stackless-theme' ),
							options: [
								{ label: __( 'Card — image + text row', 'stackless-theme' ), value: 'card' },
								{ label: __( 'Spotlight — large hero', 'stackless-theme' ), value: 'spotlight' },
								{ label: __( 'Grid — square cards', 'stackless-theme' ), value: 'grid' },
								{ label: __( 'List — vertical rows', 'stackless-theme' ), value: 'list' },
								{ label: __( 'Inline — pill links', 'stackless-theme' ), value: 'inline' },
								{ label: __( 'Compact (legacy)', 'stackless-theme' ), value: 'compact' },
								{ label: __( 'Minimal Link (legacy)', 'stackless-theme' ), value: 'minimal' }
							],
							value: layout,
							onChange: function ( val ) { props.setAttributes( { layout: val } ); }
						} ),
						layout === 'grid' && el( SelectControl, {
							label: __( 'Columns', 'stackless-theme' ),
							options: [
								{ label: __( '2 columns', 'stackless-theme' ), value: 2 },
								{ label: __( '3 columns', 'stackless-theme' ), value: 3 }
							],
							value: attributes.columns || 3,
							onChange: function ( val ) { props.setAttributes( { columns: parseInt( val, 10 ) || 3 } ); }
						} ),
						el( CheckboxControl, {
							label: __( 'Show excerpts', 'stackless-theme' ),
							checked: attributes.showExcerpts !== false,
							onChange: function ( val ) { props.setAttributes( { showExcerpts: val } ); }
						} ),
						el( CheckboxControl, {
							label: __( 'Show "Read more" CTA', 'stackless-theme' ),
							checked: attributes.showCta !== false,
							onChange: function ( val ) { props.setAttributes( { showCta: val } ); }
						} )
					),
					el( PanelColorSettings, {
						title: __( 'Colors', 'stackless-theme' ),
						initialOpen: false,
						colorSettings: [
							{
								value: attributes.labelBgColor,
								label: __( 'Label Background', 'stackless-theme' ),
								onChange: function ( val ) { props.setAttributes( { labelBgColor: val } ); }
							},
							{
								value: attributes.labelTextColor,
								label: __( 'Label Text', 'stackless-theme' ),
								onChange: function ( val ) { props.setAttributes( { labelTextColor: val } ); }
							},
							{
								value: attributes.bgColor,
								label: __( 'Card Background', 'stackless-theme' ),
								onChange: function ( val ) { props.setAttributes( { bgColor: val } ); }
							},
							{
								value: attributes.borderColor,
								label: __( 'Border Color', 'stackless-theme' ),
								onChange: function ( val ) { props.setAttributes( { borderColor: val } ); }
							},
							{
								value: attributes.titleColor,
								label: __( 'Title Color', 'stackless-theme' ),
								onChange: function ( val ) { props.setAttributes( { titleColor: val } ); }
							},
							{
								value: attributes.ctaColor,
								label: __( 'CTA Color', 'stackless-theme' ),
								onChange: function ( val ) { props.setAttributes( { ctaColor: val } ); }
							}
						]
					} ),
					el( PanelBody, { title: __( 'Design', 'stackless-theme' ), initialOpen: false },
						el( SelectControl, {
							label: __( 'Margin Bottom', 'stackless-theme' ),
							options: [
								{ label: __( 'None', 'stackless-theme' ), value: 'mb-none' },
								{ label: __( 'Half', 'stackless-theme' ), value: 'mb-half' },
								{ label: __( 'Single', 'stackless-theme' ), value: 'mb-single' },
								{ label: __( 'Double (default)', 'stackless-theme' ), value: 'mb-double' },
								{ label: __( 'Triple', 'stackless-theme' ), value: 'mb-triple' }
							],
							value: attributes.marginBottom,
							onChange: function ( val ) { props.setAttributes( { marginBottom: val } ); }
						} ),
						el( CheckboxControl, {
							label: __( 'Add shadow', 'stackless-theme' ),
							checked: attributes.shadow,
							onChange: function ( val ) { props.setAttributes( { shadow: val } ); }
						} )
					)
				),
				el( 'div', blockProps,
					attributes.label && el( 'div', {
						className: 'stb-related-content-label caps small bold',
						style: {
							backgroundColor: attributes.labelBgColor || '',
							color: attributes.labelTextColor || ''
						}
					}, attributes.label ),
					hasMulti
						? el( 'div', {
							className: 'stb-related-content-items '
								+ ( layout === 'grid' ? 'grid-' + ( attributes.columns || 3 ) + ' gap-half' : '' )
								+ ( layout === 'list' ? 'flex-column gap-half' : '' )
								+ ( layout === 'inline' ? 'flex-row flex-wrap gap-half' : '' )
						},
							( attributes.posts || [] ).map( function ( p, idx ) {
								return el( 'div', { key: idx, className: 'stb-related-content-item stb-related-content-link' },
									p.image && layout !== 'inline' && layout !== 'minimal'
										? el( 'div', { className: 'stb-related-content-image' },
											el( 'img', { className: 'stb-related-content-img', src: p.image, alt: p.title } )
										)
										: null,
									el( 'div', { className: 'stb-related-content-text block-half' },
										el( 'span', { className: 'stb-related-content-title bold' }, p.title || __( 'Article Title', 'stackless-theme' ) ),
										p.description && attributes.showExcerpts !== false && layout !== 'inline' && layout !== 'minimal'
											? el( 'span', { className: 'stb-related-content-desc small text-sec' }, p.description )
											: null,
										attributes.showCta !== false && layout !== 'inline' && layout !== 'minimal'
											? el( 'span', { className: 'stb-related-content-cta small bold' }, ( attributes.ctaText || 'Read More' ) + ' →' )
											: null
									)
								);
							} )
						)
						: el( 'div', { className: 'stb-related-content-items' },
							el( 'div', { className: 'stb-related-content-item stb-related-content-link' },
								( layout === 'card' || layout === 'spotlight' ) && el( 'div', { className: 'stb-related-content-image-wrap stb-related-content-image' },
									attributes.image
										? el( 'img', { className: 'stb-related-content-img', src: attributes.image } )
										: el( MediaUploadCheck, null,
											el( MediaUpload, {
												onSelect: function ( media ) {
													props.setAttributes( { image: media.url, imageId: media.id } );
												},
												allowedTypes: [ 'image' ],
												value: attributes.imageId,
												render: function ( obj ) {
													return el( Button, {
														className: 'stb-related-content-upload',
														onClick: obj.open
													}, el( 'span', { className: 'dashicons dashicons-format-image' } ), __( ' Add Image', 'stackless-theme' ) );
												}
											} )
										)
								),
								el( 'div', { className: 'stb-related-content-text block-half' },
									el( 'div', {
										className: 'stb-related-content-title bold',
										style: { color: attributes.titleColor || '' }
									}, attributes.title || __( 'Article Title', 'stackless-theme' ) ),
									layout !== 'minimal' && el( 'div', { className: 'stb-related-content-desc small text-sec' },
										attributes.description || __( 'Description text...', 'stackless-theme' )
									),
									el( 'div', {
										className: 'stb-related-content-cta small bold',
										style: { color: attributes.ctaColor || '' }
									}, attributes.ctaText + ' →' )
								)
							)
						)
				)
			);
		},
		save: function () {
			return null;
		}
	} );
} )( window.wp );
