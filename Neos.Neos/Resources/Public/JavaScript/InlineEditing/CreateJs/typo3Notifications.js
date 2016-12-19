/**
 * Wrapper for midgard notifications
 * TODO: Remove with resolving #45049
 */
define(
	[
		'Library/jquery-with-dependencies',
		'Shared/Configuration'
	],
	function($, Configuration) {
		$.widget('Midgard.midgardNotifications', {
			create: function (options) {
				if (Configuration.get('DevelopmentMode')) {
					console.log('Storage plugin', options.body);
				}
			}
		});
	}
);