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

			init: function() {
				if (this.get('controller.configuration.menuPanelStickyMode') && this.get('controller.configuration.isMenuPanelStickyModeShown')) {
					this.toggleProperty('controller.menuPanelMode');
				}
			},

			triggerAction: function() {
				this.toggleProperty('controller.menuPanelMode');
				if (this.get('controller.configuration.menuPanelStickyMode')) {
					this.toggleProperty('controller.isMenuPanelStickyModeShown');
				}
			},

			mouseEnter: function() {
				this._super();

				if (this.get('controller.menuPanelStickyMode') === false) {
					this.set('controller.menuPanelMode', true);
				}
			},

			mouseLeave: function() {
				this._super();

				var that = this;
				if (this.get('controller.menuPanelStickyMode') === false) {
						// Defer the check of the hover state as some browser will not update the hover status synchronously
					setTimeout(function() {
						// Check if one of the child elements have the hover state.
						// This fix a opera problem with the :hover on the container only
						if (that.get('controller.menuPanelMode') === true && $('#neos-menu-panel *:hover').length === 0) {
							that.set('controller.menuPanelMode', false);
						}
					}, 0);
				}
			}
		});
	}
);