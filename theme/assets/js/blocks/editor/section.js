/**
 * Stackless block editor script: Section.
 *
 * Block names, attributes and save output are a storage contract shared with
 * the Marketers Delight blocks of the same name. Do not rename, add or drop
 * attributes here.
 */
(function (wp) {
	var registerBlockType = wp.blocks.registerBlockType,
		el = wp.element.createElement,
		Fragment = wp.element.Fragment,
		InnerBlocks = wp.blockEditor.InnerBlocks,
		InspectorControls = wp.blockEditor.InspectorControls,
		useBlockProps = wp.blockEditor.useBlockProps,
		PanelBody = wp.components.PanelBody,
		SelectControl = wp.components.SelectControl,
		TextControl = wp.components.TextControl,
		ToggleControl = wp.components.ToggleControl,
		Button = wp.components.Button,
		__ = wp.i18n.__,
		useState = wp.element.useState;

	var tagOptions = [
		{ label: 'div', value: 'div' },
		{ label: 'section', value: 'section' },
		{ label: 'article', value: 'article' },
		{ label: 'aside', value: 'aside' },
		{ label: 'header', value: 'header' },
		{ label: 'footer', value: 'footer' },
		{ label: 'main', value: 'main' },
		{ label: 'nav', value: 'nav' },
		{ label: 'figure', value: 'figure' },
		{ label: 'details', value: 'details' },
		{ label: __( 'Custom', 'stackless-theme' ), value: 'custom' }
	];

	var roleOptions = [
		{ label: __( 'None', 'stackless-theme' ), value: '' },
		{ label: 'banner', value: 'banner' },
		{ label: 'complementary', value: 'complementary' },
		{ label: 'contentinfo', value: 'contentinfo' },
		{ label: 'form', value: 'form' },
		{ label: 'main', value: 'main' },
		{ label: 'marquee', value: 'marquee' },
		{ label: 'navigation', value: 'navigation' },
		{ label: 'region', value: 'region' },
		{ label: 'search', value: 'search' },
		{ label: 'tablist', value: 'tablist' },
		{ label: 'tabpanel', value: 'tabpanel' },
		{ label: 'presentation', value: 'presentation' },
		{ label: 'none', value: 'none' },
		{ label: __( 'Custom', 'stackless-theme' ), value: 'custom' }
	];

	function AttributeRow( rowProps ) {
		return el( 'div', { style: { display: 'flex', gap: '4px', marginBottom: '4px', alignItems: 'flex-start' } },
			el( TextControl, {
				value: rowProps.name,
				onChange: function ( val ) { rowProps.onChangeName( val ); },
				placeholder: 'data-tab',
				__nextHasNoMarginBottom: true,
				style: { flex: 1 }
			}),
			el( TextControl, {
				value: rowProps.attrValue,
				onChange: function ( val ) { rowProps.onChangeValue( val ); },
				placeholder: 'plugins',
				__nextHasNoMarginBottom: true,
				style: { flex: 1 }
			}),
			el( Button, {
				icon: 'no-alt',
				isSmall: true,
				isDestructive: true,
				label: __( 'Remove', 'stackless-theme' ),
				onClick: rowProps.onRemove,
				style: { marginTop: '6px' }
			})
		);
	}

	function parseAttributes( str ) {
		if ( ! str ) return [];
		var attrs = [];
		var regex = /([\w-]+)="([^"]*)"/g;
		var match;
		while ( ( match = regex.exec( str ) ) !== null ) {
			attrs.push({ name: match[1], attrValue: match[2] });
		}
		return attrs;
	}

	function serializeAttributes( attrs ) {
		return attrs
			.filter( function ( a ) { return a.name.trim(); } )
			.map( function ( a ) { return a.name.trim() + '="' + a.attrValue + '"'; } )
			.join( ' ' );
	}

	registerBlockType( 'stackless/section', {
		apiVersion: 3,
		title: __( 'Section', 'stackless-theme' ),
		description: __( 'A flexible container with semantic HTML tags, custom attributes, and CSS.', 'stackless-theme' ),
		icon: 'editor-insertmore',
		category: 'stackless',
		attributes: {
			htmlTag:        { type: 'string',  default: 'div' },
			customTag:      { type: 'string',  default: '' },
			sectionCSS:     { type: 'string',  default: '' },
			htmlAttributes: { type: 'string',  default: '' },
			ariaHidden:     { type: 'boolean', default: false },
			ariaLabel:      { type: 'string',  default: '' },
			ariaLabelledBy: { type: 'string',  default: '' },
			role:           { type: 'string',  default: '' },
			customRole:     { type: 'string',  default: '' },
			hidden:         { type: 'boolean', default: false }
		},
		edit: function ( props ) {
			var a = props.attributes;
			var set = props.setAttributes;
			var tag = a.htmlTag === 'custom' && a.customTag ? a.customTag : a.htmlTag;

			// Build extra props for live preview
			var extraProps = {};
			var resolvedRole = ( a.role === 'custom' && a.customRole ) ? a.customRole : a.role;
			if ( resolvedRole ) extraProps.role = resolvedRole;
			if ( a.ariaHidden ) extraProps['aria-hidden'] = 'true';
			if ( a.ariaLabel ) extraProps['aria-label'] = a.ariaLabel;
			if ( a.ariaLabelledBy ) extraProps['aria-labelledby'] = a.ariaLabelledBy;
			if ( a.hidden ) extraProps.hidden = true;

			// Parse htmlAttributes into props (style needs special handling)
			if ( a.htmlAttributes ) {
				var parsed = parseAttributes( a.htmlAttributes );
				for ( var pi = 0; pi < parsed.length; pi++ ) {
					var pName = parsed[pi].name.trim();
					var pVal = parsed[pi].attrValue;
					if ( ! pName ) continue;
					if ( pName === 'style' ) {
						// Convert CSS string to style object for React
						var styleObj = {};
						pVal.split( ';' ).forEach( function ( rule ) {
							var parts = rule.split( ':' );
							if ( parts.length >= 2 ) {
								var prop = parts[0].trim();
								var val = parts.slice( 1 ).join( ':' ).trim();
								if ( prop && val ) {
									// Convert CSS prop to camelCase for React
									var camel = prop.replace( /-([a-z])/g, function ( _, c ) { return c.toUpperCase(); } );
									// Keep CSS custom properties as-is
									if ( prop.indexOf( '--' ) === 0 ) {
										styleObj[prop] = val;
									} else {
										styleObj[camel] = val;
									}
								}
							}
						});
						extraProps.style = Object.assign( {}, extraProps.style || {}, styleObj );
					} else {
						extraProps[pName] = pVal;
					}
				}
			}

			var blockProps = useBlockProps( extraProps );

			var attrState = useState( function () { return parseAttributes( a.htmlAttributes ); } );
			var rows = attrState[0];
			var setRows = attrState[1];

			function updateRows( newRows ) {
				setRows( newRows );
				set({ htmlAttributes: serializeAttributes( newRows ) });
			}

			return el( Fragment, null,
				el( InspectorControls, null,

					el( PanelBody, { title: __( 'HTML Tag', 'stackless-theme' ) },
						el( SelectControl, {
							label: __( 'Tag', 'stackless-theme' ),
							help: __( 'Semantic HTML element for this container', 'stackless-theme' ),
							options: tagOptions,
							value: a.htmlTag,
							onChange: function ( val ) { set({ htmlTag: val }); }
						}),
						a.htmlTag === 'custom' ? el( TextControl, {
							label: __( 'Custom Tag', 'stackless-theme' ),
							value: a.customTag,
							onChange: function ( val ) { set({ customTag: val }); }
						}) : ''
					),

					el( PanelBody, { title: __( 'Accessibility', 'stackless-theme' ), initialOpen: false },
						el( SelectControl, {
							label: __( 'Role', 'stackless-theme' ),
							options: roleOptions,
							value: a.role,
							onChange: function ( val ) { set({ role: val }); }
						}),
						a.role === 'custom' ? el( TextControl, {
							label: __( 'Custom Role', 'stackless-theme' ),
							value: a.customRole,
							onChange: function ( val ) { set({ customRole: val }); }
						}) : null,
						el( TextControl, {
							label: __( 'aria-label', 'stackless-theme' ),
							help: __( 'Accessible label for this section', 'stackless-theme' ),
							value: a.ariaLabel,
							onChange: function ( val ) { set({ ariaLabel: val }); }
						}),
						el( TextControl, {
							label: __( 'aria-labelledby', 'stackless-theme' ),
							help: __( 'ID of the element that labels this section', 'stackless-theme' ),
							value: a.ariaLabelledBy,
							onChange: function ( val ) { set({ ariaLabelledBy: val }); }
						}),
						el( ToggleControl, {
							label: __( 'aria-hidden', 'stackless-theme' ),
							help: __( 'Hide from screen readers (decorative elements)', 'stackless-theme' ),
							checked: a.ariaHidden,
							onChange: function ( val ) { set({ ariaHidden: val }); }
						}),
						el( ToggleControl, {
							label: __( 'hidden', 'stackless-theme' ),
							help: __( 'Hide element from display (e.g. inactive tab panels)', 'stackless-theme' ),
							checked: a.hidden,
							onChange: function ( val ) { set({ hidden: val }); }
						})
					),

					el( PanelBody, { title: __( 'HTML Attributes', 'stackless-theme' ), initialOpen: false },
						el( 'p', { style: { fontSize: '12px', color: '#757575', marginTop: 0 } },
							__( 'Add data-*, tabindex, or other safe attributes.', 'stackless-theme' )
						),
						el( 'div', { style: { display: 'flex', gap: '4px', marginBottom: '8px', fontSize: '11px', color: '#757575' } },
							el( 'span', { style: { flex: 1 } }, __( 'Name', 'stackless-theme' ) ),
							el( 'span', { style: { flex: 1 } }, __( 'Value', 'stackless-theme' ) ),
							el( 'span', { style: { width: '30px' } } )
						),
						rows.map( function ( row, i ) {
							return el( AttributeRow, {
								key: i,
								name: row.name,
								attrValue: row.attrValue,
								onChangeName: function ( val ) {
									var next = rows.slice();
									next[i] = { name: val, attrValue: row.attrValue };
									updateRows( next );
								},
								onChangeValue: function ( val ) {
									var next = rows.slice();
									next[i] = { name: row.name, attrValue: val };
									updateRows( next );
								},
								onRemove: function () {
									var next = rows.filter( function ( _, idx ) { return idx !== i; } );
									updateRows( next );
								}
							});
						}),
						el( Button, {
							variant: 'secondary',
							isSmall: true,
							onClick: function () {
								updateRows( rows.concat([{ name: '', attrValue: '' }]) );
							}
						}, __( '+ Add Attribute', 'stackless-theme' ) )
					),

					el( PanelBody, { title: __( 'Section CSS', 'stackless-theme' ), initialOpen: false },
						el( 'textarea', {
							value: a.sectionCSS,
							onChange: function ( e ) { set({ sectionCSS: e.target.value }); },
							placeholder: '.my-section {\n  display: grid;\n  gap: 2rem;\n}',
							rows: 10,
							style: {
								width: '100%',
								fontFamily: 'monospace',
								fontSize: '12px',
								lineHeight: '1.5',
								padding: '8px',
								border: '1px solid #ddd',
								borderRadius: '4px',
								resize: 'vertical',
								tabSize: 4
							}
						})
					)
				),
				a.sectionCSS ? el( 'style', null, a.sectionCSS ) : null,
				el( tag, blockProps,
					el( InnerBlocks, { templateLock: false } )
				)
			);
		},
		save: function () {
			return el( InnerBlocks.Content );
		}
	} );
})( window.wp );
