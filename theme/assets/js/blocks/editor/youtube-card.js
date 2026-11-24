/**
 * Stackless block editor script: YouTube Card.
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
		Button = wp.components.Button,
		Spinner = wp.components.Spinner,
		Notice = wp.components.Notice,
		__ = wp.i18n.__;

	function extractVideoId(url) {
		if (!url) return null;
		var match = url.match(/(?:v=|\/embed\/|youtu\.be\/|\/v\/)([a-zA-Z0-9_-]{11})/);
		return match ? match[1] : null;
	}

	function fetchYouTubeData(url, setAttributes, setLoading, setError) {
		if (!url) {
			setError(__( 'Please enter a YouTube URL', 'stackless-theme' ));
			return;
		}

		var videoId = extractVideoId(url.trim());

		if (!videoId) {
			setError(__( 'Invalid YouTube URL', 'stackless-theme' ));
			return;
		}

		setLoading(true);
		setError(null);

		fetch('https://noembed.com/embed?url=' + encodeURIComponent(url.trim()))
			.then(function (response) {
				if (!response.ok) {
					throw new Error(__( 'Failed to fetch video data', 'stackless-theme' ));
				}
				return response.json();
			})
			.then(function (data) {
				if (data.error) {
					setLoading(false);
					setError(data.error);
					return;
				}

				var largest = 'https://img.youtube.com/vi/' + videoId + '/maxresdefault.jpg',
					standard = 'https://img.youtube.com/vi/' + videoId + '/hqdefault.jpg';

				function save(thumbnailUrl) {
					setLoading(false);
					setAttributes({
						videoUrl: url.trim(),
						videoId: videoId,
						title: data.title || '',
						channelName: data.author_name || '',
						thumbnailUrl: thumbnailUrl
					});
				}

				// Not every video has the largest thumbnail. YouTube answers a missing
				// one with a 404 and a 120px placeholder, so check before saving it.
				var probe = new Image();
				probe.onload = function () { save(probe.naturalWidth > 120 ? largest : standard); };
				probe.onerror = function () { save(standard); };
				probe.src = largest;
			})
			.catch(function (err) {
				setLoading(false);
				setError(__( 'Failed to fetch video data: ', 'stackless-theme' ) + err.message);
			});
	}

	registerBlockType('stackless/youtube-card', {
		apiVersion: 3,
		title: __( 'YouTube Card', 'stackless-theme' ),
		description: __( 'Display a YouTube video card. Fetch title and channel automatically from YouTube.', 'stackless-theme' ),
		icon: 'video-alt3',
		category: 'stackless',
		deprecated: [
			{
				// Saved blocks validate (save is null), so without this the editor
				// never runs the migration and the old string `style` is dropped.
				isEligible: function (attributes) { return typeof attributes.style === 'string'; },
				attributes: {
					videoUrl: { type: 'string' },
					videoId: { type: 'string' },
					title: { type: 'string' },
					channelName: { type: 'string' },
					thumbnailUrl: { type: 'string' },
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
			videoUrl: { type: 'string' },
			videoId: { type: 'string' },
			title: { type: 'string' },
			channelName: { type: 'string' },
			thumbnailUrl: { type: 'string' },
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
				className: 'stb-youtube-card stb-youtube-card-' + attributes.cardStyle + ' ' + attributes.marginBottom
			});

			return el(Fragment, null,
				el(InspectorControls, { key: 'inspector' },
					el(PanelBody, { title: __( 'Fetch from YouTube', 'stackless-theme' ), initialOpen: true },
						el(TextControl, {
							label: __( 'YouTube URL', 'stackless-theme' ),
							placeholder: 'https://www.youtube.com/watch?v=...',
							value: attributes.videoUrl,
							onChange: function (val) { props.setAttributes({ videoUrl: val }); }
						}),
						el(Button, {
							variant: 'primary',
							onClick: function () {
								fetchYouTubeData(attributes.videoUrl, props.setAttributes, setLoading, setError);
							},
							disabled: isLoading,
							style: { marginBottom: '10px' }
						}, isLoading ? el(Spinner) : __( 'Fetch Video Data', 'stackless-theme' )),
						error && el(Notice, {
							status: 'error',
							isDismissible: true,
							onRemove: function () { setError(null); }
						}, error)
					),
					el(PanelBody, { title: __( 'Video Details', 'stackless-theme' ), initialOpen: false },
						el(TextControl, {
							label: __( 'Title', 'stackless-theme' ),
							value: attributes.title,
							onChange: function (val) { props.setAttributes({ title: val }); }
						}),
						el(TextControl, {
							label: __( 'Channel Name', 'stackless-theme' ),
							value: attributes.channelName,
							onChange: function (val) { props.setAttributes({ channelName: val }); }
						}),
						el(TextControl, {
							label: __( 'Thumbnail URL', 'stackless-theme' ),
							value: attributes.thumbnailUrl,
							onChange: function (val) { props.setAttributes({ thumbnailUrl: val }); }
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
					attributes.cardStyle !== 'compact' && attributes.cardStyle !== 'minimal' && el('div', { className: 'stb-youtube-card-thumbnail' },
						attributes.thumbnailUrl
							? el('img', { src: attributes.thumbnailUrl, alt: attributes.title })
							: StacklessBlocks.icon( 'span', 'stb-icon-youtube', 'stb-youtube-card-thumb-placeholder' ),
						el('span', { className: 'stb-youtube-card-play' })
					),
					el('div', { className: 'stb-youtube-card-body' },
						el('h3', { className: 'stb-youtube-card-title mb-none' }, attributes.title || __( 'Video Title', 'stackless-theme' )),
						attributes.channelName && el('span', { className: 'stb-youtube-card-channel text-sec small' }, StacklessBlocks.icon( 'span', 'stb-icon-youtube' ), ' ' + attributes.channelName),
						el('div', { className: 'stb-youtube-card-actions' },
							attributes.videoUrl && el('a', { href: '#', className: 'button' }, __( 'Watch on YouTube', 'stackless-theme' ))
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
