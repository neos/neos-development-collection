define(
[
	'emberjs'
],
function(Ember) {
	return Ember.TextArea.extend({
		validators: {},
		rows: 5,

		focusOut: function() {
			// Reset scroll after focus out
			this.$().get(0).scrollTop = 0;
		},

		/**
		 * When pressing Enter inside the text area, we prevent propagation to avoid submitting the form
		 */
		keyDown: function(event) {
			if (event.keyCode === 13) {
				event.stopPropagation();
			}
		}
	});
});