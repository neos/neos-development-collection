define(
[
	'Library/jquery-with-dependencies',
	'emberjs',
	'Content/Inspector/Views/Widget',
	'Content/Inspector/Views/Data/DataSourceLoader',
	'text!./ColumnView.html'
],
function(
	$,
	Ember,
	Widget,
	DataSourceLoader,
	template
) {
	/**
	 * Widget that displays data in columns with an optional large hero column
	 */
	return Widget.extend(DataSourceLoader, {
		template: Ember.Handlebars.compile(template),

		classNames: ['neos-columnview'],
		classNameBindings: ['_columnsClass'],

		// Column definitions
		columns: null,
		// Single, large hero definition
		hero: null,

		_columnValues: function() {
			var columnValues = [],
				data = this.get('data'),
				columns = this.get('columns');
			if (!data) {
				return [];
			}
			$.each(columns, function() {
				columnValues.push($.extend({
					value: Ember.get(data, this.data)
				}, this));
			});
			return columnValues;
		}.property('columns', 'data'),

		_heroValue: function() {
			var data = this.get('data'),
				hero = this.get('hero');
			if (data && hero) {
				return {
					label: hero.label,
					value: Ember.get(data, hero.data)
				};
			} else {
				return null;
			}
		}.property('hero', 'data'),

		_columnsClass: function() {
			var columns = this.get('columns');
			return columns && columns.length ? 'neos-columnview-columns-' + columns.length : '';
		}.property('columns')
	});
});