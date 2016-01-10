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
	return Ember.Controller.extend({
		actions: {
			toggleCollapsed: function(menuGroup) {
				return this.toggleCollapsed(menuGroup);
			},
			toggleMenuPanelStickyMode: function() {
				this.set('menuPanelStickyMode', !this.get('menuPanelStickyMode'));
				this.set('isMenuPanelStickyModeShown', this.get('menuPanelStickyMode'));
			}
		},
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
				},
				function(error) {
					console.error('Error loading menu data.', error);
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
				this.send('toggleMenuPanelStickyMode');
			}
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
			var that = this;
			if ($(document.body).hasClass('neos-module')) {
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
			} else {
				var sites = this.get('items.sites');
				if (typeof sites !== 'undefined') {
					$.each(sites, function(index, value) {
						if (value.uri && value.uri.indexOf(location.hostname) !== -1) {
							that.set('items.sites.' + index + '.active', true);
						}
					});
				}
			}
		}.observes('items').on('init'),

		toggleCollapsed: function(menuGroup) {
			if (!this.get('configuration.' + menuGroup)) {
				this.set('configuration.' + menuGroup, false);
			}
			var newCollapsedState = this.toggleProperty('configuration.' + menuGroup);
			this.propertyDidChange('configuration');
			return newCollapsedState;
		}
	}).create();
});
