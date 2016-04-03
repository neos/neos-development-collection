define(
	[
		'emberjs',
		'Library/jquery-with-dependencies',
		'./MenuPanelController',
		'Shared/EventDispatcher',
		'Shared/I18n',
		'text!./MenuPanel.html'
	], function(Ember, $, MenuPanelController, EventDispatcher, I18n, template) {

		return Ember.View.extend({
			elementId: 'neos-menu-panel',
			template: Ember.Handlebars.compile(template),
			controller: MenuPanelController,

			ToggleMenuPanelHeadline: Ember.View.extend({
				tagName: 'div',
				classNameBindings: ['collapsed:neos-collapsed:neos-open'],
				_collapsed: false,

				collapsed: function() {
					if (this.get('controller.menuPanelStickyMode')) {
						return false;
					} else {
						return this.get('_collapsed');
					}
				}.property('_collapsed', 'controller.menuPanelStickyMode'),

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
					var menuGroup = this.get('group'),
						isCollapsed = this.get('controller').toggleCollapsed(menuGroup);
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

			LinkView: Ember.View.extend({
				tagName: 'a',
				attributeBindings: ['href', 'title'],
				description: '',
				label: '',
				href: '',
				shouldShowDescription: function() {
					return !this.get('controller.menuPanelStickyMode');
				}.property('controller.menuPanelStickyMode'),

				title: function() {
					return I18n.translate(this.get('shouldShowDescription') ? this.get('description') : this.get('label'));
				}.property('description', 'label', 'shouldShowDescription')
			}),

			mouseLeave: function(event) {
				// We do not want the panel to close if we move from the panel back to the button because this
				// leads to a confusing behaviour of the user.
				if (this.get('controller.menuPanelStickyMode') === false && event.relatedTarget !== $('#neos-menu-button').get(0)) {
					this.set('controller.menuPanelMode', false);
				}
			},

			onMenuPanelModeChanged: function() {
				if (this.$()) {
					var that = this;
					this.$().one('webkitTransitionEnd transitionend msTransitionEnd oTransitionEnd', function () {
						if (that.get('controller.menuPanelMode') === true) {
							EventDispatcher.triggerExternalEvent('Neos.MenuPanelOpened');
						} else {
							EventDispatcher.triggerExternalEvent('Neos.MenuPanelClosed');
						}
						if (that.get('controller.menuPanelStickyMode') === false) {
							return;
						}
						EventDispatcher.triggerExternalEvent('Neos.LayoutChanged');
					});
				}
				if (this.get('controller.menuPanelMode') === true) {
					$('body').addClass('neos-menu-panel-open');
				} else {
					$('body').removeClass('neos-menu-panel-open');
				}
			}.observes('controller.menuPanelMode').on('init'),

			onMenuPanelStickyModeChanged: function() {
				if (this.get('controller.menuPanelStickyMode')) {
					$('body').addClass('neos-menu-panel-sticky');
				} else {
					$('body').removeClass('neos-menu-panel-sticky');
				}
			}.observes('controller.menuPanelStickyMode').on('init')
		});
	}
);