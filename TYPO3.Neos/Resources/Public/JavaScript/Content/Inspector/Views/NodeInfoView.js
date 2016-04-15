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
		function pad(n) {
			return n < 10 ? '0' + n : n;
		}
		function formatDate(date) {
			var Y = date.getFullYear().toString();
			var m = (date.getMonth() + 1).toString();
			var d = date.getDate().toString();
			var H = date.getHours().toString();
			var i = date.getMinutes().toString();
			return Y + '-' + pad(m) + '-' + pad(d) + ' ' + pad(H) + ':' + pad(i);
		}

		return formatDate(new Date(value));
	});
	return Ember.View.extend({
		template: Ember.Handlebars.compile(template)
	});
});