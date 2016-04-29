define(
	[
		'emberjs',
		'Library/jquery-with-dependencies',
		'../Components/Button',
		'./MenuPanelController',
		'Shared/I18n'
	], function(Ember, $, Button, MenuPanelController, I18n) {
		return Button.extend({
			elementId: ['neos-menu-button'],
			classNames: ['neos-button'],
			classNameBindings: ['controller.menuPanelMode:neos-pressed'],


			controller: MenuPanelController,

			init: function() {
				this.set('title', I18n.translate('toggleMenu', 'Toggle menu'));
				if (this.get('controller.configuration.menuPanelStickyMode') && this.get('controller.configuration.isMenuPanelStickyModeShown')) {
					this.toggleProperty('controller.menuPanelMode');
				}
			},

			mouseDown: function() {
				this._super();

				if (this.get('controller.menuPanelStickyMode') === false) {
					this.toggleProperty('controller.menuPanelMode');
				}

			},

			touchStart: function(event) {
				this._super();

				// On mobile devices we need to prevent ghost clicks, otherwise the `MenuPanel` will be
				// closed immediately after opening. `preventDefault()` will also prevent the user to
				// start a page scroll on the `MenuButton` but this is ok for the `MenuButton`.
				//
				// For more information see
				// http://ariatemplates.com/blog/2014/05/ghost-clicks-in-mobile-browsers/
				event.preventDefault();
			},

			didInsertElement: function() {
				this.$().tooltip({placement: 'right'});
			}
		});
	}
);
