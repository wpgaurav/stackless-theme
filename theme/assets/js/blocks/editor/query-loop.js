/**
 * Stackless block editor script: Query Loop.
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
		InspectorControls  = wp.blockEditor.InspectorControls,
		useBlockProps      = wp.blockEditor.useBlockProps,
		SelectControl      = wp.components.SelectControl,
		TextControl        = wp.components.TextControl,
		RangeControl       = wp.components.RangeControl,
		ToggleControl      = wp.components.ToggleControl,
		Disabled           = wp.components.Disabled,
		ServerSideRender   = wp.serverSideRender;

	var postTypes  = ( window.StacklessQueryLoop && StacklessQueryLoop.postTypes ) || [ { label: __( 'Posts', 'stackless-theme' ), value: 'post' } ];
	var taxonomies = ( window.StacklessQueryLoop && StacklessQueryLoop.taxonomies ) || [];

	registerBlockType( 'stackless/query-loop', {
		apiVersion: 3,
		title: __( 'Query Loop', 'stackless-theme' ),
		description: __( 'Display posts using theme loop templates with custom queries.', 'stackless-theme' ),
		icon: 'list-view',
		category: 'stackless',
		attributes: {
			layout:          { type: 'string', default: 'cards' },
			postType:        { type: 'string', default: 'post' },
			postsPerPage:    { type: 'number', default: 6 },
			columns:         { type: 'number', default: 2 },
			orderBy:         { type: 'string', default: 'date' },
			order:           { type: 'string', default: 'DESC' },
			categories:      { type: 'string', default: '' },
			tags:            { type: 'string', default: '' },
			taxonomy:        { type: 'string', default: '' },
			terms:           { type: 'string', default: '' },
			offset:          { type: 'number', default: 0 },
			excludeCurrent:  { type: 'boolean', default: true },
			showExcerpt:     { type: 'boolean', default: true },
			excerptLength:   { type: 'number', default: 0 },
			postIn:          { type: 'string', default: '' },
			postNotIn:       { type: 'string', default: '' },
			author:          { type: 'string', default: '' },
			secondPostType:  { type: 'string', default: '' },
			secondTaxonomy:  { type: 'string', default: '' },
			secondTerms:     { type: 'string', default: '' },
			showImage:       { type: 'boolean', default: true },
			imageSize:       { type: 'string', default: '' },
			titleTag:        { type: 'string', default: 'h2' },
			showByline:      { type: 'boolean', default: true },
			showCategory:    { type: 'boolean', default: true },
			showAuthor:      { type: 'boolean', default: true },
			showDate:        { type: 'boolean', default: true },
			showAvatar:      { type: 'boolean', default: true },
			readMoreText:    { type: 'string', default: '' },
			featuredCount:   { type: 'number', default: 0 },
			noResultsText:   { type: 'string', default: '' },
			classLoop:       { type: 'string', default: '' },
			classItem:       { type: 'string', default: '' },
			classImage:      { type: 'string', default: '' },
			classContent:    { type: 'string', default: '' },
			classTitle:      { type: 'string', default: '' },
			classExcerpt:    { type: 'string', default: '' },
			classByline:     { type: 'string', default: '' },
			classReadMore:   { type: 'string', default: '' }
		},
		edit: function ( props ) {
			var a = props.attributes;
			var set = props.setAttributes;
			var blockProps = useBlockProps();
			var showColumns = a.layout === 'cards' || a.layout === 'teasers';

			return el( Fragment, null,
				el( InspectorControls, null,

					// Layout panel
					el( PanelBody, { title: __( 'Layout', 'stackless-theme' ) },
						el( SelectControl, {
							label: __( 'Template', 'stackless-theme' ),
							options: [
								{ label: __( 'Cards', 'stackless-theme' ), value: 'cards' },
								{ label: __( 'Blocks', 'stackless-theme' ), value: 'blocks' },
								{ label: __( 'List', 'stackless-theme' ), value: 'list' },
								{ label: __( 'Teasers', 'stackless-theme' ), value: 'teasers' },
								{ label: __( 'Docs', 'stackless-theme' ), value: 'docs' },
								{ label: __( 'Default', 'stackless-theme' ), value: 'default' }
							],
							value: a.layout,
							onChange: function( val ) { set({ layout: val }); }
						}),
						showColumns ? el( RangeControl, {
							label: __( 'Columns', 'stackless-theme' ),
							value: a.columns,
							min: 1,
							max: 4,
							onChange: function( val ) { set({ columns: val }); }
						}) : '',
						a.layout === 'teasers' ? el( RangeControl, {
							label: __( 'Featured Count', 'stackless-theme' ),
							help: __( 'Number of items spanning full width', 'stackless-theme' ),
							value: a.featuredCount,
							min: 0,
							max: 5,
							onChange: function( val ) { set({ featuredCount: val }); }
						}) : ''
					),

					// Display panel
					el( PanelBody, { title: __( 'Display', 'stackless-theme' ), initialOpen: false },
						el( SelectControl, {
							label: __( 'Title Tag', 'stackless-theme' ),
							help: __( 'HTML element for the post title. Size stays the same — it is driven by the title class, not the tag.', 'stackless-theme' ),
							options: [
								{ label: __( 'h2 (default)', 'stackless-theme' ), value: 'h2' },
								{ label: __( 'h3', 'stackless-theme' ), value: 'h3' },
								{ label: __( 'h4', 'stackless-theme' ), value: 'h4' },
								{ label: __( 'h5', 'stackless-theme' ), value: 'h5' },
								{ label: __( 'h6', 'stackless-theme' ), value: 'h6' },
								{ label: __( 'p', 'stackless-theme' ), value: 'p' },
								{ label: __( 'div', 'stackless-theme' ), value: 'div' }
							],
							value: a.titleTag,
							onChange: function( val ) { set({ titleTag: val }); }
						}),
						el( ToggleControl, {
							label: __( 'Featured Image', 'stackless-theme' ),
							checked: a.showImage,
							onChange: function( val ) { set({ showImage: val }); }
						}),
						a.showImage ? el( SelectControl, {
							label: __( 'Image Size', 'stackless-theme' ),
							options: [
								{ label: __( 'Default', 'stackless-theme' ), value: '' },
								{ label: __( 'Thumbnail', 'stackless-theme' ), value: 'thumbnail' },
								{ label: __( 'Medium', 'stackless-theme' ), value: 'medium' },
								{ label: __( 'Large', 'stackless-theme' ), value: 'large' },
								{ label: __( 'Square', 'stackless-theme' ), value: 'stb-block' },
								{ label: __( 'Banner', 'stackless-theme' ), value: 'stb-banner' }
							],
							value: a.imageSize,
							onChange: function( val ) { set({ imageSize: val }); }
						}) : '',
						el( ToggleControl, {
							label: __( 'Excerpt', 'stackless-theme' ),
							checked: a.showExcerpt,
							onChange: function( val ) { set({ showExcerpt: val }); }
						}),
						a.showExcerpt ? el( RangeControl, {
							label: __( 'Excerpt Words', 'stackless-theme' ),
							help: __( '0 = template default', 'stackless-theme' ),
							value: a.excerptLength,
							min: 0,
							max: 100,
							onChange: function( val ) { set({ excerptLength: val }); }
						}) : '',
						el( TextControl, {
							label: __( 'Read More Text', 'stackless-theme' ),
							help: __( 'Leave empty to hide', 'stackless-theme' ),
							value: a.readMoreText,
							onChange: function( val ) { set({ readMoreText: val }); }
						}),
						el( ToggleControl, {
							label: __( 'Byline', 'stackless-theme' ),
							checked: a.showByline,
							onChange: function( val ) { set({ showByline: val }); }
						}),
						a.showByline ? el( Fragment, null,
							el( ToggleControl, {
								label: __( 'Category', 'stackless-theme' ),
								checked: a.showCategory,
								onChange: function( val ) { set({ showCategory: val }); }
							}),
							el( ToggleControl, {
								label: __( 'Author', 'stackless-theme' ),
								checked: a.showAuthor,
								onChange: function( val ) { set({ showAuthor: val }); }
							}),
							a.showAuthor ? el( ToggleControl, {
								label: __( 'Author Avatar', 'stackless-theme' ),
								checked: a.showAvatar,
								onChange: function( val ) { set({ showAvatar: val }); }
							}) : '',
							el( ToggleControl, {
								label: __( 'Date', 'stackless-theme' ),
								checked: a.showDate,
								onChange: function( val ) { set({ showDate: val }); }
							})
						) : ''
					),

					// Query panel
					el( PanelBody, { title: __( 'Query', 'stackless-theme' ), initialOpen: false },
						el( SelectControl, {
							label: __( 'Post Type', 'stackless-theme' ),
							options: postTypes,
							value: a.postType,
							onChange: function( val ) { set({ postType: val }); }
						}),
						el( RangeControl, {
							label: __( 'Posts Per Page', 'stackless-theme' ),
							value: a.postsPerPage,
							min: 1,
							max: 50,
							onChange: function( val ) { set({ postsPerPage: val }); }
						}),
						el( SelectControl, {
							label: __( 'Order By', 'stackless-theme' ),
							options: [
								{ label: __( 'Date', 'stackless-theme' ), value: 'date' },
								{ label: __( 'Title', 'stackless-theme' ), value: 'title' },
								{ label: __( 'Modified', 'stackless-theme' ), value: 'modified' },
								{ label: __( 'Random', 'stackless-theme' ), value: 'rand' },
								{ label: __( 'Comment Count', 'stackless-theme' ), value: 'comment_count' },
								{ label: __( 'Menu Order', 'stackless-theme' ), value: 'menu_order' }
							],
							value: a.orderBy,
							onChange: function( val ) { set({ orderBy: val }); }
						}),
						el( SelectControl, {
							label: __( 'Order', 'stackless-theme' ),
							options: [
								{ label: __( 'Newest First', 'stackless-theme' ), value: 'DESC' },
								{ label: __( 'Oldest First', 'stackless-theme' ), value: 'ASC' }
							],
							value: a.order,
							onChange: function( val ) { set({ order: val }); }
						}),
						el( TextControl, {
							label: __( 'Offset', 'stackless-theme' ),
							type: 'number',
							value: String( a.offset ),
							onChange: function( val ) { set({ offset: parseInt( val ) || 0 }); }
						}),
						el( ToggleControl, {
							label: __( 'Exclude Current Post', 'stackless-theme' ),
							checked: a.excludeCurrent,
							onChange: function( val ) { set({ excludeCurrent: val }); }
						}),
						el( TextControl, {
							label: __( 'No Results Text', 'stackless-theme' ),
							help: __( 'Custom message when no posts found', 'stackless-theme' ),
							value: a.noResultsText,
							onChange: function( val ) { set({ noResultsText: val }); }
						})
					),

					// Filters panel
					el( PanelBody, { title: __( 'Filters', 'stackless-theme' ), initialOpen: false },
						el( TextControl, {
							label: __( 'Categories', 'stackless-theme' ),
							help: __( 'Comma-separated IDs or slugs', 'stackless-theme' ),
							value: a.categories,
							onChange: function( val ) { set({ categories: val }); }
						}),
						el( TextControl, {
							label: __( 'Tags', 'stackless-theme' ),
							help: __( 'Comma-separated IDs or slugs', 'stackless-theme' ),
							value: a.tags,
							onChange: function( val ) { set({ tags: val }); }
						}),
						el( SelectControl, {
							label: __( 'Taxonomy', 'stackless-theme' ),
							options: [ { label: __( 'None', 'stackless-theme' ), value: '' } ].concat( taxonomies ),
							value: a.taxonomy,
							onChange: function( val ) { set({ taxonomy: val }); }
						}),
						a.taxonomy ? el( TextControl, {
							label: __( 'Terms', 'stackless-theme' ),
							help: __( 'Comma-separated IDs or slugs for ', 'stackless-theme' ) + a.taxonomy,
							value: a.terms,
							onChange: function( val ) { set({ terms: val }); }
						}) : '',
						el( TextControl, {
							label: __( 'Author', 'stackless-theme' ),
							help: __( 'ID or username', 'stackless-theme' ),
							value: a.author,
							onChange: function( val ) { set({ author: val }); }
						}),
						el( TextControl, {
							label: __( 'Include Posts', 'stackless-theme' ),
							help: __( 'Comma-separated IDs or slugs. Overrides order.', 'stackless-theme' ),
							value: a.postIn,
							onChange: function( val ) { set({ postIn: val }); }
						}),
						el( TextControl, {
							label: __( 'Exclude Posts', 'stackless-theme' ),
							help: __( 'Comma-separated IDs or slugs', 'stackless-theme' ),
							value: a.postNotIn,
							onChange: function( val ) { set({ postNotIn: val }); }
						})
					),

					// CSS Classes panel
					el( PanelBody, { title: __( 'CSS Classes', 'stackless-theme' ), initialOpen: false },
						el( TextControl, {
							label: __( 'Loop Wrapper', 'stackless-theme' ),
							help: __( 'Classes for the outer loop container', 'stackless-theme' ),
							value: a.classLoop,
							onChange: function( val ) { set({ classLoop: val }); }
						}),
						el( TextControl, {
							label: __( 'Item', 'stackless-theme' ),
							help: __( 'Classes for each post article', 'stackless-theme' ),
							value: a.classItem,
							onChange: function( val ) { set({ classItem: val }); }
						}),
						el( TextControl, {
							label: __( 'Image', 'stackless-theme' ),
							help: __( 'Classes for the featured image container', 'stackless-theme' ),
							value: a.classImage,
							onChange: function( val ) { set({ classImage: val }); }
						}),
						el( TextControl, {
							label: __( 'Content', 'stackless-theme' ),
							help: __( 'Classes for the content wrapper', 'stackless-theme' ),
							value: a.classContent,
							onChange: function( val ) { set({ classContent: val }); }
						}),
						el( TextControl, {
							label: __( 'Title', 'stackless-theme' ),
							help: __( 'Classes for the post title', 'stackless-theme' ),
							value: a.classTitle,
							onChange: function( val ) { set({ classTitle: val }); }
						}),
						el( TextControl, {
							label: __( 'Excerpt', 'stackless-theme' ),
							help: __( 'Classes for the excerpt text', 'stackless-theme' ),
							value: a.classExcerpt,
							onChange: function( val ) { set({ classExcerpt: val }); }
						}),
						el( TextControl, {
							label: __( 'Byline', 'stackless-theme' ),
							help: __( 'Classes for the byline container', 'stackless-theme' ),
							value: a.classByline,
							onChange: function( val ) { set({ classByline: val }); }
						}),
						el( TextControl, {
							label: __( 'Read More', 'stackless-theme' ),
							help: __( 'Classes for the read more link wrapper', 'stackless-theme' ),
							value: a.classReadMore,
							onChange: function( val ) { set({ classReadMore: val }); }
						})
					),

					// Combine Queries panel
					el( PanelBody, { title: __( 'Combine Queries', 'stackless-theme' ), initialOpen: false },
						el( TextControl, {
							label: __( 'Additional Post Types', 'stackless-theme' ),
							help: __( 'Comma-separated post type slugs', 'stackless-theme' ),
							value: a.secondPostType,
							onChange: function( val ) { set({ secondPostType: val }); }
						}),
						el( SelectControl, {
							label: __( 'Additional Taxonomy', 'stackless-theme' ),
							options: [ { label: __( 'None', 'stackless-theme' ), value: '' } ].concat( taxonomies ),
							value: a.secondTaxonomy,
							onChange: function( val ) { set({ secondTaxonomy: val }); }
						}),
						a.secondTaxonomy ? el( TextControl, {
							label: __( 'Terms', 'stackless-theme' ),
							help: __( 'Comma-separated IDs or slugs (OR relation with primary)', 'stackless-theme' ),
							value: a.secondTerms,
							onChange: function( val ) { set({ secondTerms: val }); }
						}) : ''
					)
				),
				el( 'div', blockProps,
					el( Disabled, null,
						el( ServerSideRender, {
							block: 'stackless/query-loop',
							attributes: a
						})
					)
				)
			);
		},
		save: function () { return null; }
	});
})( wp );
