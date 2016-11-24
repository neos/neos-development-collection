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
				nodeIdentifier = InspectorController.get('nodeProperties._identifier');

			HttpClient.getResource(
				$('link[rel="neos-pluginviews"]').attr('href'),
				{
					data: {
						identifier: nodeIdentifier,
						workspaceName: $('#neos-document-metadata').data('neos-context-workspace-name'),
						dimensions: $('#neos-document-metadata').data('neos-context-dimensions')
					},
					dataType: 'json'
				}
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
			template: Ember.Handlebars.compile(I18n.translate('Neos.Neos:Main:loading', 'Loading') + ' ...')
		}),

		itemViewClass: Ember.View.extend({
			template: Ember.Handlebars.compile(template)
		})
	});
});
