define(
	[
		'emberjs',
		'text!./TreePanel.html',
		'./PageTree'
	], function(Ember, template, PageTree) {
		return Ember.View.extend({
			elementId: 't3-tree-panel',

			template: Ember.Handlebars.compile(template),
			PageTree: PageTree
		});
	}
);
