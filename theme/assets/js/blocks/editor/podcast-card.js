/**
 * Stackless block editor script: Podcast Card.
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
		RangeControl = wp.components.RangeControl,
		Button = wp.components.Button,
		Spinner = wp.components.Spinner,
		Notice = wp.components.Notice,
		__ = wp.i18n.__;

	function fetchPodcastEpisode(feedUrl, episodeIndex, setAttributes, setLoading, setError) {
		if (!feedUrl) {
			setError(__( 'Please enter a podcast feed URL', 'stackless-theme' ));
			return;
		}

		if (!window.StacklessCardImages) {
			setError(__( 'AJAX handler not available. Please reload the editor.', 'stackless-theme' ));
			return;
		}

		setLoading(true);
		setError(null);

		var formData = new FormData();
		formData.append('action', 'stackless_fetch_podcast_episode');
		formData.append('nonce', StacklessCardImages.nonce);
		formData.append('feed_url', feedUrl.trim());
		formData.append('episode_index', episodeIndex);

		fetch(StacklessCardImages.ajaxUrl, {
			method: 'POST',
			body: formData,
			credentials: 'same-origin'
		})
		.then(function (response) { return response.json(); })
		.then(function (result) {
			setLoading(false);

			if (result.success) {
				setAttributes({
					podcastTitle: result.data.podcastTitle || '',
					episodeTitle: result.data.episodeTitle || '',
					episodeDescription: result.data.episodeDescription || '',
					artworkUrl: result.data.artworkUrl || '',
					duration: result.data.duration || '',
					publishDate: result.data.publishDate || '',
					audioUrl: result.data.audioUrl || '',
					episodeUrl: result.data.episodeUrl || ''
				});
			} else {
				setError(result.data || __( 'Failed to fetch podcast episode', 'stackless-theme' ));
			}
		})
		.catch(function (err) {
			setLoading(false);
			setError(__( 'Network error: ', 'stackless-theme' ) + err.message);
		});
	}

	registerBlockType('stackless/podcast-card', {
		apiVersion: 3,
		title: __( 'Podcast Card', 'stackless-theme' ),
		description: __( 'Display a podcast episode card with artwork, metadata, and playback link. Fetches data from RSS feeds.', 'stackless-theme' ),
		icon: 'microphone',
		category: 'stackless',
		deprecated: [
			{
				// Saved blocks validate (save is null), so without this the editor
				// never runs the migration and the old string `style` is dropped.
				isEligible: function (attributes) { return typeof attributes.style === 'string'; },
				attributes: {
					feedUrl: { type: 'string' },
					episodeIndex: { type: 'number', default: 0 },
					podcastTitle: { type: 'string' },
					episodeTitle: { type: 'string' },
					episodeDescription: { type: 'string' },
					artworkUrl: { type: 'string' },
					duration: { type: 'string' },
					publishDate: { type: 'string' },
					audioUrl: { type: 'string' },
					episodeUrl: { type: 'string' },
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
			feedUrl: { type: 'string' },
			episodeIndex: { type: 'number', default: 0 },
			podcastTitle: { type: 'string' },
			episodeTitle: { type: 'string' },
			episodeDescription: { type: 'string' },
			artworkUrl: { type: 'string' },
			duration: { type: 'string' },
			publishDate: { type: 'string' },
			audioUrl: { type: 'string' },
			episodeUrl: { type: 'string' },
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
				className: 'md-podcast-card md-podcast-card-' + attributes.cardStyle + ' ' + attributes.marginBottom
			});

			return el(Fragment, null,
				el(InspectorControls, { key: 'inspector' },
					el(PanelBody, { title: __( 'Fetch Podcast Episode', 'stackless-theme' ), initialOpen: true },
						el(TextControl, {
							label: __( 'Feed URL', 'stackless-theme' ),
							placeholder: 'https://example.com/feed.xml',
							value: attributes.feedUrl,
							onChange: function (val) { props.setAttributes({ feedUrl: val }); }
						}),
						el(RangeControl, {
							label: __( 'Episode Index', 'stackless-theme' ),
							help: __( '0 = latest episode', 'stackless-theme' ),
							value: attributes.episodeIndex,
							min: 0,
							max: 50,
							onChange: function (val) { props.setAttributes({ episodeIndex: val }); }
						}),
						el(Button, {
							variant: 'primary',
							onClick: function () {
								fetchPodcastEpisode(attributes.feedUrl, attributes.episodeIndex, props.setAttributes, setLoading, setError);
							},
							disabled: isLoading,
							style: { marginBottom: '10px' }
						}, isLoading ? el(Spinner) : __( 'Fetch Episode', 'stackless-theme' )),
						error && el(Notice, {
							status: 'error',
							isDismissible: true,
							onRemove: function () { setError(null); }
						}, error)
					),
					el(PanelBody, { title: __( 'Episode Details', 'stackless-theme' ), initialOpen: false },
						el(TextControl, {
							label: __( 'Episode Title', 'stackless-theme' ),
							value: attributes.episodeTitle,
							onChange: function (val) { props.setAttributes({ episodeTitle: val }); }
						}),
						el(TextareaControl, {
							label: __( 'Episode Description', 'stackless-theme' ),
							value: attributes.episodeDescription,
							onChange: function (val) { props.setAttributes({ episodeDescription: val }); }
						}),
						el(TextControl, {
							label: __( 'Podcast Title', 'stackless-theme' ),
							value: attributes.podcastTitle,
							onChange: function (val) { props.setAttributes({ podcastTitle: val }); }
						}),
						el(TextControl, {
							label: __( 'Artwork URL', 'stackless-theme' ),
							value: attributes.artworkUrl,
							onChange: function (val) { props.setAttributes({ artworkUrl: val }); }
						}),
						el(TextControl, {
							label: __( 'Duration', 'stackless-theme' ),
							value: attributes.duration,
							onChange: function (val) { props.setAttributes({ duration: val }); }
						}),
						el(TextControl, {
							label: __( 'Audio URL', 'stackless-theme' ),
							value: attributes.audioUrl,
							onChange: function (val) { props.setAttributes({ audioUrl: val }); }
						}),
						el(TextControl, {
							label: __( 'Episode URL', 'stackless-theme' ),
							value: attributes.episodeUrl,
							onChange: function (val) { props.setAttributes({ episodeUrl: val }); }
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
					el('div', { className: 'md-podcast-card-layout' },
					el('div', { className: 'md-podcast-card-artwork' },
						attributes.artworkUrl
							? el('img', { src: attributes.artworkUrl, alt: attributes.podcastTitle })
							: StacklessBlocks.icon( 'span', 'md-icon-mic', 'md-podcast-card-artwork-placeholder' )
					),
					el('div', { className: 'md-podcast-card-body' },
						attributes.podcastTitle && el('div', { className: 'md-podcast-card-show' }, attributes.podcastTitle),
						el('h3', { className: 'md-podcast-card-title' }, attributes.episodeTitle || __( 'Episode Title', 'stackless-theme' )),
						el('div', { className: 'md-podcast-card-meta' },
							attributes.duration && el('span', { className: 'md-podcast-card-duration' }, StacklessBlocks.icon( 'span', 'md-icon-clock' ), ' ' + attributes.duration),
							attributes.publishDate && el('span', { className: 'md-podcast-card-date' }, StacklessBlocks.cardIcon( 'calendar' ), ' ' + attributes.publishDate)
						),
						attributes.episodeDescription && el('p', { className: 'md-podcast-card-desc' }, attributes.episodeDescription),
						el('div', { className: 'md-podcast-card-actions' },
							(attributes.audioUrl || attributes.episodeUrl) && el('a', { href: '#', className: 'button' }, __( 'Listen', 'stackless-theme' )),
							attributes.episodeUrl && el('a', { href: '#', className: 'button button-outline' }, __( 'View Episode', 'stackless-theme' ))
						)
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
