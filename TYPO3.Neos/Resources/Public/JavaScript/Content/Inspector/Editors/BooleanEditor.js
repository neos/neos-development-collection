define(
[
	'emberjs',
	'Library/jquery-with-dependencies'
],
function(Ember, $) {
	return Ember.Checkbox.extend({
		_checkedDidChange: function() {
			if (this.get('value') !== this.get('checked')) {
				this.set('value', this.get('checked'));
			}
		}.observes('checked'),

		_valueDidChange: function() {
			if (this.get('value') !== this.get('checked')) {
				this.set('checked', this.get('value'));
			}
		}.observes('value'),

		didInsertElement: function() {
			this.$().after($('<span />'));
		}
	});
});
