define(
[
	'Content/Components/ToggleButton'
],
function(ToggleButton) {
	return ToggleButton.extend({

		// mixed in...
		alohaComponent: null,

		_previousSetStateFn: null,

		didInsertElement: function() {
			var that = this,
				alohaComponent = this.get('alohaComponent');

			if (!alohaComponent.element) {
				return;
			}

			this.set('label', alohaComponent.element.find('.ui-button-text').text());
			this.set('pressed', alohaComponent.getState());

			this._previousSetStateFn = alohaComponent.setState;
			alohaComponent.setState = function(toggled) {
				that.set('pressed', toggled);
				that._previousSetStateFn.call(alohaComponent, toggled);
			};

			this._super();
		},

		willDestroyElement: function() {
			this.get('alohaComponent').setState = this._previousSetStateFn;
		},

		triggerAction: function(ctx) {
			this.get('alohaComponent')._onClick();
		},

		click: function(event) {
			event.preventDefault();
		}
	});
});