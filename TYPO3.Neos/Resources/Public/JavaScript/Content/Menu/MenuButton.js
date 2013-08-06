define(
	[
		'emberjs',
		'Library/jquery-with-dependencies',
		'../Components/ToggleButton'
	], function(Ember, $, ToggleButton) {
		return ToggleButton.extend({
			elementId: ['neos-menu-button'],
			title: 'Toggle menu',

			toggle: function() {
				var state = !this.get('pressed');
				this.set('pressed', state);
			},

			_onPressedChanged: function() {
				if (this.get('pressed') === true) {
					$('body').addClass('neos-menu-panel-open');
				} else {
					$('body').removeClass('neos-menu-panel-open');
				}
			}.observes('pressed')
		});
	}
);