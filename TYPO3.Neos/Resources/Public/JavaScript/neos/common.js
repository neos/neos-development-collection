/**
 * T3.Common
 *
 * Contains JavaScript which is needed in all modules
 */

define(
[
	'jquery',
	'emberjs',
	'bootstrap.alert',
	'bootstrap.notify'
],
function($, Ember) {
	if (window._requirejsLoadingTrace) window._requirejsLoadingTrace.push('neos/common');

	var T3 = window.T3 || {};
	T3.Common = {};

	/**
	 * Notification handler
	 *
	 * @singleton
	 */
	T3.Common.Notification = Ember.Object.extend({
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
			$('.t3-notification-container').notify({
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

	T3.Common.Util = Ember.Object.extend({
		isValidJsonString: function(jsonString) {
				// The following regular expression comes from http://tools.ietf.org/html/rfc4627 and checks if the JSON is valid
			return !/[^,:{}\[\]0-9.\-+Eaeflnr-u \n\r\t]/.test(jsonString.replace(/"(\\.|[^"\\])*"/g, ''));
		}
	}).create();

	/**
	 * Wrapper class for the localStorage, supporting storage of objects and arrays.
	 * Internally, all values are JSON encoded and decoded automatically.
	 */
	T3.Common.LocalStorage = Ember.Object.extend({
		/**
		* Get an item from localStorage
		*
		* @param {string} key Name of the value to get
		* @return {mixed} Depends on the stored value
		*/
		getItem: function (key) {
			if (!this._supportsLocalStorage()) return undefined;

			try {
				return JSON.parse(window.localStorage.getItem(key));
			} catch (e) {
				return undefined;
			}
		},

		/**
		* Set a value into localStorage
		*
		* @param {string} key
		* @param {mixed} value
		* @return {void}
		*/
		setItem: function (key, value) {
			if (!this._supportsLocalStorage()) return;
			window.localStorage.setItem(key, JSON.stringify(value));
		},

		/**
		* Remove a value form localStorage
		* @param {string} key
		* @return {void}
		*/
		removeItem: function (key) {
			if (!this._supportsLocalStorage()) return;
			window.localStorage.removeItem(key);
		},

		_supportsLocalStorage: function() {
			try {
				return 'localStorage' in window && window['localStorage'] !== null;
			} catch (e) {
				return false;
			}
		}
	}).create();

	window.T3 = T3;
});
