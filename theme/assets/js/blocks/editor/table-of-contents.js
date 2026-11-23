/**
 * Stackless block editor script: Table of Contents.
 *
 * Block names, attributes and save output are a storage contract shared with
 * the Marketers Delight blocks of the same name. Do not rename, add or drop
 * attributes here.
 */
( function ( wp ) {
	var registerBlockType = wp.blocks.registerBlockType,
		el                = wp.element.createElement,
		Fragment          = wp.element.Fragment,
		InspectorControls = wp.blockEditor.InspectorControls,
		useBlockProps     = wp.blockEditor.useBlockProps,
		PanelBody         = wp.components.PanelBody,
		TextControl       = wp.components.TextControl,
		SelectControl     = wp.components.SelectControl,
		ToggleControl     = wp.components.ToggleControl,
		RangeControl      = wp.components.RangeControl,
		CheckboxControl   = wp.components.CheckboxControl,
		Notice            = wp.components.Notice,
		useSelect         = wp.data.useSelect,
		useMemo           = wp.element.useMemo,
		__                = wp.i18n.__;

	var LEVELS = [ 'h1', 'h2', 'h3', 'h4', 'h5', 'h6' ];

	/*
	 * Site defaults, localized from the theme settings.
	 *
	 * The preview fell back to a hardcoded "Table of Contents" and treated an
	 * empty Numbering value as 'none', while the page resolves both from the
	 * settings. A site titling its TOC "On this page" and numbering it therefore
	 * saw a different heading and an unnumbered list in the canvas, for a block
	 * whose own fields were empty in both places.
	 */
	var DEFAULTS = window.StacklessTocDefaults || {};

	function defaultTitle() {
		return DEFAULTS.title || __( 'Table of Contents', 'stackless-theme' );
	}

	function effectiveNumbering( value ) {
		return value || DEFAULTS.numbering || 'none';
	}

	/*
	 * The three toggles have no attribute default: an unset toggle means "use
	 * the site setting", as it does on the page. A default of true was always
	 * serialized as set, so every block overrode the site-wide TOC settings.
	 */
	var TOGGLE_FALLBACK = { hierarchical: true, collapsible: false, collapsed: false };

	function effectiveToggle( value, key ) {
		if ( typeof value === 'boolean' ) return value;
		return typeof DEFAULTS[ key ] === 'boolean' ? DEFAULTS[ key ] : TOGGLE_FALLBACK[ key ];
	}

	/**
	 * Strip markup out of a heading's stored content the way the server does,
	 * so the preview matches what will actually render.
	 */
	function plainText( html ) {
		var div = document.createElement( 'div' );
		div.innerHTML = html || '';
		return ( div.textContent || '' ).replace( /\s+/g, ' ' ).trim();
	}

	/**
	 * Collect headings from the editor's blocks, including nested ones, so the
	 * preview fills in live as the post is written.
	 */
	function collectHeadings( blocks, out ) {
		out = out || [];

		( blocks || [] ).forEach( function ( block ) {
			if ( block.name === 'core/heading' ) {
				var level = block.attributes.level || 2;
				var text = plainText( block.attributes.content );
				var className = block.attributes.className || '';
				var skipped = ( ' ' + className + ' ' ).indexOf( ' no-toc ' ) !== -1;

				if ( text ) {
					out.push( { level: level, text: text, skipped: skipped } );
				}
			}

			if ( block.innerBlocks && block.innerBlocks.length ) {
				collectHeadings( block.innerBlocks, out );
			}
		});

		return out;
	}

	registerBlockType( 'stackless/table-of-contents', {
		apiVersion: 3,
		title: __( 'Table of Contents', 'stackless-theme' ),
		keywords: [ __( 'table of contents', 'stackless-theme' ), __( 'toc', 'stackless-theme' ), __( 'outline', 'stackless-theme' ), __( 'headings', 'stackless-theme' ), __( 'jump links', 'stackless-theme' ) ],
		description: __( 'List the headings in this post, with links that jump to each section.', 'stackless-theme' ),
		category: 'stackless',
		icon: 'list-view',
		supports: {
			html: false,
			multiple: false
		},
		attributes: {
			title: { type: 'string', default: '' },
			levels: { type: 'string', default: '' },
			hierarchical: { type: 'boolean' },
			numbering: { type: 'string', default: '' },
			collapsible: { type: 'boolean' },
			collapsed: { type: 'boolean' },
			exclude: { type: 'string', default: '' },
			min: { type: 'number', default: 0 }
		},

		// "Transform to" from a Rank Math TOC, with the house defaults:
		// collapsible and collapsed. The list is rebuilt from the headings.
		transforms: {
			from: [ {
				type: 'block',
				blocks: [ 'rank-math/toc-block' ],
				transform: function () {
					return wp.blocks.createBlock( 'stackless/table-of-contents', { collapsible: true, collapsed: true } );
				}
			} ]
		},
		edit: function ( props ) {
			var atts = props.attributes;
			// Mirrors the class list the server-side render builds, so the canvas is
			// styled by the same rules the front end uses rather than an
			// approximation of them.
			var numbering = effectiveNumbering( atts.numbering );
			var numbered = numbering !== 'none';
			var hierarchical = effectiveToggle( atts.hierarchical, 'hierarchical' );
			var collapsible = effectiveToggle( atts.collapsible, 'collapsible' );
			var collapsed = effectiveToggle( atts.collapsed, 'collapsed' );
			var classes = [ 'md-toc', 'md-toc-editor-preview' ];
			if ( collapsible ) classes.push( 'md-toc-collapsible' );
			if ( collapsible && collapsed ) classes.push( 'is-collapsed' );
			if ( numbered ) classes.push( 'md-toc-numbered' );

			var blockProps = useBlockProps( {
				className: classes.join( ' ' ),
				'data-md-toc': true,
				'data-numbering': numbering
			} );

			// The selector returns a string, not the list, so the block re-renders
			// only when the headings change. A new array never compares equal.
			var headingsKey = useSelect( function ( select ) {
				var store = select( 'core/block-editor' );
				return JSON.stringify( collectHeadings( store ? store.getBlocks() : [] ) );
			}, [] );
			var headings = useMemo( function () {
				return JSON.parse( headingsKey );
			}, [ headingsKey ] );

			// With no levels chosen, the preview lists what the page will: the default levels.
			var levelList = atts.levels || DEFAULTS.levels || '';
			var active = levelList
				? levelList.split( ',' ).map( function ( l ) { return l.trim().toLowerCase(); } )
				: null;

			var excluded = atts.exclude
				? atts.exclude.split( ',' ).map( function ( t ) { return t.trim().toLowerCase(); } ).filter( Boolean )
				: [];

			var shown = headings.filter( function ( heading ) {
				if ( heading.skipped ) return false;
				if ( active && active.indexOf( 'h' + heading.level ) === -1 ) return false;
				for ( var i = 0; i < excluded.length; i++ ) {
					if ( heading.text.toLowerCase().indexOf( excluded[i] ) !== -1 ) return false;
				}
				return true;
			});

			function toggleLevel( level, on ) {
				var current = active ? active.slice() : [ 'h2', 'h3' ];
				var at = current.indexOf( level );
				if ( on && at === -1 ) current.push( level );
				if ( ! on && at !== -1 ) current.splice( at, 1 );
				current.sort();
				props.setAttributes( { levels: current.join( ',' ) } );
			}

			var controls = el( InspectorControls, null,
				el( PanelBody, { title: __( 'Table of Contents', 'stackless-theme' ) },
					el( TextControl, {
						label: __( 'Title', 'stackless-theme' ),
						value: atts.title,
						placeholder: defaultTitle(),
						onChange: function ( v ) { props.setAttributes( { title: v } ); }
					}),
					el( ToggleControl, {
						label: __( 'Nest headings by level', 'stackless-theme' ),
						checked: hierarchical,
						onChange: function ( v ) { props.setAttributes( { hierarchical: v } ); }
					}),
					el( SelectControl, {
						label: __( 'Numbering', 'stackless-theme' ),
						value: atts.numbering,
						options: [
							{ label: __( 'Use site default', 'stackless-theme' ), value: '' },
							{ label: __( 'None', 'stackless-theme' ), value: 'none' },
							{ label: __( '1, 2, 3', 'stackless-theme' ), value: 'decimal' },
							{ label: __( 'i, ii, iii', 'stackless-theme' ), value: 'roman' },
							{ label: __( 'a, b, c', 'stackless-theme' ), value: 'alpha' }
						],
						onChange: function ( v ) { props.setAttributes( { numbering: v } ); }
					})
				),

				el( PanelBody, { title: __( 'Headings', 'stackless-theme' ), initialOpen: false },
					el( 'p', { className: 'components-base-control__help' },
						__( 'Leave every level unchecked to use the site default.', 'stackless-theme' )
					),
					LEVELS.map( function ( level ) {
						return el( CheckboxControl, {
							key: level,
							label: level.toUpperCase(),
							checked: atts.levels ? active.indexOf( level ) !== -1 : false,
							onChange: function ( on ) { toggleLevel( level, on ); }
						});
					}),
					el( TextControl, {
						label: __( 'Exclude headings containing', 'stackless-theme' ),
						help: __( 'Comma separated. To skip one heading instead, give it the class no-toc.', 'stackless-theme' ),
						value: atts.exclude,
						onChange: function ( v ) { props.setAttributes( { exclude: v } ); }
					}),
					el( RangeControl, {
						label: __( 'Minimum headings', 'stackless-theme' ),
						value: atts.min,
						min: 0,
						max: 20,
						help: __( 'Zero uses the site default.', 'stackless-theme' ),
						onChange: function ( v ) { props.setAttributes( { min: v || 0 } ); }
					})
				),

				el( PanelBody, { title: __( 'Behavior', 'stackless-theme' ), initialOpen: false },
					el( ToggleControl, {
						label: __( 'Collapsible', 'stackless-theme' ),
						checked: collapsible,
						onChange: function ( v ) { props.setAttributes( { collapsible: v } ); }
					}),
					el( ToggleControl, {
						label: __( 'Start collapsed', 'stackless-theme' ),
						checked: collapsed,
						disabled: ! collapsible,
						onChange: function ( v ) { props.setAttributes( { collapsed: v } ); }
					})
				)
			);

			var preview;

			// The render callback returns nothing below the minimum, so say that
			// here rather than previewing a list the page will not show.
			var belowMin = atts.min > 0 && shown.length < atts.min;

			if ( ! shown.length || belowMin ) {
				preview = el( Notice, { status: 'info', isDismissible: false },
					belowMin
						? __( 'Below the minimum for this block, so nothing will render. Headings found: ', 'stackless-theme' ) + shown.length + ' / ' + atts.min
						: ( headings.length
							? __( 'No headings match the levels chosen for this block.', 'stackless-theme' )
							: __( 'Add some headings and they will appear here.', 'stackless-theme' ) )
				);
			} else {
				var base = Math.min.apply( null, shown.map( function ( h ) { return h.level; } ) );
				
				var listTag = numbered ? 'ol' : 'ul';

				/*
				 * Mirrors the server-side nested list: descend one level at a time so a
				 * skipped level cannot open a list with no item to close it. The
				 * preview built a flat list whatever the settings said, which is
				 * why nesting and numbering both looked broken in the editor.
				 */
				function buildNested( items ) {
					var idx = 0;
					function walk( depth ) {
						var children = [];
						while ( idx < items.length ) {
							var lvl = Math.min( items[ idx ].level - base, depth + 1 );
							if ( lvl < depth ) break;
							// Deeper than this list: nest it under the item above. With no
							// item above (a heading that skips a level, h2 then h4) it
							// stays in this list, so every pass consumes a heading.
							if ( lvl > depth && children.length ) {
								var last = children.length - 1;
								children[ last ] = el( 'li', { key: 'i' + idx, className: 'md-toc-item' },
									children[ last ].props.children, walk( depth + 1 ) );
								continue;
							}
							var item = items[ idx ];
							idx++;
							children.push( el( 'li', { key: 'i' + idx, className: 'md-toc-item' },
								el( 'span', { className: 'md-toc-link', 'data-level': item.level }, item.text ) ) );
						}
						return children.length
							? el( listTag, { className: depth ? 'md-toc-sublist' : 'md-toc-list' }, children )
							: null;
					}
					return walk( 0 );
				}

				var body = hierarchical
					? buildNested( shown )
					: el( listTag, { className: 'md-toc-list md-toc-flat' },
						shown.map( function ( heading, i ) {
							return el( 'li', {
								key: i,
								className: 'md-toc-item',
								'data-depth': heading.level - base
							}, el( 'span', { className: 'md-toc-link', 'data-level': heading.level }, heading.text ) );
						} ) );

				preview = el( Fragment, null,
					el( 'div', { className: 'md-toc-head' },
						el( 'p', { className: 'md-toc-title' }, atts.title || defaultTitle() ),
						collapsible
							? el( 'span', {
								className: 'md-toc-toggle',
								'aria-hidden': 'true'
							}, el( 'span', { className: 'md-toc-chevron' } ) )
							: null
					),
					el( 'div', {
						className: 'md-toc-body',
						hidden: !! ( collapsible && collapsed )
					}, body )
				);
			}

			return el( Fragment, null, controls, el( 'nav', blockProps, preview ) );
		},

		save: function () {
			return null;
		}
	});
})( window.wp );
