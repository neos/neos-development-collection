/**
 */
define(
	[
		'text!phoenix/templates/content/ui/contentelementHandles.html'
	],
	function (template) {
		if (window._requirejsLoadingTrace) window._requirejsLoadingTrace.push('phoenix/content/ui/contentelement-handles');

		return Ember.View.extend({
			template: Ember.Handlebars.compile(template),

			_element: null,

			_collection: null,

			didInsertElement: function() {
			}
		});
	}
);