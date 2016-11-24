/**
 * A button which has a "pressed" state
 */
define(
	[
		'emberjs',
		'./Button'
	],
	function (Ember, Button) {
		return Button.extend({
			classNameBindings: ['pressed:neos-pressed'],
			pressed: false,

			toggle: function() {
				this.set('pressed', !this.get('pressed'));
			},

			mouseUp: function(event) {
				if (this.get('isActive')) {
					// Actually invoke the button's target and action.
					// This method comes from the Ember.TargetActionSupport mixin.
					this.toggle();
					this.triggerAction();
					this.set('isActive', false);
				}

				this._mouseDown = false;
				this._mouseEntered = false;
				return this.get('propagateEvents');
			}
		});
	}
);