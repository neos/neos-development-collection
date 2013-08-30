/**
 * Notification handler
 */
define(
[
	'Library/jquery-with-dependencies',
	'emberjs'
],
function($, Ember) {
	/**
	 * Notification handler
	 *
	 * @singleton
	 */
	return Ember.Object.extend({
		_timeout: 5000,

		/**
		 * Shows a new notification
		 *
		 * @param {string} message
		 * @param {boolean} fadeout
		 * @param {string} type
		 * @private
		 * @return {void}
		 */
		_show: function(message, fadeout, type) {
			$('.neos-notification-container').notify({
				message: {
					html: message
				},
				type: type,
				fadeOut: {
					enabled: fadeout,
					delay: this.get('_timeout')
				}
			}).show();
		},

		/**
		 * Show ok message
		 *
		 * @param {string} message
		 * @return {void}
		 */
		ok: function(message) {
			this._show('<i class="icon-ok-sign"></i>' + message, true, 'success');
		},

		/**
		 * Show notice message
		 *
		 * @param {string} message
		 * @return {void}
		 */
		notice: function(message) {
			this._show('<i class="icon-info-sign"></i>' + message, true, 'info');
		},

		/**
		 * Show warning message
		 *
		 * @param {string} message
		 * @return {void}
		 */
		warning: function(message) {
			this._show('<i class="icon-warning-sign"></i>' + message, false, 'warning');
		},

		/**
		 * Show error message
		 *
		 * @param {string} message
		 * @return {void}
		 */
		error: function(message) {
			this._show('<i class="icon-exclamation-sign"></i>' + message, false, 'error');
		}
	}).create();
});