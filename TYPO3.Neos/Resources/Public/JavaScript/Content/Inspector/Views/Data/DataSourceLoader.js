define(
[
	'Library/jquery-with-dependencies',
	'emberjs',
	'Content/Inspector/InspectorController',
	'Shared/HttpClient'
],
function(
	$,
	Ember,
	InspectorController,
	HttpClient
) {
	/**
	 * A mixin for widgets that load data from a (generic) data source
	 */
	return Ember.Mixin.create({
		// The loaded data
		data: null,
		// Data source identifier
		dataSource: null,
		// Arguments for the data source
		arguments: null,

		isLoading: true,

		// Load data from the data source
		_loadData: function() {
			var that = this,
				nodePath = InspectorController.nodeSelection.get('selectedNode.nodePath');

			var dataSourceUri = HttpClient._getEndpointUrl('neos-data-source') + '/' + this.get('dataSource');

			var arguments = this.get('arguments') || {};
			arguments.node = nodePath;

			HttpClient.getResource(dataSourceUri + '?' + $.param(arguments), {dataType: 'json'}).then(
				function(results) {
					if (results.error) {
						that.set('error', results.error);
					} else {
						that.set('data', results.data);
					}
					that.set('isLoading', false);
				}
			);
		}.on('init')
	});
});