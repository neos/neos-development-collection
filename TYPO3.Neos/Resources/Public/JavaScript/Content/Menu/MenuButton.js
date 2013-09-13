define(
	[
		'emberjs',
		'Library/jquery-with-dependencies',
		'../Components/ToggleButton'
	], function(Ember, $, ToggleButton) {
		return ToggleButton.extend({
			elementId: ['neos-menu-button'],
			title: 'Toggle menu',
			toggleStateName: 'pressed',

			mouseEnter: function() {
				if (!this.get(this.toggleStateName)) {
					this.toggleState(this.toggleStateName);
				}
			},

			mouseLeave: function() {
				if ($('#neos-menu-panel:hover').length !== 0) {
					this.toggleState(true);
				} else {
					this.toggleState(false);
				}
			},

			toggle: function() {
				this.toggleState(this.toggleStateName);
			},

			toggleState: function(state) {
				if (state === this.toggleStateName) {
					state = !this.get(this.toggleStateName);
				}
				this.set(this.toggleStateName, state);
			},

			_onPressedChanged: function() {
				if (this.get(this.toggleStateName) === true) {
					$('body').addClass('neos-menu-panel-open');
				} else {
					$('body').removeClass('neos-menu-panel-open');
				}
			}.observes('pressed')
		}).create().appendTo('#neos-top-bar');
	}
);