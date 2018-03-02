define(
[
	'Content/Components/Button'
],
function(Button) {
	return Button.extend({
		// mixed in...
		alohaComponent: null,

		didInsertElement: function() {
			var buttonElement = this.get('alohaComponent').element;
			if (!buttonElement) {
				return;
			}
			this.set('label', buttonElement.find('.ui-button-text').text());
			this._super();
		},
		triggerAction: function() {
			this.get('alohaComponent')._onClick();
		},
		click: function(event) {
			event.preventDefault();
		}
	});
});