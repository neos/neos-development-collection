/**
 * A button which has a "pressed" state
 */
define(
	[
		'emberjs',
		'phoenix/content/ui/elements/button'
	],
	function (Ember, Button) {
		if (window._requirejsLoadingTrace) window._requirejsLoadingTrace.push('phoenix/content/ui/elements/toggle-button');

		return Button.extend({
			classNames: ['t3-button'],
			classNameBindings: ['pressed'],
			pressed: false,
			toggle: function() {
				this.set('pressed', !this.get('pressed'));
			},
			mouseUp: function(event) {
				if (this.get('isActive')) {
					var action = this.get('action'),
						target = this.get('targetObject');

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