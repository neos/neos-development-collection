/**
 * An object containing general configuration
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
		init: function() {
			this.setProperties(window.T3Configuration);
			delete window.T3Configuration;
		}
	}).create();
});