define(
[
	'emberjs',
	'Library/jquery-with-dependencies',
	'text!./PluginViewsEditor.html',
	'Content/Inspector/InspectorController',
	'Shared/HttpClient',
	'Shared/I18n'
],
function(
	Ember,
	$,
	template,
	InspectorController,
	HttpClient,
	I18n
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
			template: Ember.Handlebars.compile(I18n.translate('Main:TYPO3.Neos:loading', 'Loading ...'))
		}),
		itemViewClass: Ember.View.extend({
			template: Ember.Handlebars.compile(template)
		})
	});
});