/**
 * A set of utility functions
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
		/**
		 * The following regular expression comes from http://tools.ietf.org/html/rfc4627 and checks if the JSON is valid
		 *
		 * @param {string} jsonString
		 * @return {boolean}
		 */
		isValidJsonString: function(jsonString) {
			return !/[^,:{}\[\]0-9.\-+Eaeflnr-u \n\r\t]/.test(jsonString.replace(/"(\\.|[^"\\])*"/g, ''));
		}
	}).create();
});