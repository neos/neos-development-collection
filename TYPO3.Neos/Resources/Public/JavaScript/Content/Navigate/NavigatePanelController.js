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
			if (LocalStorage.getItem('navigatePanelMode') !== false) {
				this.toggleNavigatePanelMode();
			}
			if (LocalStorage.getItem('contextStructureMode') !== false) {
				this.toggleContextStructureMode();
			}
		},

		toggleNavigatePanelMode: function() {
			this.toggleProperty('navigatePanelMode');
		},

		toggleContextStructureMode: function() {
			this.toggleProperty('contextStructureMode');
		},

		_navigatePanelModeChanged: function() {
			LocalStorage.setItem('navigatePanelMode', this.get('navigatePanelMode'));
		}.observes('navigatePanelMode'),

		_contextStructureModeChanged: function() {
			LocalStorage.setItem('contextStructureMode', this.get('contextStructureMode'));
		}.observes('contextStructureMode')
	}).create();
});