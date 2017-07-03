define(
[
	'Library/jquery-with-dependencies',
	'emberjs',
	'Content/Inspector/Views/Widget',
	'Content/Inspector/Views/Data/DataSourceLoader',
	'text!./TimeSeriesView.html',
	'Shared/Charts/TimeSeriesChart'
],
function(
	$,
	Ember,
	Widget,
	DataSourceLoader,
	template,
	TimeSeriesChart
) {
	return Widget.extend(DataSourceLoader, {
		template: Ember.Handlebars.compile(template),
		classNames: ['neos-timeseriesview'],
		TimeSeriesChart: TimeSeriesChart,

		// Additional chart definition
		chart: null,

		_lineData: function() {
			var seriesValues = [],
				data = this.get('data');
			if (!data || !this.get('collection')) {
				return [];
			}
			var collection = Ember.get(data, this.get('collection')),
				series = this.get('series'),
				dateformat = d3.time.format('%Y-%m-%d');
			$.each(collection, function() {
				var row = this;
				var entry = {
					time: dateformat.parse(Ember.get(row, series.timeData)),
					value: parseInt(Ember.get(row, series.valueData))
				};
				seriesValues.push(entry);
			});
			return seriesValues;
		}.property('series', 'data', 'collection')
	});
});