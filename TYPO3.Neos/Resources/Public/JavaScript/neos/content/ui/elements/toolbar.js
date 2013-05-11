/**
 * Toolbar which can contain other views. Has two areas, left and right.
 */
define(
	[
		'emberjs'
	], function(Ember) {
		if (window._requirejsLoadingTrace) window._requirejsLoadingTrace.push('neos/content/ui/elements/toolbar');

		return Ember.View.extend({
			tagName: 'div',
			classNames: ['t3-toolbar', 't3-ui'],
			template: Ember.required()
		});

	}
);