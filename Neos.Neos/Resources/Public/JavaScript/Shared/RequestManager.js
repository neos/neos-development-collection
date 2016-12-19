/**
 * A request manager for keeping an overview of current requests.
 */
define([
	'emberjs'
], function(
	Ember
) {
	/**
	 * @singleton
	 */
	return Ember.Object.extend({
		requests: Ember.A(),
		requestInProgress: false,

		/**
		 * Adds a listener to the "beforeunload" event to show an alert if requests are in progress when navigating away from the page.
		 */
		init: function() {
			var that = this;
			window.addEventListener('beforeunload', function(e) {
				if (that.get('requestInProgress')) {
					e.preventDefault();
					e.returnValue = 'A request is in progress.';
				}
			});
		},

		/**
		 * @param {xhr} jQuery ajax object
		 * @return {void}
		 */
		add: function(xhr) {
			this.get('requests').pushObject(xhr);
		},

		/**
		 * @param {xhr} jQuery ajax object
		 * @return {void}
		 */
		remove: function(xhr) {
			this.get('requests').removeObject(xhr);
		},

		_requestsDidChange: function() {
			this.set('requestInProgress', this.get('requests').length > 0);
		}.observes('requests.@each')
	}).create();
});