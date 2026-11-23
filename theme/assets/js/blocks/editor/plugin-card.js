/**
 * Stackless block editor script: Plugin Card.
 *
 * Block names, attributes and save output are a storage contract shared with
 * the Marketers Delight blocks of the same name. Do not rename, add or drop
 * attributes here.
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
			return imageUrl; // Fallback to original URL
		})
		.catch(function() {
			return imageUrl; // Fallback to original URL on error
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

	// Fetch plugin data from WordPress.org API
	function fetchPluginData(slug, setAttributes, setLoading, setError) {
		if (!slug) {
			setError(__( 'Please enter a plugin slug', 'stackless-theme' ));
			return;
		}

		setLoading(true);
		setError(null);

		var trimmedSlug = slug.trim();

		// Use query_plugins API to get icons (plugin_information doesn't include them)
		var apiUrl = 'https://api.wordpress.org/plugins/info/1.2/?action=query_plugins&request[search]=' + encodeURIComponent(trimmedSlug) + '&request[fields][icons]=1&request[fields][banners]=1&request[per_page]=1';

		fetch(apiUrl)
			.then(function(response) { return response.json(); })
			.then(function(response) {
				var plugins = response.plugins || [];
				var data = null;

				// Find exact match by slug
				for (var i = 0; i < plugins.length; i++) {
					if (plugins[i].slug === trimmedSlug) {
						data = plugins[i];
						break;
					}
				}

				// If no exact match, try plugin_information endpoint as fallback
				if (!data) {
					return fetch('https://api.wordpress.org/plugins/info/1.2/?action=plugin_information&slug=' + encodeURIComponent(trimmedSlug))
						.then(function(r) { return r.json(); });
				}
				return data;
			})
			.then(function(data) {
				if (!data || data.error || !data.name) {
					setLoading(false);
					setError(data && data.error ? data.error : __( 'Plugin not found', 'stackless-theme' ));
					return;
				}

				// Get best icon (prefer SVG, then 2x, then 1x)
				var iconUrl = '';
				if (data.icons) {
					iconUrl = data.icons.svg || data.icons['2x'] || data.icons['1x'] || data.icons.default || '';
				}

				// Get banner image (prefer 2x/high, then 1x/low)
				var bannerUrl = '';
				if (data.banners) {
					bannerUrl = data.banners['2x'] || data.banners.high || data.banners['1x'] || data.banners.low || '';
				}

				// Convert rating from 0-100 to 0-5
				var rating = data.rating ? Math.round((data.rating / 20) * 2) / 2 : 0;

				// Decode HTML entities
				var decodedName = decodeHtmlEntities(data.name);
				var decodedDesc = decodeHtmlEntities(data.short_description || '');
				var decodedAuthor = data.author ? decodeHtmlEntities(data.author.replace(/<[^>]*>/g, '')) : '';

				// Check for existing images in media library, then import if needed
				var pluginSlug = data.slug;

				Promise.all([
					iconUrl ? checkCardImage('plugin', pluginSlug, 'icon').then(function(result) {
						if (result.exists) return result.url;
						return importCardImage('plugin', pluginSlug, 'icon', iconUrl);
					}) : Promise.resolve(''),
					bannerUrl ? checkCardImage('plugin', pluginSlug, 'banner').then(function(result) {
						if (result.exists) return result.url;
						return importCardImage('plugin', pluginSlug, 'banner', bannerUrl);
					}) : Promise.resolve('')
				]).then(function(urls) {
					setLoading(false);
					setAttributes({
						name: decodedName,
						slug: pluginSlug,
						description: decodedDesc,
						icon: urls[0] || iconUrl,
						banner: urls[1] || bannerUrl,
						rating: rating,
						ratingCount: data.num_ratings || 0,
						activeInstalls: formatNumber(data.active_installs),
						version: data.version || '',
						author: decodedAuthor,
						testedUpTo: data.tested || '',
						requiresWP: data.requires || '',
						requiresPHP: data.requires_php || '',
						downloadUrl: data.download_link || '',
						wpUrl: 'https://wordpress.org/plugins/' + pluginSlug + '/'
					});
				}).catch(function() {
					// If image import fails, still set attributes with original URLs
					setLoading(false);
					setAttributes({
						name: decodedName,
						slug: pluginSlug,
						description: decodedDesc,
						icon: iconUrl,
						banner: bannerUrl,
						rating: rating,
						ratingCount: data.num_ratings || 0,
						activeInstalls: formatNumber(data.active_installs),
						version: data.version || '',
						author: decodedAuthor,
						testedUpTo: data.tested || '',
						requiresWP: data.requires || '',
						requiresPHP: data.requires_php || '',
						downloadUrl: data.download_link || '',
						wpUrl: 'https://wordpress.org/plugins/' + pluginSlug + '/'
					});
				});
			})
			.catch(function(err) {
				setLoading(false);
				setError(__( 'Failed to fetch plugin data: ', 'stackless-theme' ) + err.message);
			});
	}

	registerBlockType('stackless/plugin-card', {
		apiVersion: 3,
		title: __( 'Plugin Card', 'stackless-theme' ),
		description: __( 'Display WordPress plugin information. Fetch data automatically from WordPress.org.', 'stackless-theme' ),
		icon: 'admin-plugins',
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
					icon: { type: 'string' },
					banner: { type: 'string' },
					rating: { type: 'number', default: 0 },
					ratingCount: { type: 'number', default: 0 },
					activeInstalls: { type: 'string' },
					version: { type: 'string' },
					author: { type: 'string' },
					testedUpTo: { type: 'string' },
					requiresWP: { type: 'string' },
					requiresPHP: { type: 'string' },
					downloadUrl: { type: 'string' },
					wpUrl: { type: 'string' },
					style: { type: 'string', default: 'card' },
					showBanner: { type: 'boolean', default: true },
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
			icon: { type: 'string' },
			banner: { type: 'string' },
			rating: { type: 'number', default: 0 },
			ratingCount: { type: 'number', default: 0 },
			activeInstalls: { type: 'string' },
			version: { type: 'string' },
			author: { type: 'string' },
			testedUpTo: { type: 'string' },
			requiresWP: { type: 'string' },
			requiresPHP: { type: 'string' },
			downloadUrl: { type: 'string' },
			wpUrl: { type: 'string' },
			cardStyle: { type: 'string', default: 'card' },
			showBanner: { type: 'boolean', default: true },
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
					stars.push(StacklessBlocks.icon( 'span', 'md-icon-star-full', 'text-warning', { key: 'star-' + i } ));
				}
				if (hasHalf) {
					stars.push(StacklessBlocks.icon( 'span', 'md-icon-star-half', 'text-warning', { key: 'star-half' } ));
				}
				return stars;
			};

			var showBannerArea = attributes.cardStyle === 'card' || attributes.cardStyle === 'horizontal';

			var blockProps = useBlockProps({
				className: 'md-plugin-card md-plugin-card-' + attributes.cardStyle + ' ' + attributes.marginBottom
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
							label: __( 'Plugin Slug', 'stackless-theme' ),
							help: __( 'e.g., "jetpack", "woocommerce", "contact-form-7"', 'stackless-theme' ),
							value: attributes.slug,
							onChange: function (val) { props.setAttributes({ slug: val }); }
						}),
						el(Button, {
							variant: 'primary',
							onClick: function() {
								fetchPluginData(attributes.slug, props.setAttributes, setLoading, setError);
							},
							disabled: isLoading,
							style: { marginBottom: '10px' }
						}, isLoading ? el(Spinner) : __( 'Fetch Plugin Data', 'stackless-theme' )),
						error && el(Notice, {
							status: 'error',
							isDismissible: true,
							onRemove: function() { setError(null); }
						}, error)
					),
					el(PanelBody, { title: __( 'Plugin Details', 'stackless-theme' ), initialOpen: false },
						el(TextControl, {
							label: __( 'Plugin Name', 'stackless-theme' ),
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
					el(PanelBody, { title: __( 'Images', 'stackless-theme' ), initialOpen: false },
						el('p', { className: 'components-base-control__label', style: { fontWeight: 600, marginBottom: '8px' } }, __( 'Icon', 'stackless-theme' )),
						attributes.icon && el('img', {
							src: attributes.icon,
							style: { maxWidth: '100px', marginBottom: '10px', display: 'block', borderRadius: '6px' }
						}),
						el(TextControl, {
							label: __( 'Icon URL', 'stackless-theme' ),
							value: attributes.icon,
							onChange: function (val) { props.setAttributes({ icon: val }); }
						}),
						el('div', { style: { display: 'flex', gap: '8px', marginBottom: '16px' } },
							el(MediaUpload, {
								onSelect: function (media) { props.setAttributes({ icon: media.url }); },
								allowedTypes: ['image'],
								render: function (obj) {
									return el(Button, {
										onClick: obj.open,
										variant: 'secondary',
										size: 'small'
									}, __( 'Upload', 'stackless-theme' ));
								}
							}),
							attributes.icon && el(Button, {
								onClick: function () { props.setAttributes({ icon: '' }); },
								variant: 'link',
								isDestructive: true,
								size: 'small'
							}, __( 'Remove', 'stackless-theme' ))
						),
						el('p', { className: 'components-base-control__label', style: { fontWeight: 600, marginBottom: '8px' } }, __( 'Banner', 'stackless-theme' )),
						attributes.banner && el('img', {
							src: attributes.banner,
							style: { maxWidth: '100%', marginBottom: '10px', display: 'block', borderRadius: '6px' }
						}),
						el(TextControl, {
							label: __( 'Banner URL', 'stackless-theme' ),
							value: attributes.banner,
							onChange: function (val) { props.setAttributes({ banner: val }); }
						}),
						el('div', { style: { display: 'flex', gap: '8px' } },
							el(MediaUpload, {
								onSelect: function (media) { props.setAttributes({ banner: media.url }); },
								allowedTypes: ['image'],
								render: function (obj) {
									return el(Button, {
										onClick: obj.open,
										variant: 'secondary',
										size: 'small'
									}, __( 'Upload', 'stackless-theme' ));
								}
							}),
							attributes.banner && el(Button, {
								onClick: function () { props.setAttributes({ banner: '' }); },
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
							label: __( 'Download URL', 'stackless-theme' ),
							value: attributes.downloadUrl,
							onChange: function (val) { props.setAttributes({ downloadUrl: val }); }
						})
					),
					el(PanelBody, { title: __( 'Requirements', 'stackless-theme' ), initialOpen: false },
						el(TextControl, {
							label: __( 'Tested up to WP', 'stackless-theme' ),
							value: attributes.testedUpTo,
							onChange: function (val) { props.setAttributes({ testedUpTo: val }); }
						}),
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
								{ label: __( 'Card (with banner)', 'stackless-theme' ), value: 'card' },
								{ label: __( 'Horizontal', 'stackless-theme' ), value: 'horizontal' },
								{ label: __( 'Compact', 'stackless-theme' ), value: 'compact' },
								{ label: __( 'Minimal', 'stackless-theme' ), value: 'minimal' }
							],
							value: attributes.cardStyle,
							onChange: function (val) { props.setAttributes({ cardStyle: val }); }
						}),
						(attributes.cardStyle === 'card' || attributes.cardStyle === 'horizontal') && el(ToggleControl, {
							label: __( 'Show banner image', 'stackless-theme' ),
							checked: attributes.showBanner,
							onChange: function (val) { props.setAttributes({ showBanner: val }); }
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
					showBannerArea && attributes.showBanner && attributes.banner && el('div', { className: 'md-plugin-card-banner' },
						el('img', { src: attributes.banner, alt: attributes.name })
					),
					el('div', { className: 'md-plugin-card-body' },
						el('div', { className: 'md-plugin-card-header' },
							attributes.icon
								? el('img', { src: attributes.icon, alt: attributes.name, className: 'md-plugin-card-icon' })
								: StacklessBlocks.icon( 'span', 'md-icon-plugin', 'md-plugin-card-icon-placeholder' ),
							el('div', { className: 'md-plugin-card-info' },
								el('h3', { className: 'md-plugin-card-name mb-none' }, attributes.name || __( 'Plugin Name', 'stackless-theme' )),
								attributes.author && el('span', { className: 'md-plugin-card-author text-sec small' }, 'by ' + attributes.author)
							)
						),
						attributes.description && el('p', { className: 'md-plugin-card-desc' }, attributes.description),
						el('div', { className: 'md-plugin-card-meta' },
							attributes.rating > 0 && el('span', { className: 'md-plugin-card-rating' },
								renderStars(),
								attributes.ratingCount > 0 && el('span', { className: 'md-plugin-card-rating-count' }, '(' + attributes.ratingCount + ')')
							),
							attributes.activeInstalls && el('span', { className: 'md-plugin-card-installs' },
								StacklessBlocks.icon( 'span', 'md-icon-download' ),
								' ' + attributes.activeInstalls + '+'
							),
							attributes.version && el('span', { className: 'md-plugin-card-version' }, StacklessBlocks.cardIcon( 'tag' ), ' v' + attributes.version)
						),
						attributes.showRequirements && (attributes.testedUpTo || attributes.requiresWP || attributes.requiresPHP) && el('div', { className: 'md-plugin-card-requirements' },
							attributes.testedUpTo && el('span', null, __( 'Tested up to: ', 'stackless-theme' ) + attributes.testedUpTo),
							attributes.requiresWP && el('span', null, __( 'Requires WP: ', 'stackless-theme' ) + attributes.requiresWP),
							attributes.requiresPHP && el('span', null, __( 'Requires PHP: ', 'stackless-theme' ) + attributes.requiresPHP)
						),
						el('div', { className: 'md-plugin-card-actions' },
							attributes.wpUrl && el('a', { href: '#', className: 'button' }, __( 'View on WordPress.org', 'stackless-theme' )),
							attributes.downloadUrl && el('a', { href: '#', className: 'button button-outline' }, __( 'Download', 'stackless-theme' ))
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
