define(
[
	'Library/jquery-with-dependencies',
	'text!./PluginViewsEditor.html',
	'Content/Inspector/InspectorController'
],
function(
	$,
	template,
	InspectorController
) {
	return Ember.CollectionView.extend({
		tagName: 'ul',
		classNames: ['neos-inspector-list-stacked'],
		content: null,
		init: function() {
			var nodePath = InspectorController.nodeSelection.get('selectedNode.nodePath');
			var that = this;
			$.getJSON('/neos/content/pluginViews?node=' + nodePath, function(views) {
				Ember.run(function() {
					var viewsArray = [];
					for (var viewName in views) {
						viewsArray.push(views[viewName]);
					}
					that.set('content', viewsArray);
					that.rerender();
				});
			});
			return this._super();
		},
		emptyView: Ember.View.extend({
			template: Ember.Handlebars.compile("Loading ...")
		}),
		itemViewClass: Ember.View.extend({
			template: Ember.Handlebars.compile(template)
		})
	});
});
