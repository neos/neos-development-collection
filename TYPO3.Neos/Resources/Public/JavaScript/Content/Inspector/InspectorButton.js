define(
	[
		'emberjs',
		'../Components/ToggleButton',
		'Shared/LocalStorage'
	], function(Ember, ToggleButton, LocalStorage) {
		return ToggleButton.extend({
			elementId: ['neos-inspector-button'],
			title: 'Toggle inspector',

			init: function() {
				if (LocalStorage.getItem('inspectorMode') !== false) {
					this.set('pressed', true);
				}
			},

			toggle: function() {
				var state = !this.get('pressed');
				this.set('pressed', state);
				LocalStorage.setItem('inspectorMode', state);
			},

			_onPressedChanged: function() {
				if (this.get('pressed') === true) {
					$('body').addClass('neos-inspector-panel-open');
				} else {
					$('body').removeClass('neos-inspector-panel-open');
				}
			}.observes('pressed')
		});
	}
);