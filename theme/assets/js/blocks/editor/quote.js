/**
 * Stackless block editor script: Quote.
 *
 * Block names, attributes and save output are a storage contract: content
 * saved with them has to keep parsing. Do not rename, add or drop attributes
 * here.
 */
(function (wp) {
	var registerBlockType = wp.blocks.registerBlockType,
		el = wp.element.createElement,
		Fragment = wp.element.Fragment,
		RichText = wp.blockEditor.RichText,
		InspectorControls = wp.blockEditor.InspectorControls,
		MediaUpload = wp.blockEditor.MediaUpload,
		useBlockProps = wp.blockEditor.useBlockProps,
		PanelColorSettings = wp.blockEditor.PanelColorSettings,
		PanelBody = wp.components.PanelBody,
		SelectControl = wp.components.SelectControl,
		RangeControl = wp.components.RangeControl,
		TextControl = wp.components.TextControl,
		CheckboxControl = wp.components.CheckboxControl,
		Button = wp.components.Button,
		__ = wp.i18n.__;

	/**
	 * Render star rating in editor.
	 */
	function renderStars(rating) {
		var stars = [];
		for (var i = 1; i <= 5; i++) {
			stars.push(
				el('span', {
					key: i,
					style: {
						color: i <= rating ? '#d4a017' : '#ccc',
						fontSize: '1rem',
						letterSpacing: '0.1em'
					}
				}, '\u2605')
			);
		}
		return el('div', { className: 'stb-quote-stars' }, stars);
	}

	/**
	 * Style definitions for editor preview.
	 */
	var styleConfigs = {
		// Blockquote styles
		'vertical-rule': {
			wrapper: function (a) {
				return { borderLeft: '3px solid ' + (a.borderColor || 'var(--color-primary)'), paddingLeft: '1.75em' };
			},
			hasAvatar: false, hasStars: false, hasMark: false
		},
		'oversize-mark': {
			wrapper: function () { return { position: 'relative', paddingLeft: '3.5em' }; },
			hasAvatar: false, hasStars: false, hasMark: true
		},
		'editorial-band': {
			wrapper: function () {
				return { borderTop: '1px solid var(--color-border)', borderBottom: '1px solid var(--color-border)', padding: '2em 0', textAlign: 'center' };
			},
			hasAvatar: false, hasStars: false, hasMark: false
		},
		'tinted-surface': {
			wrapper: function (a) {
				return { background: a.bgColor || 'color-mix(in srgb, var(--color-primary) 8%, var(--color-content-bg))', borderRadius: '12px', padding: '2rem 2.25rem' };
			},
			hasAvatar: false, hasStars: false, hasMark: false
		},
		'boxed-accent': {
			wrapper: function (a) {
				return { border: '1px solid ' + (a.borderColor || 'var(--color-border)'), borderRadius: '12px', padding: '2rem 2.25rem', borderLeft: '4px solid ' + (a.borderColor || 'var(--color-primary)') };
			},
			hasAvatar: false, hasStars: false, hasMark: false
		},
		'inline-dash': {
			wrapper: function () { return {}; },
			hasAvatar: false, hasStars: false, hasMark: false
		},
		// Success
		'success-soft': {
			wrapper: function () {
				return { background: 'color-mix(in srgb, var(--color-tertiary, #22c55e) 10%, var(--color-content-bg, #fff))', border: '1px solid color-mix(in srgb, var(--color-tertiary, #22c55e) 25%, var(--color-border, #e5e5e5))', borderRadius: '12px', padding: '1.5rem 1.75rem' };
			},
			hasAvatar: false, hasStars: false, hasMark: false
		},
		'success-border': {
			wrapper: function () {
				return { border: '1px solid var(--color-tertiary, #22c55e)', borderLeftWidth: '4px', borderRadius: '0 8px 8px 0', padding: '1.5rem 1.75rem' };
			},
			hasAvatar: false, hasStars: false, hasMark: false
		},
		'success-dark': {
			wrapper: function () {
				return { background: 'color-mix(in srgb, var(--color-tertiary, #22c55e) 85%, var(--color-headline, #1a1a1a))', borderRadius: '12px', padding: '1.5rem 1.75rem', color: '#f0f0f0' };
			},
			hasAvatar: false, hasStars: false, hasMark: false
		},
		// Lead/Intro
		'serif-lead': {
			wrapper: function () { return {}; },
			hasAvatar: false, hasStars: false, hasMark: false
		},
		'sans-secondary': {
			wrapper: function () { return {}; },
			hasAvatar: false, hasStars: false, hasMark: false
		},
		'editorial-italic': {
			wrapper: function () { return { borderBottom: '1px solid var(--color-border)', paddingBottom: '1.5em' }; },
			hasAvatar: false, hasStars: false, hasMark: false
		},
		// Testimonials
		'classic-centered': {
			wrapper: function () { return { textAlign: 'center' }; },
			hasAvatar: true, hasStars: false, hasMark: false
		},
		'card-stars': {
			wrapper: function (a) {
				return { background: a.bgColor || '#fff', border: '1px solid ' + (a.borderColor || '#e5e5e5'), borderRadius: '16px', padding: '2rem' };
			},
			hasAvatar: true, hasStars: true, hasMark: false
		},
		'dark-statement': {
			wrapper: function (a) {
				return { background: a.bgColor || '#1a1a2e', borderRadius: '16px', padding: '3rem', color: '#f0f0f0' };
			},
			hasAvatar: true, hasStars: false, hasMark: false
		},
		'asymmetric-mark': {
			wrapper: function () {
				return { display: 'grid', gridTemplateColumns: '3rem 1fr', gap: '1.5rem' };
			},
			hasAvatar: true, hasStars: false, hasMark: true
		}
	};

	registerBlockType('stackless/quote', {
		apiVersion: 3,
		title: __( 'Quote', 'stackless-theme' ),
		description: __( 'Versatile quote block with blockquote styles, testimonials, callout quotes, and more.', 'stackless-theme' ),
		icon: 'format-quote',
		category: 'stackless',
		deprecated: [
			{
				// Saved blocks validate (save is null), so without this the editor
				// never runs the migration and the old string `style` is dropped.
				isEligible: function (attributes) { return typeof attributes.style === 'string'; },
				attributes: {
					quote: { type: 'string' },
					citation: { type: 'string' },
					role: { type: 'string' },
					avatarUrl: { type: 'string' },
					avatarId: { type: 'number' },
					rating: { type: 'number', default: 0 },
					style: { type: 'string', default: 'classic-centered' },
					fontFamily: { type: 'string', default: 'sans' },
					quoteColor: { type: 'string' },
					citationColor: { type: 'string' },
					bgColor: { type: 'string' },
					borderColor: { type: 'string' },
					fontSize: { type: 'number', default: 125 },
					marginBottom: { type: 'string', default: 'mb-double' },
					shadow: { type: 'boolean', default: false }
				},
				save: function () { return null; },
				migrate: function (attributes) {
					var newAttrs = Object.assign({}, attributes, { quoteStyle: attributes.style || 'classic-centered' });
					delete newAttrs.style;
					return newAttrs;
				}
			}
		],
		attributes: {
			quote: { type: 'string' },
			citation: { type: 'string' },
			role: { type: 'string' },
			itemReviewed: { type: 'string' },
			itemType: { type: 'string', default: '' },
			avatarUrl: { type: 'string' },
			avatarId: { type: 'number' },
			rating: { type: 'number', default: 0 },
			quoteStyle: { type: 'string', default: 'classic-centered' },
			fontFamily: { type: 'string', default: 'sans' },
			quoteColor: { type: 'string' },
			citationColor: { type: 'string' },
			bgColor: { type: 'string' },
			borderColor: { type: 'string' },
			fontSize: { type: 'number', default: 125 },
			marginBottom: { type: 'string', default: 'mb-double' },
			shadow: { type: 'boolean', default: false }
		},
		// "Transform to" from the core Quote: paragraphs become the quote text.
		transforms: {
			from: [ {
				type: 'block',
				blocks: [ 'core/quote' ],
				transform: function ( attributes, innerBlocks ) {
					var text = ( innerBlocks || [] ).map( function ( b ) { return b.attributes && b.attributes.content ? String( b.attributes.content ) : ''; } ).filter( Boolean ).join( '<br>' );
					return wp.blocks.createBlock( 'stackless/quote', { quote: text || String( attributes.value || '' ), citation: attributes.citation ? String( attributes.citation ) : '' } );
				}
			} ]
		},
		edit: function (props) {
			var attributes = props.attributes;
			var style = attributes.quoteStyle;
			var config = styleConfigs[style] || styleConfigs['vertical-rule'];
			var isDark = style === 'dark-statement' || style === 'success-dark';

			var fontFam = attributes.fontFamily === 'serif' ? 'var(--font-serif)' : 'var(--font-base)';
			var wrapperStyle = config.wrapper(attributes);
			var textStyle = {
				fontFamily: fontFam,
				fontSize: (attributes.fontSize / 100) + 'em',
				fontStyle: 'normal',
				color: isDark ? '#f0f0f0' : (attributes.quoteColor || '')
			};
			var citeStyle = {
				fontStyle: 'normal',
				color: isDark ? 'rgba(255,255,255,0.6)' : (attributes.citationColor || '')
			};

			// Force serif for editorial-italic
			if (style === 'editorial-italic') {
				textStyle.fontStyle = 'italic';
				textStyle.fontFamily = 'var(--font-serif)';
			}

			var blockProps = useBlockProps({
				className: 'stb-quote stb-quote-' + style + ' stb-quote-font-' + attributes.fontFamily + ' ' + attributes.marginBottom + (attributes.shadow ? ' has-shadow' : ''),
				style: wrapperStyle
			});

			// Avatar element
			var avatarEl = attributes.avatarUrl && config.hasAvatar
				? el('img', {
					className: 'stb-quote-avatar',
					src: attributes.avatarUrl,
					alt: '',
					style: {
						width: style === 'classic-centered' ? '56px' : '40px',
						height: style === 'classic-centered' ? '56px' : '40px',
						borderRadius: style === 'asymmetric-mark' ? '8px' : '50%',
						objectFit: 'cover',
						flexShrink: 0
					}
				})
				: null;

			// Build meta (name + role)
			var metaEl = el('div', { className: 'stb-quote-meta', style: { display: 'flex', alignItems: 'center', gap: '0.75rem', marginTop: '1.25rem' } },
				config.hasAvatar && avatarEl && style !== 'classic-centered' ? avatarEl : null,
				el('div', null,
					el(RichText, {
						tagName: 'cite',
						className: 'stb-quote-name',
						style: citeStyle,
						value: attributes.citation,
						placeholder: __( 'Name / Source', 'stackless-theme' ),
						onChange: function (val) { props.setAttributes({ citation: val }); }
					}),
					el(RichText, {
						tagName: 'span',
						className: 'stb-quote-role',
						value: attributes.role,
						placeholder: __( 'Role / Company (optional)', 'stackless-theme' ),
						onChange: function (val) { props.setAttributes({ role: val }); }
					})
				)
			);

			// Content
			var content;

			if (style === 'asymmetric-mark') {
				content = el(Fragment, null,
					el('span', { className: 'stb-quote-mark', style: { fontSize: '4.5rem', lineHeight: '0.85', opacity: 0.25, paddingTop: '0.25rem' } }, '\u201C'),
					el('div', null,
						el(RichText, {
							tagName: 'blockquote',
							className: 'stb-quote-text',
							style: textStyle,
							value: attributes.quote,
							placeholder: __( 'Write your quote...', 'stackless-theme' ),
							onChange: function (val) { props.setAttributes({ quote: val }); }
						}),
						metaEl
					)
				);
			} else if (style === 'classic-centered') {
				content = el(Fragment, null,
					avatarEl && el('div', { style: { display: 'flex', justifyContent: 'center', marginBottom: '1.5rem' } }, avatarEl),
					el(RichText, {
						tagName: 'blockquote',
						className: 'stb-quote-text',
						style: textStyle,
						value: attributes.quote,
						placeholder: __( 'Write your quote...', 'stackless-theme' ),
						onChange: function (val) { props.setAttributes({ quote: val }); }
					}),
					metaEl
				);
			} else if (style === 'card-stars') {
				content = el(Fragment, null,
					attributes.rating > 0 && renderStars(attributes.rating),
					el(RichText, {
						tagName: 'blockquote',
						className: 'stb-quote-text',
						style: textStyle,
						value: attributes.quote,
						placeholder: __( 'Write your quote...', 'stackless-theme' ),
						onChange: function (val) { props.setAttributes({ quote: val }); }
					}),
					el('div', { style: { borderTop: '1px solid #eee', paddingTop: '1.25rem', marginTop: '1.5rem' } }, metaEl)
				);
			} else if (style === 'oversize-mark') {
				content = el(Fragment, null,
					el('span', { style: { position: 'absolute', left: 0, top: '-0.15em', fontSize: '5em', lineHeight: 1, opacity: 0.2, fontFamily: 'var(--font-serif)' } }, '\u201C'),
					el(RichText, {
						tagName: 'blockquote',
						className: 'stb-quote-text',
						style: textStyle,
						value: attributes.quote,
						placeholder: __( 'Write your quote...', 'stackless-theme' ),
						onChange: function (val) { props.setAttributes({ quote: val }); }
					}),
					metaEl
				);
			} else {
				// All other styles: simple blockquote + meta
				content = el(Fragment, null,
					el(RichText, {
						tagName: 'blockquote',
						className: 'stb-quote-text',
						style: textStyle,
						value: attributes.quote,
						placeholder: __( 'Write your quote...', 'stackless-theme' ),
						onChange: function (val) { props.setAttributes({ quote: val }); }
					}),
					metaEl
				);
			}

			return el(Fragment, null,
				el(InspectorControls, { key: 'inspector' },
					el(PanelBody, { title: __( 'Style', 'stackless-theme' ), initialOpen: true },
						el(SelectControl, {
							label: __( 'Quote Style', 'stackless-theme' ),
							options: [
								{ label: __( '— Blockquote —', 'stackless-theme' ), value: '', disabled: true },
								{ label: __( 'Vertical Rule', 'stackless-theme' ), value: 'vertical-rule' },
								{ label: __( 'Quote Mark', 'stackless-theme' ), value: 'oversize-mark' },
								{ label: __( 'Editorial Band', 'stackless-theme' ), value: 'editorial-band' },
								{ label: __( 'Tinted Surface', 'stackless-theme' ), value: 'tinted-surface' },
								{ label: __( 'Boxed Accent', 'stackless-theme' ), value: 'boxed-accent' },
								{ label: __( 'Inline Dash', 'stackless-theme' ), value: 'inline-dash' },
								{ label: __( '— Success/Highlight —', 'stackless-theme' ), value: '', disabled: true },
								{ label: __( 'Success', 'stackless-theme' ), value: 'success-soft' },
								{ label: __( 'Success Border', 'stackless-theme' ), value: 'success-border' },
								{ label: __( 'Success Dark', 'stackless-theme' ), value: 'success-dark' },
								{ label: __( '— Lead/Intro —', 'stackless-theme' ), value: '', disabled: true },
								{ label: __( 'Serif Lead', 'stackless-theme' ), value: 'serif-lead' },
								{ label: __( 'Sans Lead', 'stackless-theme' ), value: 'sans-secondary' },
								{ label: __( 'Editorial Italic', 'stackless-theme' ), value: 'editorial-italic' },
								{ label: __( '— Testimonial —', 'stackless-theme' ), value: '', disabled: true },
								{ label: __( 'Classic Centered', 'stackless-theme' ), value: 'classic-centered' },
								{ label: __( 'Card with Stars', 'stackless-theme' ), value: 'card-stars' },
								{ label: __( 'Dark Statement', 'stackless-theme' ), value: 'dark-statement' },
								{ label: __( 'Asymmetric Mark', 'stackless-theme' ), value: 'asymmetric-mark' }
							],
							value: style,
							onChange: function (val) { if (val) props.setAttributes({ quoteStyle: val }); }
						}),
						el(SelectControl, {
							label: __( 'Quote Font', 'stackless-theme' ),
							options: [
								{ label: __( 'Sans-serif (default)', 'stackless-theme' ), value: 'sans' },
								{ label: __( 'Serif', 'stackless-theme' ), value: 'serif' }
							],
							value: attributes.fontFamily,
							onChange: function (val) { props.setAttributes({ fontFamily: val }); }
						}),
						el(RangeControl, {
							label: __( 'Font Size (%)', 'stackless-theme' ),
							value: attributes.fontSize,
							onChange: function (val) { props.setAttributes({ fontSize: val }); },
							min: 80,
							max: 200,
							step: 5
						}),
						config.hasStars && el(RangeControl, {
							label: __( 'Star Rating', 'stackless-theme' ),
							value: attributes.rating,
							onChange: function (val) { props.setAttributes({ rating: val }); },
							min: 0,
							max: 5,
							step: 1
						})
					),
					config.hasAvatar && el(PanelBody, { title: __( 'Avatar', 'stackless-theme' ), initialOpen: false },
						el(MediaUpload, {
							onSelect: function (media) {
								props.setAttributes({ avatarUrl: media.url, avatarId: media.id });
							},
							allowedTypes: ['image'],
							value: attributes.avatarId,
							render: function (obj) {
								return el(Fragment, null,
									attributes.avatarUrl
										? el(Fragment, null,
											el('img', {
												src: attributes.avatarUrl,
												style: { width: '60px', height: '60px', borderRadius: '50%', objectFit: 'cover', marginBottom: '8px', display: 'block' }
											}),
											el(Button, { onClick: obj.open, variant: 'secondary', isSmall: true, style: { marginRight: '8px' } }, __( 'Replace', 'stackless-theme' )),
											el(Button, { onClick: function () { props.setAttributes({ avatarUrl: '', avatarId: undefined }); }, variant: 'tertiary', isSmall: true, isDestructive: true }, __( 'Remove', 'stackless-theme' ))
										)
										: el(Button, { onClick: obj.open, variant: 'secondary' }, __( 'Upload Avatar', 'stackless-theme' ))
								);
							}
						})
					),
					el(PanelColorSettings, {
						title: __( 'Colors', 'stackless-theme' ),
						initialOpen: false,
						colorSettings: [
							{ value: attributes.quoteColor, label: __( 'Quote Text', 'stackless-theme' ), onChange: function (val) { props.setAttributes({ quoteColor: val }); } },
							{ value: attributes.citationColor, label: __( 'Citation', 'stackless-theme' ), onChange: function (val) { props.setAttributes({ citationColor: val }); } },
							{ value: attributes.bgColor, label: __( 'Background', 'stackless-theme' ), onChange: function (val) { props.setAttributes({ bgColor: val }); } },
							{ value: attributes.borderColor, label: __( 'Border/Accent', 'stackless-theme' ), onChange: function (val) { props.setAttributes({ borderColor: val }); } }
						]
					}),
					el(PanelBody, { title: __( 'Schema (testimonial only)', 'stackless-theme' ), initialOpen: false },
						el(SelectControl, { label: __( 'Reviewed item type', 'stackless-theme' ), value: attributes.itemType || '', options: [{label:__( 'Ordinary testimonial / unspecified', 'stackless-theme' ),value:''},{label:__( 'Product', 'stackless-theme' ),value:'Product'},{label:__( 'Book', 'stackless-theme' ),value:'Book'},{label:__( 'Course', 'stackless-theme' ),value:'Course'}], onChange: function(v) { props.setAttributes({itemType:v}); } }),
						el(TextControl, {
							label: __( 'Item reviewed (name)', 'stackless-theme' ),
							help: __( 'Required for Review rich results. Provide the product, service, or thing this testimonial is about. If empty, the block emits Quotation schema instead of Review.', 'stackless-theme' ),
							value: attributes.itemReviewed || '',
							onChange: function (val) { props.setAttributes({ itemReviewed: val }); }
						})
					),
					el(PanelBody, { title: __( 'Layout', 'stackless-theme' ), initialOpen: false },
						el(SelectControl, {
							label: __( 'Margin Bottom', 'stackless-theme' ),
							options: [
								{ label: __( 'None', 'stackless-theme' ), value: 'mb-none' },
								{ label: __( 'Half', 'stackless-theme' ), value: 'mb-half' },
								{ label: __( 'Single', 'stackless-theme' ), value: 'mb-single' },
								{ label: __( 'Double (default)', 'stackless-theme' ), value: 'mb-double' },
								{ label: __( 'Triple', 'stackless-theme' ), value: 'mb-triple' }
							],
							value: attributes.marginBottom,
							onChange: function (val) { props.setAttributes({ marginBottom: val }); }
						}),
						el(CheckboxControl, {
							label: __( 'Add shadow', 'stackless-theme' ),
							checked: attributes.shadow,
							onChange: function (val) { props.setAttributes({ shadow: val }); }
						})
					)
				),
				el('figure', blockProps, content)
			);
		},
		save: function () {
			return null;
		}
	});
})(window.wp);
