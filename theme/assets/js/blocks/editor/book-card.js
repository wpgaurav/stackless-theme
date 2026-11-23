/**
 * Stackless block editor script: Book Card.
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

	function fetchBookData(isbn, setAttributes, setLoading, setError) {
		if (!isbn) {
			setError(__( 'Please enter an ISBN', 'stackless-theme' ));
			return;
		}

		var cleanedIsbn = isbn.replace(/[-\s]/g, '').trim();

		if (!cleanedIsbn) {
			setError(__( 'Please enter a valid ISBN', 'stackless-theme' ));
			return;
		}

		setLoading(true);
		setError(null);

		var apiUrl = 'https://openlibrary.org/api/books?bibkeys=ISBN:' + cleanedIsbn + '&format=json&jscmd=data';

		fetch(apiUrl)
			.then(function (response) { return response.json(); })
			.then(function (response) {
				var data = response['ISBN:' + cleanedIsbn];

				if (!data) {
					data = response['ISBN:' + isbn.trim()];
				}

				if (!data) {
					setLoading(false);
					setError(__( 'Book not found. Check the ISBN and try again.', 'stackless-theme' ));
					return;
				}

				var authors = data.authors ? data.authors.map(function (a) { return a.name; }) : [];
				var publisher = data.publishers && data.publishers[0] ? data.publishers[0].name : '';

				var publishYear = '';
				if (data.publish_date) {
					var yearMatch = data.publish_date.match(/\d{4}/);
					if (yearMatch) {
						publishYear = yearMatch[0];
					}
				}

				var pageCount = data.number_of_pages || 0;
				var subjects = data.subjects ? data.subjects.slice(0, 5).map(function (s) { return s.name; }) : [];
				var coverImage = data.cover ? data.cover.large : 'https://covers.openlibrary.org/b/isbn/' + cleanedIsbn + '-L.jpg';
				var openLibraryUrl = data.url || '';
				var description = data.excerpts && data.excerpts[0] ? data.excerpts[0].text : '';

				setLoading(false);
				setAttributes({
					title: data.title,
					authors: authors,
					publisher: publisher,
					publishYear: publishYear,
					pageCount: pageCount,
					subjects: subjects,
					coverImage: coverImage,
					openLibraryUrl: openLibraryUrl,
					description: description
				});
			})
			.catch(function (err) {
				setLoading(false);
				setError(__( 'Failed to fetch book data: ', 'stackless-theme' ) + err.message);
			});
	}

	registerBlockType('stackless/book-card', {
		apiVersion: 3,
		title: __( 'Book Card', 'stackless-theme' ),
		description: __( 'Display book information. Fetch data automatically from Open Library.', 'stackless-theme' ),
		icon: 'book-alt',
		category: 'stackless',
		deprecated: [
			{
				// Saved blocks validate (save is null), so without this the editor
				// never runs the migration and the old string `style` is dropped.
				isEligible: function (attributes) { return typeof attributes.style === 'string'; },
				attributes: {
					isbn: { type: 'string' },
					title: { type: 'string' },
					authors: { type: 'array', default: [] },
					coverImage: { type: 'string' },
					publishYear: { type: 'string' },
					pageCount: { type: 'number', default: 0 },
					publisher: { type: 'string' },
					subjects: { type: 'array', default: [] },
					description: { type: 'string' },
					openLibraryUrl: { type: 'string' },
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
			isbn: { type: 'string' },
			title: { type: 'string' },
			authors: { type: 'array', default: [] },
			coverImage: { type: 'string' },
			publishYear: { type: 'string' },
			pageCount: { type: 'number', default: 0 },
			publisher: { type: 'string' },
			subjects: { type: 'array', default: [] },
			description: { type: 'string' },
			openLibraryUrl: { type: 'string' },
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
				className: 'md-book-card md-book-card-' + attributes.cardStyle + ' ' + attributes.marginBottom
			});

			return el(Fragment, null,
				el(InspectorControls, { key: 'inspector' },
					el(PanelBody, { title: __( 'Fetch from Open Library', 'stackless-theme' ), initialOpen: true },
						el(TextControl, {
							label: __( 'ISBN', 'stackless-theme' ),
							help: __( 'Enter ISBN-10 or ISBN-13, e.g., 9780316769488', 'stackless-theme' ),
							value: attributes.isbn,
							onChange: function (val) { props.setAttributes({ isbn: val }); }
						}),
						el(Button, {
							variant: 'primary',
							onClick: function () {
								fetchBookData(attributes.isbn, props.setAttributes, setLoading, setError);
							},
							disabled: isLoading,
							style: { marginBottom: '10px' }
						}, isLoading ? el(Spinner) : __( 'Fetch Book Data', 'stackless-theme' )),
						error && el(Notice, {
							status: 'error',
							isDismissible: true,
							onRemove: function () { setError(null); }
						}, error)
					),
					el(PanelBody, { title: __( 'Book Details', 'stackless-theme' ), initialOpen: false },
						el(TextControl, {
							label: __( 'Title', 'stackless-theme' ),
							value: attributes.title,
							onChange: function (val) { props.setAttributes({ title: val }); }
						}),
						el(TextControl, {
							label: __( 'Authors', 'stackless-theme' ),
							help: __( 'Fetched from API. Comma-separated if multiple.', 'stackless-theme' ),
							value: attributes.authors ? attributes.authors.join(', ') : '',
							onChange: function (val) {
								props.setAttributes({ authors: val.split(',').map(function (a) { return a.trim(); }).filter(function (a) { return a; }) });
							}
						}),
						el(TextControl, {
							label: __( 'Publisher', 'stackless-theme' ),
							value: attributes.publisher,
							onChange: function (val) { props.setAttributes({ publisher: val }); }
						}),
						el(TextControl, {
							label: __( 'Publish Year', 'stackless-theme' ),
							value: attributes.publishYear,
							onChange: function (val) { props.setAttributes({ publishYear: val }); }
						}),
						el(TextareaControl, {
							label: __( 'Description', 'stackless-theme' ),
							value: attributes.description,
							onChange: function (val) { props.setAttributes({ description: val }); }
						}),
						el(TextControl, {
							label: __( 'Cover Image URL', 'stackless-theme' ),
							value: attributes.coverImage,
							onChange: function (val) { props.setAttributes({ coverImage: val }); }
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
					el('div', { className: 'md-book-card-layout' },
					el('div', { className: 'md-book-card-cover' },
						attributes.coverImage
							? el('img', { src: attributes.coverImage, alt: attributes.title })
							: StacklessBlocks.icon( 'span', 'md-icon-book', 'md-book-card-cover-placeholder' )
					),
					el('div', { className: 'md-book-card-body' },
						el('h3', { className: 'md-book-card-title' }, attributes.title || __( 'Book Title', 'stackless-theme' )),
						attributes.authors && attributes.authors.length > 0 && el('div', { className: 'md-book-card-authors' },
							StacklessBlocks.cardIcon( 'user' ),
							' ' + attributes.authors.join(', ')
						),
						el('div', { className: 'md-book-card-meta' },
							attributes.publisher && el('span', { className: 'md-book-card-publisher' }, attributes.publisher),
							attributes.publishYear && el('span', { className: 'md-book-card-year' }, StacklessBlocks.cardIcon( 'calendar' ), ' ' + attributes.publishYear),
							attributes.pageCount > 0 && el('span', { className: 'md-book-card-pages' }, StacklessBlocks.icon( 'span', 'md-icon-file' ), ' ' + attributes.pageCount + ' ' + __( 'pages', 'stackless-theme' ))
						),
						attributes.description && el('p', { className: 'md-book-card-desc' }, attributes.description),
						attributes.subjects && attributes.subjects.length > 0 && el('div', { className: 'md-book-card-subjects' },
							attributes.subjects.map(function (subject, i) {
								return el('span', { key: 'subject-' + i, className: 'md-book-card-subject' }, subject);
							})
						),
						el('div', { className: 'md-book-card-actions' },
							attributes.openLibraryUrl && el('a', { href: '#', className: 'button' }, __( 'View on Open Library', 'stackless-theme' ))
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
