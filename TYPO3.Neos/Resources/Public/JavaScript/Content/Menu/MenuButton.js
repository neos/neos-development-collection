define(
	[
		'emberjs',
		'Library/jquery-with-dependencies',
		'../Components/Button',
		'./MenuPanelController'
	], function(Ember, $, Button, MenuPanelController) {
		return Button.extend({
			elementId: ['neos-menu-button'],
			title: 'Toggle menu',
			classNames: ['neos-button'],
			classNameBindings: ['controller.menuPanelMode:neos-pressed'],

			controller: MenuPanelController,

			triggerAction: function() {
				this.toggleProperty('controller.menuPanelMode');
			},

			mouseEnter: function() {
				this._super();
				this.set('controller.menuPanelMode', true);
			},

			mouseLeave: function() {
				this._super();
				if ($('#neos-menu-panel:hover').length !== 0) {
					this.set('controller.menuPanelMode', false);
				}
			}
		});
	}
);