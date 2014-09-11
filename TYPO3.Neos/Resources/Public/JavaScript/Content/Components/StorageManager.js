define(
[
	'emberjs',
	'Library/jquery-with-dependencies'
],
function(Ember, $) {
	return Ember.Object.create({
		savePending: false,
		changes: null,

		start: function() {
			var that = this;
			setInterval(function() {
				that.set('savePending', that.get('changes').length > 0);
			}, 250);
		},

		persist: function() {
			if (this.get('savePending')) {
				$('body').data('Midgard-midgardStorage').element.midgardStorage('saveRemoteAll');
			}
		}
	});
});