define(
	[
		'emberjs',
		'Library/jquery-with-dependencies',
		'Shared/LocalStorage',
		'Shared/ResourceCache',
		'Shared/Configuration',
		'./MenuPanelController',
		'text!./MenuPanel.html'
	], function(Ember, $, LocalStorage, ResourceCache, Configuration, MenuPanelController, template) {

		return Ember.View.extend({
			elementId: 'neos-menu-panel',
			template: Ember.Handlebars.compile(template),
			classNameBindings: ['_menuIsSticky:neos-menu-sticky-on:neos-menu-sticky'],
			_menuIsSticky: false,
			controller: MenuPanelController,

			StickyMenuButton: Ember.View.extend({
				tagName: 'div',
				classNames: ['neos-button', 'neos-menu-stickybutton'],
				classNameBindings: ['_menuIsSticky:neos-menu-stickybutton neos-pressed:neos-menu-stickybutton'],
				_menuIsSticky: null,

				didInsertElement: function() {
					var stickyMenuState = this.get('controller.configuration.stickyMenuState');
					if (stickyMenuState === undefined || stickyMenuState === null) {
						stickyMenuState = false;
						this.set('controller.configuration.stickyMenuState', stickyMenuState);
					}
					this.set('_menuIsSticky', stickyMenuState);
					this.set('controller.stickyMenuPanelMode', stickyMenuState);
				},

				click: function() {
					var stickyMenuState = this.get('controller').toggleStickyMenu();
					this.set('_menuIsSticky', stickyMenuState);
					this.set('controller.stickyMenuPanelMode', stickyMenuState);
				},

				onStickyMenuModeChanged: function() {
					if (this.get('controller.stickyMenuPanelMode')) {
						$('body').addClass('neos-menu-sticky-on');
					} else {
						$('body').removeClass('neos-menu-sticky-on');
					}
				}.observes('controller.stickyMenuPanelMode')
			}),

			ToggleMenuPanelHeadline: Ember.View.extend({
				tagName: 'div',
				classNameBindings: ['collapsed:neos-collapsed:neos-open'],
				_collapsed: false,
				_menuIsSticky: null,

				collapsed: function() {
					if(this.get('controller.configuration.stickyMenuState')) {
						return false;
					} else {
						return this.get('_collapsed');
					}
				}.property('_collapsed', 'controller.configuration.stickyMenuState'),

				// bound in handlebar
				group: undefined,

				didInsertElement: function() {
					var menuGroup = this.get('group'),
						collapsed = this.get('controller.configuration.' + menuGroup);

					if (typeof menuGroup !== 'undefined') {
						if (collapsed) {
							this.$().next().hide();
							this.set('_collapsed', true);
						}
					}
				},

				click: function() {
					this.toggleCollapsed();
				},

				toggleCollapsed: function() {
					var menuGroup = this.get('group');
					var isCollapsed = this.get('controller').toggleCollapsed(menuGroup);
					this.set('_collapsed', isCollapsed);
				},

				_onCollapsedChange: function() {
					var $content = this.$().next();
					if (this.get('collapsed') === true) {
						$content.slideUp(200);
					} else {
						$content.slideDown(200);
					}
				}.observes('collapsed')
			}),

			mouseEnter: function() {
				this.set('controller.menuPanelMode', true);
			},

			mouseLeave: function() {
				if (this.get('controller.configuration.stickyMenuState') === false) {
					this.set('controller.menuPanelMode', false);
				}
			},

			toggleMenuPanelMode: function() {
				this.set('controller.menuPanelMode', !this.get('controller.menuPanelMode'));
			},

			onMenuPanelModeChanged: function() {
				if (this.get('controller.menuPanelMode') === true) {
					$('body').addClass('neos-menu-panel-open');
				} else {
					$('body').removeClass('neos-menu-panel-open');
				}
			}.observes('controller.menuPanelMode')
		});
	}
);