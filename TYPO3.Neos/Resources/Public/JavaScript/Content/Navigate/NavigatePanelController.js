/**
 * Controller for the Navigate Panel
 *
 * Singleton
 */
define(
[
	'emberjs',
	'Shared/LocalStorage'
], function(
	Ember,
	LocalStorage
) {
	return Ember.Object.extend({
		navigatePanelMode: false,
		contextStructureMode: false,

		init: function() {
			if (LocalStorage.getItem('navigateTreeMode') !== false) {
				this.toggleNavigatePanelMode();
			}
			if (LocalStorage.getItem('contextStructureMode') !== false) {
				this.toggleContextStructureMode();
			}
		},

		toggleNavigatePanelMode: function() {
			this.set('navigatePanelMode', !this.get('navigatePanelMode'));
		},

		toggleContextStructureMode: function() {
			this.set('contextStructureMode', !this.get('contextStructureMode'));
		},

		navigatePanelModeChanged: function() {
			LocalStorage.setItem('navigateTreeMode', this.get('navigatePanelMode'));
		}.observes('navigatePanelMode'),

		contextStructureModeChanged: function() {
			LocalStorage.setItem('contextStructureMode', this.get('contextStructureMode'));
		}.observes('contextStructureMode')
	}).create();
});