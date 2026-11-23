/**
 * Shared helpers for the Stackless block editor scripts.
 *
 * wp_localize_script() prints the StacklessBlocks object (colors, popups,
 * icons, iconSvg) before this file runs, so everything here extends that
 * object and never replaces it.
 */
( function ( wp ) {
	var el = wp.element.createElement;

	window.StacklessBlocks = window.StacklessBlocks || {};

	var SB = window.StacklessBlocks;

	// The block scripts read these when they load. Fill in any key that was
	// not localized so a missing one cannot stop every block from registering.
	SB.icons       = SB.icons || [];
	SB.iconSvg     = SB.iconSvg || {};
	SB.popups      = SB.popups || [];
	SB.colors      = SB.colors || {};
	SB.colors.slug = SB.colors.slug || {};
	SB.colors.hex  = SB.colors.hex || {};

	/**
	 * Build an icon element for an editor preview.
	 *
	 * Stored icon values are icon-font class names such as `md-icon-bolt`.
	 * There is no icon font here, so the matching inline SVG is printed inside
	 * the element that carries the class. An unknown value still returns the
	 * element with its classes, only empty.
	 *
	 * @param {string} tag        Element tag, e.g. 'i' or 'span'.
	 * @param {string} value      Icon class, e.g. 'md-icon-bolt'.
	 * @param {string} className  Optional extra classes, printed before the icon class.
	 * @param {Object} extraProps Optional extra props, e.g. { style: { color: '#000' } }.
	 * @return {Object} Element.
	 */
	SB.icon = function ( tag, value, className, extraProps ) {
		var props = {},
			icon  = value || '',
			svg   = icon ? SB.iconSvg[ icon ] : '',
			key;

		if ( extraProps ) {
			for ( key in extraProps ) {
				if ( Object.prototype.hasOwnProperty.call( extraProps, key ) ) {
					props[ key ] = extraProps[ key ];
				}
			}
		}

		props.className      = ( className ? className + ' ' : '' ) + icon;
		props['aria-hidden'] = 'true';

		if ( svg ) {
			props.dangerouslySetInnerHTML = { __html: svg };
		}

		return el( tag, props );
	};
} )( window.wp );
