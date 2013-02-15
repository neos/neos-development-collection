/**
 * Toolbar which can contain other views. Has two areas, left and right.
 */
define(
	[
		'emberjs',
		'text!neos/templates/content/ui/toolbar.html'
	], function(Ember, toolbarTemplate) {
		if (window._requirejsLoadingTrace) window._requirejsLoadingTrace.push('neos/content/ui/elements/toolbar');

		return Ember.View.extend({
			tagName: 'div',
			classNames: ['t3-toolbar', 't3-ui'],
			template: Ember.Handlebars.compile(toolbarTemplate)
		});

	}
);