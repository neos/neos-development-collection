define(
[
	'Library/jquery-with-dependencies',
	'Library/underscore',
	'emberjs',
	'Content/Inspector/Views/Widget',
	'Content/Inspector/Views/Data/DataSourceLoader',
	'text!./TableView.html'
],
function(
	$,
	_,
	Ember,
	Widget,
	DataSourceLoader,
	template
) {
	/**
	 * Widget that displays data in a table with support for icons
	 */
	return Widget.extend(DataSourceLoader, {
		template: Ember.Handlebars.compile(template),

		classNames: ['neos-tableview'],

		// Column definitions for the table
		columns: null,

		_rowsValues: function() {
			var data = this.get('data'),
				collectionPath = this.get('collection');
			if (!data || !collectionPath) {
				return [];
			}
			var collection = Ember.get(data, collectionPath),
				columns = this.get('columns');
			return _.map(collection, function(row) {
				return _.map(columns, function(column) {
					var rowValue = {
						value: Ember.get(row, column.data),
						suffix: column.suffix
					};
					if (column.iconMap) {
						rowValue.icon = column.iconMap[rowValue.value];
					}
					return rowValue;
				});
			});
		}.property('columns', 'data', 'collection')
	});
});