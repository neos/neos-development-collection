/**
 * Loading indicator used to show a loading progress bar.
 */
define([
	'emberjs',
	'Library/nprogress'
], function(
	Ember,
	NProgress
) {
	/**
	 * @singleton
	 */
	return Ember.Object.extend({
		settings: {
			minimum: 0.01,
			speed: 200,
			trickleRate: 0.02,
			trickleSpeed: 400,
			template: '<div class="neos-bar" role="bar"><div class="neos-peg"></div><div class="neos-peg-end"></div></div>'
		},

		/**
		 * @return {void}
		 */
		init: function() {
			NProgress.configure(this.get('settings'));
		},

		/**
		 * @return {void}
		 */
		start: function() {
			NProgress.set(0);
			NProgress.start();
		},

		/**
		 * @return {void}
		 */
		done: function() {
			NProgress.done();
			this.reset();
		},

		/**
		 * @param {number} percentage
		 * @param {number} speed
		 * @return {void}
		 */
		set: function(percentage, speed) {
			if (typeof speed !== 'undefined') {
				NProgress.configure({speed: speed});
			}
			NProgress.set(percentage);
		},

		/**
		 * @return {void}
		 */
		increment: function() {
			NProgress.inc();
		},

		/**
		 * @return {void}
		 */
		stop: function() {
			NProgress.remove();
			this.reset();
		},

		/**
		 * @return {void}
		 */
		reset: function() {
			NProgress.configure(this.get('settings'));
		}
	}).create();
});