/**
 * LocalStorage, supporting storage of objects and arrays.
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
		_supportsLocalStorageResult: null,

		/**
		 * Get an item from localStorage
		 *
		 * @param {string} key Name of the value to get
		 * @return {mixed} Depends on the stored value
		 */
		getItem: function(key) {
			if (!this._supportsLocalStorage()) {
				return;
			}

			try {
				return JSON.parse(window.localStorage.getItem(key));
			} catch (e) {
				return undefined;
			}
		},

		/**
		 * Set a value into local storage
		 *
		 * @param {string} key
		 * @param {mixed} value
		 * @return {void}
		 */
		setItem: function(key, value) {
			if (!this._supportsLocalStorage()) {
				return;
			}
			window.localStorage.setItem(key, JSON.stringify(value));
		},

		/**
		 * Remove a value form local storage
		 *
		 * @param {string} key
		 * @return {void}
		 */
		removeItem: function(key) {
			if (!this._supportsLocalStorage()) {
				return;
			}
			window.localStorage.removeItem(key);
		},

		/**
		 * Check if the browser supports local storage
		 * Inspired by https://github.com/Modernizr/Modernizr/blob/master/feature-detects/storage/localstorage.js
		 *
		 * @return {boolean}
		 */
		_supportsLocalStorage: function() {
			var supportsLocalStorageResult = this.get('_supportsLocalStorageResult');
			if (supportsLocalStorageResult !== null) {
				return supportsLocalStorageResult;
			}
			var test = 'localStorage',
				result;
			try {
				localStorage.setItem(test, test);
				localStorage.removeItem(test);
				result = true;
			} catch(e) {
				result = false;
			}
			this.set('_supportsLocalStorageResult', result);
			return result;
		}
	}).create();
});