/**
 * Stackless block editor script: GitHub Card.
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
		PanelBody = wp.components.PanelBody,
		SelectControl = wp.components.SelectControl,
		TextControl = wp.components.TextControl,
		TextareaControl = wp.components.TextareaControl,
		Button = wp.components.Button,
		Spinner = wp.components.Spinner,
		Notice = wp.components.Notice,
		__ = wp.i18n.__;

	// Format number with commas
	function formatNumber(num) {
		if (!num) return '0';
		return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ',');
	}

	// Language color map for common languages
	var languageColors = {
		JavaScript: '#f1e05a',
		TypeScript: '#3178c6',
		Python: '#3572A5',
		Java: '#b07219',
		Go: '#00ADD8',
		Rust: '#dea584',
		Ruby: '#701516',
		PHP: '#4F5D95',
		'C#': '#178600',
		'C++': '#f34b7d',
		C: '#555555',
		Swift: '#F05138',
		Kotlin: '#A97BFF',
		Dart: '#00B4AB',
		Shell: '#89e051',
		HTML: '#e34c26',
		CSS: '#563d7c',
		Vue: '#41b883',
		Svelte: '#ff3e00',
		Lua: '#000080',
		Elixir: '#6e4a7e',
		Scala: '#c22d40',
		R: '#198CE7',
		Haskell: '#5e5086',
		Zig: '#ec915c'
	};

	// Format relative time
	function formatRelativeTime(dateStr) {
		if (!dateStr) return '';
		var now = new Date();
		var date = new Date(dateStr);
		var diffMs = now - date;
		var diffDays = Math.floor(diffMs / (1000 * 60 * 60 * 24));

		if (diffDays === 0) return 'today';
		if (diffDays === 1) return 'yesterday';
		if (diffDays < 30) return diffDays + ' days ago';
		if (diffDays < 365) {
			var months = Math.floor(diffDays / 30);
			return months === 1 ? '1 month ago' : months + ' months ago';
		}
		var years = Math.floor(diffDays / 365);
		return years === 1 ? '1 year ago' : years + ' years ago';
	}

	// Fetch repository data from GitHub API
	function fetchGitHubData(slug, setAttributes, setLoading, setError) {
		if (!slug) {
			setError(__( 'Please enter a repository slug', 'stackless-theme' ));
			return;
		}

		var trimmedSlug = slug.trim();

		// Validate owner/repo format
		if (!/^[^\/]+\/[^\/]+$/.test(trimmedSlug)) {
			setError(__( 'Invalid format. Use "owner/repo" (e.g., "facebook/react")', 'stackless-theme' ));
			return;
		}

		setLoading(true);
		setError(null);

		var parts = trimmedSlug.split('/');
		var owner = parts[0];
		var repo = parts[1];

		fetch('https://api.github.com/repos/' + encodeURIComponent(owner) + '/' + encodeURIComponent(repo))
			.then(function (response) {
				if (response.status === 404) {
					throw new Error(__( 'Repository not found', 'stackless-theme' ));
				}
				if (response.status === 403) {
					throw new Error(__( 'GitHub API rate limit exceeded. Try again later.', 'stackless-theme' ));
				}
				if (!response.ok) {
					throw new Error(__( 'Failed to fetch repository data', 'stackless-theme' ));
				}
				return response.json();
			})
			.then(function (data) {
				setLoading(false);
				setAttributes({
					repoSlug: trimmedSlug,
					name: data.full_name || '',
					description: data.description || '',
					language: data.language || '',
					stars: data.stargazers_count || 0,
					forks: data.forks_count || 0,
					openIssues: data.open_issues_count || 0,
					license: data.license && data.license.spdx_id ? data.license.spdx_id : '',
					owner: data.owner ? data.owner.login : '',
					ownerAvatar: data.owner ? data.owner.avatar_url : '',
					lastUpdated: data.updated_at || '',
					repoUrl: data.html_url || '',
					topics: data.topics || []
				});
			})
			.catch(function (err) {
				setLoading(false);
				setError(err.message);
			});
	}

	registerBlockType('stackless/github-card', {
		apiVersion: 3,
		title: __( 'GitHub Card', 'stackless-theme' ),
		description: __( 'Display GitHub repository information. Fetch data automatically from GitHub.', 'stackless-theme' ),
		icon: 'editor-code',
		category: 'stackless',
		deprecated: [
			{
				// Saved blocks validate (save is null), so without this the editor
				// never runs the migration and the old string `style` is dropped.
				isEligible: function (attributes) { return typeof attributes.style === 'string'; },
				attributes: {
					repoSlug: { type: 'string' },
					name: { type: 'string' },
					description: { type: 'string' },
					language: { type: 'string' },
					stars: { type: 'number', default: 0 },
					forks: { type: 'number', default: 0 },
					openIssues: { type: 'number', default: 0 },
					license: { type: 'string' },
					owner: { type: 'string' },
					ownerAvatar: { type: 'string' },
					lastUpdated: { type: 'string' },
					repoUrl: { type: 'string' },
					topics: { type: 'array', default: [] },
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
			repoSlug: { type: 'string' },
			name: { type: 'string' },
			description: { type: 'string' },
			language: { type: 'string' },
			stars: { type: 'number', default: 0 },
			forks: { type: 'number', default: 0 },
			openIssues: { type: 'number', default: 0 },
			license: { type: 'string' },
			owner: { type: 'string' },
			ownerAvatar: { type: 'string' },
			lastUpdated: { type: 'string' },
			repoUrl: { type: 'string' },
			topics: { type: 'array', default: [] },
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

			var blockProps = useBlockProps({
				className: 'md-github-card md-github-card-' + attributes.cardStyle + ' ' + attributes.marginBottom
			});

			return el(Fragment, null,
				el(InspectorControls, { key: 'inspector' },
					el(PanelBody, { title: __( 'Fetch from GitHub', 'stackless-theme' ), initialOpen: true },
						el(TextControl, {
							label: __( 'Repository Slug', 'stackless-theme' ),
							help: __( 'e.g., "facebook/react"', 'stackless-theme' ),
							value: attributes.repoSlug,
							onChange: function (val) { props.setAttributes({ repoSlug: val }); }
						}),
						el(Button, {
							variant: 'primary',
							onClick: function () {
								fetchGitHubData(attributes.repoSlug, props.setAttributes, setLoading, setError);
							},
							disabled: isLoading,
							style: { marginBottom: '10px' }
						}, isLoading ? el(Spinner) : __( 'Fetch Repo Data', 'stackless-theme' )),
						error && el(Notice, {
							status: 'error',
							isDismissible: true,
							onRemove: function () { setError(null); }
						}, error)
					),
					el(PanelBody, { title: __( 'Repository Details', 'stackless-theme' ), initialOpen: false },
						el(TextControl, {
							label: __( 'Repository Name', 'stackless-theme' ),
							value: attributes.name,
							onChange: function (val) { props.setAttributes({ name: val }); }
						}),
						el(TextareaControl, {
							label: __( 'Description', 'stackless-theme' ),
							value: attributes.description,
							onChange: function (val) { props.setAttributes({ description: val }); }
						}),
						el(TextControl, {
							label: __( 'Language', 'stackless-theme' ),
							value: attributes.language,
							onChange: function (val) { props.setAttributes({ language: val }); }
						}),
						el(TextControl, {
							label: __( 'Owner', 'stackless-theme' ),
							value: attributes.owner,
							onChange: function (val) { props.setAttributes({ owner: val }); }
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
					el('div', { className: 'md-github-card-body' },
						el('div', { className: 'md-github-card-header' },
							attributes.ownerAvatar
								? el('img', { src: attributes.ownerAvatar, alt: attributes.owner, className: 'md-github-card-avatar' })
								: StacklessBlocks.icon( 'span', 'md-icon-github', 'md-github-card-avatar-placeholder' ),
							el('div', { className: 'md-github-card-identity' },
								el('h3', { className: 'md-github-card-name mb-none' }, attributes.name || __( 'owner/repo', 'stackless-theme' )),
								attributes.owner && el('span', { className: 'md-github-card-owner text-sec small' }, attributes.owner)
							)
						),
						attributes.description && el('p', { className: 'md-github-card-desc' }, attributes.description),
						el('div', { className: 'md-github-card-meta' },
							attributes.language && el('span', { className: 'md-github-card-language' },
								el('span', {
									className: 'md-github-card-language-dot',
									style: { backgroundColor: languageColors[attributes.language] || '#8b8b8b' }
								}),
								attributes.language
							),
							el('span', { className: 'md-github-card-stat md-github-card-stars' }, StacklessBlocks.icon( 'span', 'md-icon-star-full' ), ' ' + formatNumber(attributes.stars)),
							attributes.forks > 0 && el('span', { className: 'md-github-card-stat' }, StacklessBlocks.cardIcon( 'fork', 'md-github-card-icon' ), ' ' + formatNumber(attributes.forks)),
							attributes.license && attributes.license !== 'NOASSERTION' && el('span', { className: 'md-github-card-stat' }, StacklessBlocks.icon( 'span', 'md-icon-shield-check' ), ' ' + attributes.license),
							attributes.openIssues > 0 && el('span', { className: 'md-github-card-stat' }, StacklessBlocks.icon( 'span', 'md-icon-warning' ), ' ' + formatNumber(attributes.openIssues) + ' issues'),
							attributes.lastUpdated && el('span', { className: 'md-github-card-stat' }, StacklessBlocks.icon( 'span', 'md-icon-clock' ), ' Updated ' + formatRelativeTime(attributes.lastUpdated))
						),
						attributes.topics && attributes.topics.length > 0 && el('div', { className: 'md-github-card-topics' },
							attributes.topics.map(function (topic, i) {
								return el('span', { key: 'topic-' + i, className: 'md-github-card-topic' }, topic);
							})
						),
						el('div', { className: 'md-github-card-actions' },
							attributes.repoUrl && el('a', { href: '#', className: 'button' }, __( 'View on GitHub', 'stackless-theme' ))
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
