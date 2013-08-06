define(
	[
		'emberjs',
		'text!./TreePanel.html',
		'./PageTree'
	], function(Ember, template, PageTree) {
		return Ember.View.extend({
			elementId: 'neos-tree-panel',

			template: Ember.Handlebars.compile(template),
			PageTree: PageTree
		});
	}
);
