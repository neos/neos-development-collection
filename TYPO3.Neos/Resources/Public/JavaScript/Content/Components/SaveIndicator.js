define(
[
	'emberjs',
	'text!./SaveIndicator.html'
],
function(Ember, template) {
	return Ember.View.extend({
		tag: 'span',
		template: Ember.Handlebars.compile(template),
		saveRunning: false,
		lastSuccessfulTransfer: null,

		lastSuccessfulTransferLabel: function() {
			var date = this.get('lastSuccessfulTransfer');
			if (date !== null) {
				function pad(n) {
					return n < 10 ? '0' + n : n;
				}
				return 'Saved at ' + pad(date.getHours()) + ':' + pad(date.getMinutes()) + ':' + pad(date.getSeconds());
			}
			return '';
		}.property('lastSuccessfulTransfer')
	});
});