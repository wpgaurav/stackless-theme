/**
 * Customizer controls: the font settings preview without a reload, so the "custom stack" fields
 * follow their font choice here instead of waiting on the preview to report them active.
 */
(function (api) {
	'use strict';

	if (!api) {
		return;
	}

	['body', 'heading'].forEach(function (where) {
		var id = 'stackless_font_' + where;
		api(id, function (setting) {
			api.control(id + '_custom', function (control) {
				var sync = function () {
					control.active.set(setting.get() === 'custom');
				};
				setting.bind(sync);
			});
		});
	});
})(window.wp && window.wp.customize);
