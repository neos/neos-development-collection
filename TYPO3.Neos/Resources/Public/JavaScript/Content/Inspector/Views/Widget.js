define(
[
	'Library/jquery-with-dependencies',
	'emberjs',
	'text!./Widget.html'
],
function(
	$,
	Ember,
	template
) {
	/**
	 * A widget base class
	 */
	return Ember.View.extend({
		template: null,
		layout: Ember.Handlebars.compile(template),

		classNames: ['widget'],

		// Icon for the widget header
		icon: null,
		// Label for the widget header
		label: null,
		// Optional subtitle in the header
		subtitle: null,
		// Loading state for showing a loading indicator, provide a computed property in implementations
		isLoading: false,
		// A possible error on the widget
		error: null
	});
});