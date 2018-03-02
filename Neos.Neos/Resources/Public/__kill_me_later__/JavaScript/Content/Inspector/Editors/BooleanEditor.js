define(
[
	'emberjs',
	'Library/jquery-with-dependencies'
],
function(Ember, $) {
	return Ember.Checkbox.extend({
		/**
		 * The value attribute of Ember.Checkbox is renamed to 'checked' in Emberjs 0.9.7
		 * This method is a wrapper to make sure this Editor object still has a value property.
		 */
		value: function(key, value) {
			if (arguments.length === 2) {
				this.set('checked', value);
				return value;
			} else {
				return this.get('checked');
			}
		}.property('checked'),

		didInsertElement: function() {
			this.$().after($('<span />'));
		}
	});
});