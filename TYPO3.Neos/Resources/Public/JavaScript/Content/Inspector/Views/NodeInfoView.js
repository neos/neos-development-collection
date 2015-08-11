define([
	'Library/jquery-with-dependencies',
	'emberjs',
	'text!./NodeInfoView.html'
],
function(
	$,
	Ember,
	template
) {
	Ember.Handlebars.registerBoundHelper('formatDate', function(value) {
		return new Date(value).toISOString().slice(0, 16).replace('T', ' ');
	});
	return Ember.View.extend({
		template: Ember.Handlebars.compile(template)
	});
});