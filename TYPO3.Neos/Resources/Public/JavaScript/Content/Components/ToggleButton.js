/**
 * A button which has a "pressed" state
 */
define(
	[
		'emberjs',
		'./Button'
	],
	function (Ember, Button) {
		if (window._requirejsLoadingTrace) window._requirejsLoadingTrace.push('neos/content/ui/elements/toggle-button');

		return Button.extend({
			classNames: ['neos-button'],
			classNameBindings: ['pressed'],
			pressed: false,
			toggle: function() {
				this.set('pressed', !this.get('pressed'));
			},
			mouseUp: function(event) {
				if (this.get('isActive')) {
					var action = this.get('action'),
						target = this.get('target');

					this.toggle();
					if (target && action) {
						if (typeof action === 'string') {
							action = target[action];
						}
						action.call(target, this.get('pressed'), this);
					}

					this.set('isActive', false);
				}

				this._mouseDown = false;
				this._mouseEntered = false;
			}
		});
	}
);