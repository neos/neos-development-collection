define(
	[
		'emberjs',
		'Library/jquery-with-dependencies',
		'../Components/ToggleButton',
		'Shared/LocalStorage'
	], function(Ember, $, ToggleButton, LocalStorage) {
		return ToggleButton.extend({
			elementId: ['neos-menu-button'],
			title: 'Toggle menu',

			init: function() {
				this._super();
				if (LocalStorage.getItem('menuMode') !== false) {
					this.set('pressed', true);
				}
			},

			toggle: function() {
				var state = !this.get('pressed');
				this.set('pressed', state);
				LocalStorage.setItem('menuMode', state);
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