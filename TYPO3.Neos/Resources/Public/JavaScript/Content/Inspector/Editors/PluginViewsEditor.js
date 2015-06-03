define(
[
	'Library/jquery-with-dependencies',
	'text!./PluginViewsEditor.html',
	'Content/Inspector/InspectorController',
	'Shared/HttpClient'
],
function(
	$,
	template,
	InspectorController,
	HttpClient
) {
	return Ember.CollectionView.extend({
		tagName: 'ul',
		classNames: ['neos-inspector-list-stacked'],
		content: null,
		init: function() {
			var that = this,
				nodePath = InspectorController.nodeSelection.get('selectedNode.nodePath');

			HttpClient.getResource(
				$('link[rel="neos-pluginviews"]').attr('href') + '?node=' + nodePath,
				{dataType: 'json'}
			).then(
				function(views) {
					var viewsArray = [];
					for (var viewName in views) {
						viewsArray.push(views[viewName]);
					}
					that.set('content', viewsArray);
					that.rerender();
				}
			);
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