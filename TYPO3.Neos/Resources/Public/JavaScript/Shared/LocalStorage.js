/**
 * LocalStorage, supporting storage of objects and arrays.
 * Internally, all values are JSON encoded and decoded automatically.
 */
define(
[
	'emberjs'
],
function(Ember) {
	return Ember.Object.extend({
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
			var test = 'localStorage';
			try {
				localStorage.setItem(test, test);
				localStorage.removeItem(test);
				return true;
			} catch(e) {
				return false;
			}
		}
	}).create();
});