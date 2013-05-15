/**
 * Wrapper for midgard notifications
 * TODO: Remove with resolving #45049
 */
define(['Library/jquery-with-dependencies'], function(jQuery) {
	jQuery.widget('Midgard.midgardNotifications', {
		create: function (options) {
			if (T3.Configuration.DevelopmentMode) {
				console.log('Storage plugin', options.body);
			}
		}
	});
});