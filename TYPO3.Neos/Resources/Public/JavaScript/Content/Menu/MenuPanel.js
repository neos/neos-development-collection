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

			controller: MenuPanelController,

			ToggleMenuPanelHeadline: Ember.View.extend({
				tagName: 'div',
				classNameBindings: ['_collapsed:neos-collapsed:neos-open'],
				_collapsed: false,
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
					if (this.get('_collapsed') === true) {
						$content.slideUp(200);
					} else {
						$content.slideDown(200);
					}
				}.observes('_collapsed')
			}),

			mouseEnter: function() {
				this.set('controller.menuPanelMode', true);
			},

			mouseLeave: function() {
				this.set('controller.menuPanelMode', false);
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