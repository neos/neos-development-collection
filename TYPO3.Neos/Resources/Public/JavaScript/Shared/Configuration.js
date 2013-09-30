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
		TYPO3_NAMESPACE: 'http://www.typo3.org/ns/2012/Flow/Packages/Neos/Content/',

		init: function() {
			this.setProperties(window.T3Configuration);
			delete window.T3Configuration;
		}
	}).create();
});