define(
[
	'Library/jquery-with-dependencies',
	'emberjs'
],
function($, Ember) {
	return Ember.TextField.extend({
		classNames: ['neos-input-small'],
		didInsertElement: function() {
			this.$().attr('placeholder', 'No date set');

			this.$().datepicker({
				dateFormat: $.datepicker.W3C,
				beforeShow: function(field, datePicker) {
					$(datePicker.dpDiv).addClass('neos');
				}
			});
		}
	});
});