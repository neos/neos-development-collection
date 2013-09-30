/**
 * SessionStorage, supporting storage of objects and arrays.
 * Internally, all values are JSON encoded and decoded automatically.
 */
define(
[
	'emberjs'
],
function(Ember) {
	/**
	 * @singleton
	 */
	return Ember.Object.extend({
		_supportsSessionStorageResult: null,

		/**
		 * Get an item from sessionStorage
		 *
		 * @param {string} key Name of the value to get
		 * @return {mixed} Depends on the stored value
		 */
		getItem: function(key) {
			if (!this._supportsSessionStorage()) {
				return;
			}

			try {
				return JSON.parse(window.sessionStorage.getItem(key));
			} catch (e) {
				return undefined;
			}
		},

		/**
		 * Set a value into session storage
		 *
		 * @param {string} key
		 * @param {mixed} value
		 * @return {void}
		 */
		setItem: function(key, value) {
			if (!this._supportsSessionStorage()) {
				return;
			}
			try {
				window.sessionStorage.setItem(key, JSON.stringify(value));
			} catch (e) {
				// Clear the session storage in case an quota error is thrown
				window.sessionStorage.clear();
				window.sessionStorage.setItem(key, JSON.stringify(value));
			}
		},

		/**
		 * Remove a value form session storage
		 *
		 * @param {string} key
		 * @return {void}
		 */
		removeItem: function(key) {
			if (!this._supportsSessionStorage()) {
				return;
			}
			window.sessionStorage.removeItem(key);
		},

		/**
		 * Check if the browser supports session storage
		 * Inspired by https://github.com/Modernizr/Modernizr/blob/master/feature-detects/storage/sessionstorage.js
		 *
		 * @return {boolean}
		 */
		_supportsSessionStorage: function() {
			var supportsSessionStorageResult = this.get('_supportsSessionStorageResult');
			if (supportsSessionStorageResult !== null) {
				return supportsSessionStorageResult;
			}
			var test = 'sessionStorage',
				result;
			try {
				sessionStorage.setItem(test, test);
				sessionStorage.removeItem(test);
				result = true;
			} catch(e) {
				result = false;
			}
			this.set('_supportsSessionStorageResult', result);
			return result;
		}
	}).create();
});