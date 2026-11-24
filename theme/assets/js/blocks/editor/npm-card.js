/**
 * Stackless block editor script: npm Card.
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
		PanelBody = wp.components.PanelBody,
		SelectControl = wp.components.SelectControl,
		TextControl = wp.components.TextControl,
		TextareaControl = wp.components.TextareaControl,
		Button = wp.components.Button,
		Spinner = wp.components.Spinner,
		Notice = wp.components.Notice,
		__ = wp.i18n.__;

	function formatNumber(num) {
		if (!num) return '0';
		var n = parseInt(num, 10);
		if (isNaN(n)) return '0';
		if (n >= 1000000) return (n / 1000000).toFixed(1).replace(/\.0$/, '') + 'M';
		if (n >= 1000) return (n / 1000).toFixed(1).replace(/\.0$/, '') + 'k';
		return n.toString();
	}

	function fetchNpmData(name, setAttributes, setLoading, setError) {
		if (!name) {
			setError(__( 'Please enter a package name', 'stackless-theme' ));
			return;
		}

		var trimmedName = name.trim();
		if (!trimmedName) {
			setError(__( 'Please enter a package name', 'stackless-theme' ));
			return;
		}

		setLoading(true);
		setError(null);

		var registryUrl = 'https://registry.npmjs.org/' + encodeURIComponent(trimmedName);

		fetch(registryUrl)
			.then(function (response) {
				if (response.status === 404) {
					throw new Error(__( 'Package not found on npm', 'stackless-theme' ));
				}
				if (!response.ok) {
					throw new Error(__( 'Failed to fetch package data', 'stackless-theme' ));
				}
				return response.json();
			})
			.then(function (data) {
				if (!data || !data.name) {
					throw new Error(__( 'Invalid package data', 'stackless-theme' ));
				}

				var version = data['dist-tags'] && data['dist-tags'].latest ? data['dist-tags'].latest : '';

				var license = '';
				if (data.license) {
					license = typeof data.license === 'string' ? data.license : (data.license.type || '');
				}

				var author = '';
				if (data.author) {
					author = typeof data.author === 'string' ? data.author : (data.author.name || '');
				}

				var repository = '';
				if (data.repository && data.repository.url) {
					repository = data.repository.url;
					if (repository.indexOf('git+') === 0) {
						repository = repository.substring(4);
					}
					if (repository.substring(repository.length - 4) === '.git') {
						repository = repository.substring(0, repository.length - 4);
					}
				}

				var keywords = data.keywords || [];

				var lastPublished = '';
				if (version && data.time && data.time[version]) {
					var date = new Date(data.time[version]);
					lastPublished = date.toLocaleDateString('en-US', {
						year: 'numeric',
						month: 'short',
						day: 'numeric'
					});
				}

				var npmUrl = 'https://www.npmjs.com/package/' + trimmedName;
				var homepage = data.homepage || '';
				var description = data.description || '';

				var downloadsUrl = 'https://api.npmjs.org/downloads/point/last-week/' + encodeURIComponent(trimmedName);

				fetch(downloadsUrl)
					.then(function (response) { return response.json(); })
					.then(function (dlData) {
						var downloads = dlData && dlData.downloads ? formatNumber(dlData.downloads) : '0';

						setLoading(false);
						setAttributes({
							packageName: data.name,
							description: description,
							version: version,
							license: license,
							weeklyDownloads: downloads,
							author: author,
							homepage: homepage,
							repository: repository,
							npmUrl: npmUrl,
							keywords: keywords,
							lastPublished: lastPublished
						});
					})
					.catch(function () {
						setLoading(false);
						setAttributes({
							packageName: data.name,
							description: description,
							version: version,
							license: license,
							weeklyDownloads: '0',
							author: author,
							homepage: homepage,
							repository: repository,
							npmUrl: npmUrl,
							keywords: keywords,
							lastPublished: lastPublished
						});
					});
			})
			.catch(function (err) {
				setLoading(false);
				setError(err.message || __( 'Failed to fetch package data', 'stackless-theme' ));
			});
	}

	registerBlockType('stackless/npm-card', {
		apiVersion: 3,
		title: __( 'npm Package Card', 'stackless-theme' ),
		description: __( 'Display npm package information. Fetch data automatically from the npm registry.', 'stackless-theme' ),
		icon: 'editor-code',
		category: 'stackless',
		deprecated: [
			{
				// Saved blocks validate (save is null), so without this the editor
				// never runs the migration and the old string `style` is dropped.
				isEligible: function (attributes) { return typeof attributes.style === 'string'; },
				attributes: {
					packageName: { type: 'string' },
					description: { type: 'string' },
					version: { type: 'string' },
					license: { type: 'string' },
					weeklyDownloads: { type: 'string' },
					author: { type: 'string' },
					homepage: { type: 'string' },
					repository: { type: 'string' },
					npmUrl: { type: 'string' },
					keywords: { type: 'array', default: [] },
					lastPublished: { type: 'string' },
					style: { type: 'string', default: 'card' },
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
			packageName: { type: 'string' },
			description: { type: 'string' },
			version: { type: 'string' },
			license: { type: 'string' },
			weeklyDownloads: { type: 'string' },
			author: { type: 'string' },
			homepage: { type: 'string' },
			repository: { type: 'string' },
			npmUrl: { type: 'string' },
			keywords: { type: 'array', default: [] },
			lastPublished: { type: 'string' },
			cardStyle: { type: 'string', default: 'card' },
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
			var _inputState = useState(attributes.packageName || ''),
				inputValue = _inputState[0],
				setInputValue = _inputState[1];

			var blockProps = useBlockProps({
				className: 'stb-npm-card stb-npm-card-' + attributes.cardStyle + ' ' + attributes.marginBottom
			});

			return el(Fragment, null,
				el(InspectorControls, { key: 'inspector' },
					el(PanelBody, { title: __( 'Fetch from npm', 'stackless-theme' ), initialOpen: true },
						el(TextControl, {
							label: __( 'Package Name', 'stackless-theme' ),
							help: __( 'e.g., "express", "react"', 'stackless-theme' ),
							value: inputValue,
							onChange: function (val) { setInputValue(val); }
						}),
						el(Button, {
							variant: 'primary',
							onClick: function () {
								fetchNpmData(inputValue, props.setAttributes, setLoading, setError);
							},
							disabled: isLoading,
							style: { marginBottom: '10px' }
						}, isLoading ? el(Spinner) : __( 'Fetch Package Data', 'stackless-theme' )),
						error && el(Notice, {
							status: 'error',
							isDismissible: true,
							onRemove: function () { setError(null); }
						}, error)
					),
					el(PanelBody, { title: __( 'Package Details', 'stackless-theme' ), initialOpen: false },
						el(TextControl, {
							label: __( 'Package Name', 'stackless-theme' ),
							value: attributes.packageName,
							onChange: function (val) { props.setAttributes({ packageName: val }); }
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
							label: __( 'License', 'stackless-theme' ),
							value: attributes.license,
							onChange: function (val) { props.setAttributes({ license: val }); }
						}),
						el(TextControl, {
							label: __( 'Homepage', 'stackless-theme' ),
							value: attributes.homepage,
							onChange: function (val) { props.setAttributes({ homepage: val }); }
						})
					),
					el(PanelBody, { title: __( 'Design', 'stackless-theme' ), initialOpen: false },
						el(SelectControl, {
							label: __( 'Style', 'stackless-theme' ),
							options: [
								{ label: __( 'Card', 'stackless-theme' ), value: 'card' },
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
					el('div', { className: 'stb-npm-card-body' },
						el('div', { className: 'stb-npm-card-header' },
							el('span', { className: 'stb-npm-card-icon' }, StacklessBlocks.cardIcon( 'package', '' )),
							el('div', { className: 'stb-npm-card-identity' },
								el('h3', { className: 'stb-npm-card-name mb-none' }, attributes.packageName || __( 'package-name', 'stackless-theme' )),
								attributes.author && el('span', { className: 'stb-npm-card-author text-sec small' }, 'by ' + attributes.author)
							)
						),
						attributes.description && el('p', { className: 'stb-npm-card-desc' }, attributes.description),
						el('div', { className: 'stb-npm-card-meta' },
							attributes.version && el('span', { className: 'stb-npm-card-version' }, StacklessBlocks.cardIcon( 'tag' ), ' v' + attributes.version),
							el('span', { className: 'stb-npm-card-downloads' },
								StacklessBlocks.icon( 'span', 'stb-icon-download' ),
								' ' + (attributes.weeklyDownloads || '0') + '/wk'
							),
							attributes.license && el('span', { className: 'stb-npm-card-license' }, StacklessBlocks.cardIcon( 'scale' ), ' ' + attributes.license),
							attributes.lastPublished && el('span', { className: 'stb-npm-card-published' }, StacklessBlocks.icon( 'span', 'stb-icon-clock' ), ' ' + attributes.lastPublished)
						),
						attributes.keywords && attributes.keywords.length > 0 && el('div', { className: 'stb-npm-card-keywords' },
							attributes.keywords.slice(0, 8).map(function (kw, i) {
								return el('span', { key: 'kw-' + i, className: 'stb-npm-card-keyword' }, kw);
							})
						),
						el('div', { className: 'stb-npm-card-actions' },
							attributes.npmUrl && el('a', { href: '#', className: 'button' }, __( 'View on npm', 'stackless-theme' )),
							attributes.homepage && el('a', { href: '#', className: 'button button-outline' }, __( 'Homepage', 'stackless-theme' ))
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
