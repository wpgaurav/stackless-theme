/**
 * Stackless block editor script: Theme Card.
 *
 * Block names, attributes and save output are a storage contract: content
 * saved with them has to keep parsing. Do not rename, add or drop attributes
 * here.
 */
(function (wp) {
	var registerBlockType = wp.blocks.registerBlockType,
		el = wp.element.createElement,
		Fragment = wp.element.Fragment,
		useState = wp.element.useState,
		useBlockProps = wp.blockEditor.useBlockProps,
		InspectorControls = wp.blockEditor.InspectorControls,
		MediaUpload = wp.blockEditor.MediaUpload,
		PanelBody = wp.components.PanelBody,
		SelectControl = wp.components.SelectControl,
		TextControl = wp.components.TextControl,
		TextareaControl = wp.components.TextareaControl,
		RangeControl = wp.components.RangeControl,
		Button = wp.components.Button,
		Spinner = wp.components.Spinner,
		Notice = wp.components.Notice,
		ToggleControl = wp.components.ToggleControl,
		__ = wp.i18n.__;

	// Format number with commas
	function formatNumber(num) {
		if (!num) return '';
		return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ',');
	}

	// Decode HTML entities
	function decodeHtmlEntities(str) {
		if (!str) return '';
		var txt = document.createElement('textarea');
		txt.innerHTML = str;
		return txt.value;
	}

	// Import image to media library via AJAX
	function importCardImage(type, slug, imageType, imageUrl) {
		if (!imageUrl || !window.StacklessCardImages) {
			return Promise.resolve(imageUrl);
		}

		var formData = new FormData();
		formData.append('action', 'stackless_import_card_image');
		formData.append('nonce', StacklessCardImages.nonce);
		formData.append('type', type);
		formData.append('slug', slug);
		formData.append('image_type', imageType);
		formData.append('image_url', imageUrl);

		return fetch(StacklessCardImages.ajaxUrl, {
			method: 'POST',
			body: formData,
			credentials: 'same-origin'
		})
		.then(function(response) { return response.json(); })
		.then(function(result) {
			if (result.success && result.data.url) {
				return result.data.url;
			}
			return imageUrl;
		})
		.catch(function() {
			return imageUrl;
		});
	}

	// Check if image exists in media library
	function checkCardImage(type, slug, imageType) {
		if (!window.StacklessCardImages) {
			return Promise.resolve({ exists: false });
		}

		var formData = new FormData();
		formData.append('action', 'stackless_check_card_image');
		formData.append('nonce', StacklessCardImages.nonce);
		formData.append('type', type);
		formData.append('slug', slug);
		formData.append('image_type', imageType);

		return fetch(StacklessCardImages.ajaxUrl, {
			method: 'POST',
			body: formData,
			credentials: 'same-origin'
		})
		.then(function(response) { return response.json(); })
		.then(function(result) {
			if (result.success) {
				return result.data;
			}
			return { exists: false };
		})
		.catch(function() {
			return { exists: false };
		});
	}

	// Fetch theme data from WordPress.org API
	function fetchThemeData(slug, setAttributes, setLoading, setError) {
		if (!slug) {
			setError(__( 'Please enter a theme slug', 'stackless-theme' ));
			return;
		}

		setLoading(true);
		setError(null);

		var trimmedSlug = slug.trim();
		var apiUrl = 'https://api.wordpress.org/themes/info/1.2/?action=theme_information&slug=' + encodeURIComponent(trimmedSlug);

		fetch(apiUrl)
			.then(function(response) { return response.json(); })
			.then(function(data) {
				if (data.error) {
					setLoading(false);
					setError(data.error);
					return;
				}

				if (!data.name) {
					setLoading(false);
					setError(__( 'Theme not found', 'stackless-theme' ));
					return;
				}

				// Convert rating from 0-100 to 0-5
				var rating = data.rating ? Math.round((data.rating / 20) * 2) / 2 : 0;

				// Get author name (strip HTML if present)
				var author = '';
				if (data.author) {
					if (typeof data.author === 'object' && data.author.display_name) {
						author = data.author.display_name;
					} else if (typeof data.author === 'string') {
						author = data.author.replace(/<[^>]*>/g, '');
					}
				}

				// Get description (strip HTML and limit length)
				var description = '';
				if (data.sections && data.sections.description) {
					description = data.sections.description.replace(/<[^>]*>/g, '').substring(0, 200) + '...';
				}

				// Decode HTML entities
				var decodedName = decodeHtmlEntities(data.name);
				var decodedDesc = decodeHtmlEntities(description);
				var decodedAuthor = decodeHtmlEntities(author);

				var screenshotUrl = data.screenshot_url || '';
				var themeSlug = data.slug;

				// Check for existing screenshot in media library, then import if needed
				var screenshotPromise = screenshotUrl
					? checkCardImage('theme', themeSlug, 'screenshot').then(function(result) {
						if (result.exists) return result.url;
						return importCardImage('theme', themeSlug, 'screenshot', screenshotUrl);
					})
					: Promise.resolve('');

				screenshotPromise.then(function(finalScreenshot) {
					setLoading(false);
					setAttributes({
						name: decodedName,
						slug: themeSlug,
						description: decodedDesc,
						screenshot: finalScreenshot || screenshotUrl,
						rating: rating,
						ratingCount: data.num_ratings || 0,
						activeInstalls: formatNumber(data.active_installs),
						version: data.version || '',
						author: decodedAuthor,
						requiresWP: data.requires || '',
						requiresPHP: data.requires_php || '',
						previewUrl: data.preview_url || 'https://wp-themes.com/' + themeSlug + '/',
						wpUrl: 'https://wordpress.org/themes/' + themeSlug + '/'
					});
				}).catch(function() {
					setLoading(false);
					setAttributes({
						name: decodedName,
						slug: themeSlug,
						description: decodedDesc,
						screenshot: screenshotUrl,
						rating: rating,
						ratingCount: data.num_ratings || 0,
						activeInstalls: formatNumber(data.active_installs),
						version: data.version || '',
						author: decodedAuthor,
						requiresWP: data.requires || '',
						requiresPHP: data.requires_php || '',
						previewUrl: data.preview_url || 'https://wp-themes.com/' + themeSlug + '/',
						wpUrl: 'https://wordpress.org/themes/' + themeSlug + '/'
					});
				});
			})
			.catch(function(err) {
				setLoading(false);
				setError(__( 'Failed to fetch theme data: ', 'stackless-theme' ) + err.message);
			});
	}

	registerBlockType('stackless/theme-card', {
		apiVersion: 3,
		title: __( 'Theme Card', 'stackless-theme' ),
		description: __( 'Display WordPress theme information. Fetch data automatically from WordPress.org.', 'stackless-theme' ),
		icon: 'admin-appearance',
		category: 'stackless',
		deprecated: [
			{
				// Saved blocks validate (save is null), so without this the editor
				// never runs the migration and the old string `style` is dropped.
				isEligible: function (attributes) { return typeof attributes.style === 'string'; },
				attributes: {
					name: { type: 'string' },
					slug: { type: 'string' },
					description: { type: 'string' },
					screenshot: { type: 'string' },
					rating: { type: 'number', default: 0 },
					ratingCount: { type: 'number', default: 0 },
					activeInstalls: { type: 'string' },
					version: { type: 'string' },
					author: { type: 'string' },
					requiresWP: { type: 'string' },
					requiresPHP: { type: 'string' },
					previewUrl: { type: 'string' },
					wpUrl: { type: 'string' },
					style: { type: 'string', default: 'card' },
					showRequirements: { type: 'boolean', default: false },
					marginBottom: { type: 'string', default: 'mb-double' }
				},
				save: function () { return null; },
				migrate: function (attributes) {
					var newAttrs = Object.assign({}, attributes, { cardStyle: attributes.style || 'card' });
					delete newAttrs.style;
					return newAttrs;
				}
			}
		],
		attributes: {
			schemaEnabled: { type: 'boolean', default: true },
			price: { type: 'string', default: '' },
			priceCurrency: { type: 'string', default: 'USD' },
			authorType: { type: 'string', default: '' },
			name: { type: 'string' },
			slug: { type: 'string' },
			description: { type: 'string' },
			screenshot: { type: 'string' },
			rating: { type: 'number', default: 0 },
			ratingCount: { type: 'number', default: 0 },
			activeInstalls: { type: 'string' },
			version: { type: 'string' },
			author: { type: 'string' },
			requiresWP: { type: 'string' },
			requiresPHP: { type: 'string' },
			previewUrl: { type: 'string' },
			wpUrl: { type: 'string' },
			cardStyle: { type: 'string', default: 'card' },
			showRequirements: { type: 'boolean', default: false },
			marginBottom: { type: 'string', default: 'mb-double' }
		},
		edit: function (props) {
			var attributes = props.attributes;
			var _loadingState = useState(false),
				isLoading = _loadingState[0],
				setLoading = _loadingState[1];
			var _errorState = useState(null),
				error = _errorState[0],
				setError = _errorState[1];

			var renderStars = function() {
				var stars = [];
				// 0-5 scale. A value saved on WordPress.org's 0-100 scale is converted,
				// as the front end does, so it never prints one star per point.
				var rating = attributes.rating > 5 ? Math.round((attributes.rating / 20) * 2) / 2 : attributes.rating;
				rating = Math.max(0, Math.min(5, rating || 0));
				var fullStars = Math.floor(rating);
				var hasHalf = (rating - fullStars) >= 0.5;

				for (var i = 0; i < fullStars; i++) {
					stars.push(StacklessBlocks.icon( 'span', 'stb-icon-star-full', 'text-warning', { key: 'star-' + i } ));
				}
				if (hasHalf) {
					stars.push(StacklessBlocks.icon( 'span', 'stb-icon-star-half', 'text-warning', { key: 'star-half' } ));
				}
				return stars;
			};

			var blockProps = useBlockProps({
				className: 'stb-theme-card stb-theme-card-' + attributes.cardStyle + ' ' + attributes.marginBottom
			});

			return el(Fragment, null,
				el(InspectorControls, { key: 'inspector' },
					el(PanelBody, { title: __( 'Structured data', 'stackless-theme' ), initialOpen: false },
						el(ToggleControl, { label: __( 'Include eligible software schema', 'stackless-theme' ), checked: attributes.schemaEnabled, onChange: function(v) { props.setAttributes({schemaEnabled:v}); }, help: __( 'Requires a real rating count and offer. WordPress.org directory listings describe their free version.', 'stackless-theme' ) }),
						el(TextControl, { label: __( 'Price (leave empty for WordPress.org free listing)', 'stackless-theme' ), value: attributes.price, onChange: function(v) { props.setAttributes({price:v}); } }),
						el(TextControl, { label: __( 'Currency', 'stackless-theme' ), value: attributes.priceCurrency, onChange: function(v) { props.setAttributes({priceCurrency:v}); } }),
						el(SelectControl, { label: __( 'Author type', 'stackless-theme' ), value: attributes.authorType, options: [{label:__( 'Unspecified', 'stackless-theme' ),value:''},{label:__( 'Person', 'stackless-theme' ),value:'Person'},{label:__( 'Organization', 'stackless-theme' ),value:'Organization'}], onChange: function(v) { props.setAttributes({authorType:v}); } })
					),
					el(PanelBody, { title: __( 'Fetch from WordPress.org', 'stackless-theme' ), initialOpen: true },
						el(TextControl, {
							label: __( 'Theme Slug', 'stackless-theme' ),
							help: __( 'e.g., "twentytwentyfive", "developer", "flavor"', 'stackless-theme' ),
							value: attributes.slug,
							onChange: function (val) { props.setAttributes({ slug: val }); }
						}),
						el(Button, {
							variant: 'primary',
							onClick: function() {
								fetchThemeData(attributes.slug, props.setAttributes, setLoading, setError);
							},
							disabled: isLoading,
							style: { marginBottom: '10px' }
						}, isLoading ? el(Spinner) : __( 'Fetch Theme Data', 'stackless-theme' )),
						error && el(Notice, {
							status: 'error',
							isDismissible: true,
							onRemove: function() { setError(null); }
						}, error)
					),
					el(PanelBody, { title: __( 'Theme Details', 'stackless-theme' ), initialOpen: false },
						el(TextControl, {
							label: __( 'Theme Name', 'stackless-theme' ),
							value: attributes.name,
							onChange: function (val) { props.setAttributes({ name: val }); }
						}),
						el(TextareaControl, {
							label: __( 'Description', 'stackless-theme' ),
							value: attributes.description,
							onChange: function (val) { props.setAttributes({ description: val }); }
						}),
						el(TextControl, {
							label: __( 'Author', 'stackless-theme' ),
							value: attributes.author,
							onChange: function (val) { props.setAttributes({ author: val }); }
						}),
						el(TextControl, {
							label: __( 'Version', 'stackless-theme' ),
							value: attributes.version,
							onChange: function (val) { props.setAttributes({ version: val }); }
						}),
						el(TextControl, {
							label: __( 'Active Installs', 'stackless-theme' ),
							value: attributes.activeInstalls,
							onChange: function (val) { props.setAttributes({ activeInstalls: val }); }
						}),
						el(RangeControl, {
							label: __( 'Rating', 'stackless-theme' ),
							value: attributes.rating,
							min: 0,
							max: 5,
							step: 0.5,
							onChange: function (val) { props.setAttributes({ rating: val }); }
						})
					),
					el(PanelBody, { title: __( 'Screenshot', 'stackless-theme' ), initialOpen: false },
						attributes.screenshot && el('img', {
							src: attributes.screenshot,
							style: { maxWidth: '100%', marginBottom: '10px', display: 'block', borderRadius: '6px' }
						}),
						el(TextControl, {
							label: __( 'Screenshot URL', 'stackless-theme' ),
							value: attributes.screenshot,
							onChange: function (val) { props.setAttributes({ screenshot: val }); }
						}),
						el('div', { style: { display: 'flex', gap: '8px' } },
							el(MediaUpload, {
								onSelect: function (media) { props.setAttributes({ screenshot: media.url }); },
								allowedTypes: ['image'],
								render: function (obj) {
									return el(Button, {
										onClick: obj.open,
										variant: 'secondary',
										size: 'small'
									}, __( 'Upload', 'stackless-theme' ));
								}
							}),
							attributes.screenshot && el(Button, {
								onClick: function () { props.setAttributes({ screenshot: '' }); },
								variant: 'link',
								isDestructive: true,
								size: 'small'
							}, __( 'Remove', 'stackless-theme' ))
						)
					),
					el(PanelBody, { title: __( 'Links', 'stackless-theme' ), initialOpen: false },
						el(TextControl, {
							label: __( 'WordPress.org URL', 'stackless-theme' ),
							value: attributes.wpUrl,
							onChange: function (val) { props.setAttributes({ wpUrl: val }); }
						}),
						el(TextControl, {
							label: __( 'Live Preview URL', 'stackless-theme' ),
							value: attributes.previewUrl,
							onChange: function (val) { props.setAttributes({ previewUrl: val }); }
						})
					),
					el(PanelBody, { title: __( 'Requirements', 'stackless-theme' ), initialOpen: false },
						el(TextControl, {
							label: __( 'Requires WordPress', 'stackless-theme' ),
							value: attributes.requiresWP,
							onChange: function (val) { props.setAttributes({ requiresWP: val }); }
						}),
						el(TextControl, {
							label: __( 'Requires PHP', 'stackless-theme' ),
							value: attributes.requiresPHP,
							onChange: function (val) { props.setAttributes({ requiresPHP: val }); }
						}),
						el(ToggleControl, {
							label: __( 'Show requirements in card', 'stackless-theme' ),
							checked: attributes.showRequirements,
							onChange: function (val) { props.setAttributes({ showRequirements: val }); }
						})
					),
					el(PanelBody, { title: __( 'Design', 'stackless-theme' ), initialOpen: false },
						el(SelectControl, {
							label: __( 'Style', 'stackless-theme' ),
							options: [
								{ label: __( 'Card', 'stackless-theme' ), value: 'card' },
								{ label: __( 'Horizontal', 'stackless-theme' ), value: 'horizontal' },
								{ label: __( 'Compact', 'stackless-theme' ), value: 'compact' },
								{ label: __( 'Minimal', 'stackless-theme' ), value: 'minimal' }
							],
							value: attributes.cardStyle,
							onChange: function (val) { props.setAttributes({ cardStyle: val }); }
						}),
						el(SelectControl, {
							label: __( 'Margin Bottom', 'stackless-theme' ),
							options: [
								{ label: __( 'None', 'stackless-theme' ), value: 'mb-none' },
								{ label: __( 'Half', 'stackless-theme' ), value: 'mb-half' },
								{ label: __( 'Single', 'stackless-theme' ), value: 'mb-single' },
								{ label: __( 'Double (default)', 'stackless-theme' ), value: 'mb-double' }
							],
							value: attributes.marginBottom,
							onChange: function (val) { props.setAttributes({ marginBottom: val }); }
						})
					)
				),
				el('div', blockProps,
					attributes.screenshot && el('div', { className: 'stb-theme-card-screenshot' },
						el('img', { src: attributes.screenshot, alt: attributes.name }),
						attributes.previewUrl && el('a', { href: '#', className: 'stb-theme-card-preview-overlay' },
							StacklessBlocks.icon( 'span', 'stb-icon-eye' ),
							' ' + __( 'Preview', 'stackless-theme' )
						)
					),
					el('div', { className: 'stb-theme-card-content' },
						el('h3', { className: 'stb-theme-card-name mb-none' }, attributes.name || __( 'Theme Name', 'stackless-theme' )),
						attributes.author && el('span', { className: 'stb-theme-card-author text-sec small' }, 'by ' + attributes.author),
						attributes.description && el('p', { className: 'stb-theme-card-desc' }, attributes.description),
						el('div', { className: 'stb-theme-card-meta' },
							attributes.rating > 0 && el('span', { className: 'stb-theme-card-rating' },
								renderStars(),
								attributes.ratingCount > 0 && el('span', { className: 'stb-theme-card-rating-count' }, '(' + attributes.ratingCount + ')')
							),
							attributes.activeInstalls && el('span', { className: 'stb-theme-card-installs' },
								StacklessBlocks.icon( 'span', 'stb-icon-download' ),
								' ' + attributes.activeInstalls + '+'
							),
							attributes.version && el('span', { className: 'stb-theme-card-version' }, StacklessBlocks.cardIcon( 'tag' ), ' v' + attributes.version)
						),
						attributes.showRequirements && (attributes.requiresWP || attributes.requiresPHP) && el('div', { className: 'stb-theme-card-requirements' },
							attributes.requiresWP && el('span', null, __( 'Requires WP: ', 'stackless-theme' ) + attributes.requiresWP),
							attributes.requiresPHP && el('span', null, __( 'Requires PHP: ', 'stackless-theme' ) + attributes.requiresPHP)
						),
						el('div', { className: 'stb-theme-card-actions' },
							attributes.wpUrl && el('a', { href: '#', className: 'button' }, __( 'View on WordPress.org', 'stackless-theme' )),
							attributes.previewUrl && el('a', { href: '#', className: 'button button-outline' }, __( 'Live Preview', 'stackless-theme' ))
						)
					)
				)
			);
		},
		save: function () {
			return null;
		}
	});
})(window.wp);
