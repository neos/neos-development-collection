/**
 * Context bar which can contain other views.
 */
define(
	[
		'emberjs'
	], function(Ember) {
		return Ember.View.extend({
			tagName: 'div',
			elementId: ['neos-context-bar'],
			template: Ember.required()
		});
	}
);