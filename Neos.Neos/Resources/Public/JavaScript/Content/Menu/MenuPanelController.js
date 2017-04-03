/**
 * Controller for the Menu Panel
 *
 * Singleton
 */
define(
[
	'emberjs',
	'Library/jquery-with-dependencies',
	'Shared/LocalStorage',
	'Shared/ResourceCache',
	'Shared/Configuration'
], function(Ember, $, LocalStorage, ResourceCache, Configuration) {
	return Ember.Object.extend({
		configuration: null,
		menuPanelMode: false,
		menuPanelStickyMode: false,
		isMenuPanelStickyModeShown: false,

		items: [],

		init: function() {
			var that = this;
			ResourceCache.getItem(Configuration.get('MenuDataUri')).then(
				function(data) {
					that.set('items', data);
				}
			);

			this.set('configuration', LocalStorage.getItem('menuConfiguration') || {'content': true});
			Ember.addObserver(this, 'configuration', function() {
				var configuration = this.get('configuration');
				if ($.isEmptyObject(configuration) === false) {
					LocalStorage.setItem('menuConfiguration', configuration);
				}
			});
			if (this.get('configuration.menuPanelStickyMode') === true) {
				this.toggleCollapsed();
				this.toggleMenuPanelStickyMode();
			}
		},

		toggleCollapsed: function(menuGroup) {
			if (!this.get('configuration.' + menuGroup)) {
				this.set('configuration.' + menuGroup, false);
			}
			var newCollapsedState = this.toggleProperty('configuration.' + menuGroup);
			this.propertyDidChange('configuration');
			return newCollapsedState;
		},

		toggleMenuPanelStickyMode: function() {
			this.set('menuPanelStickyMode', !this.get('menuPanelStickyMode'));
			this.set('isMenuPanelStickyModeShown', this.get('menuPanelStickyMode'));
		},

		menuPanelStickyModeChanged: function() {
			this.toggleProperty('configuration.menuPanelStickyMode');
			this.propertyDidChange('configuration');
		}.observes('menuPanelStickyMode'),

		isMenuPanelStickyModeShownChanged: function() {
			this.toggleProperty('configuration.isMenuPanelStickyModeShown');
			this.propertyDidChange('configuration');
		}.observes('isMenuPanelStickyModeShown'),

		activeItem: function() {
			var that = this,
				module = $(document.body).hasClass('neos-module');
			if (module) {
				var modules = this.get('items.modules');
				if (typeof modules !== 'undefined') {
					$.each(modules, function(moduleIndex, moduleConfiguration) {
						var submoduleMatched = false;
						if (typeof moduleConfiguration.submodules !== 'undefined') {
							$.each(moduleConfiguration.submodules, function(submoduleIndex, submoduleConfiguration) {
								if (location.pathname.indexOf(submoduleConfiguration.modulePath) !== -1) {
									that.set('items.modules.' + moduleIndex + '.submodules.' + submoduleIndex + '.active', true);
									submoduleMatched = true;
								}
							});
						}
						if (submoduleMatched === false) {
							if (location.pathname.indexOf(moduleConfiguration.modulePath) !== -1) {
								that.set('items.modules.' + moduleIndex + '.active', true);
							}
						}
					});
				}
			}
			var sites = this.get('items.sites'),
				currentSite = Configuration.get('site');
			if (typeof sites !== 'undefined') {
				$.each(sites, function(index, value) {
					that.set('items.sites.' + index + '.active', module ? false : value.nodeName === currentSite);
				});
			}
		}.observes('items').on('init')
	}).create();
});
