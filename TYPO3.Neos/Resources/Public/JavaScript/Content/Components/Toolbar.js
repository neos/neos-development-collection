/**
 * Toolbar which can contain other views. Has two areas, left and right.
 */
define(
	[
		'emberjs'
	], function(Ember) {
		return Ember.View.extend({
			tagName: 'div',
			classNames: ['neos-toolbar'],
			template: Ember.required()
		});
	}
);