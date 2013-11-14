/**
 * Controller for the Views Panel
 *
 * Singleton
 */
define(
[
	'emberjs',
	'Shared/LocalStorage',
	'./FrontendEditingMode',
	'./WireframeEditingMode',
	'./FrontendPreviewMode'
], function(
	Ember,
	LocalStorage,
	FrontendEditingMode,
	WireframeEditingMode,
	FrontendPreviewMode
) {

	var frontendEditingModeInstance = FrontendEditingMode.create();

	return Ember.Controller.extend({
		visible: false,

		currentlyActiveMode: frontendEditingModeInstance,

		editingModes: [
			frontendEditingModeInstance,
			WireframeEditingMode.create()
		],
		previewModes: [
			FrontendPreviewMode.create()
		],

		_deactivatePreviouslyActiveMode: function() {
			Ember.sendEvent(this.get('currentlyActiveMode'), 'deactivate');
			this.set('currentlyActiveMode.active', false);
		}.observesBefore('currentlyActiveMode'),

		_activateCurrentlyActiveMode: function() {
			this.set('currentlyActiveMode.active', true);
			Ember.sendEvent(this.get('currentlyActiveMode'), 'activate');
		}.observes('currentlyActiveMode'),

		_saveCurrentlyActiveModeInLocalStorage: function() {
			LocalStorage.setItem('editPreviewMode', this.get('currentlyActiveMode.identifier'));
		}.observes('currentlyActiveMode'),

		init: function() {
			var currentlyActiveViewModeIdentifier = LocalStorage.getItem('editPreviewMode') || frontendEditingModeInstance.get('identifier');
			this.set('currentlyActiveMode', this._findViewMode(currentlyActiveViewModeIdentifier) ||frontendEditingModeInstance);

			this.set('currentlyActiveMode.active', true);
			Ember.sendEvent(this.get('currentlyActiveMode'), 'activateOnPageLoad');

			if (LocalStorage.getItem('editPreviewPanelVisible') == true) {
				this.toggleVisibility();
			}
		},

		_findViewMode: function(identifier) {
			return this.get('editingModes').findBy('identifier', identifier) || this.get('previewModes').findBy('identifier', identifier);
		},

		toggleVisibility: function() {
			this.toggleProperty('visible');
		},

		_editPreviewPanelVisibilityChanged: function() {
			LocalStorage.setItem('editPreviewPanelVisible', this.get('visible'));
		}.observes('visible'),

		actions: {
			activateMode: function(editingMode) {
				this.set('currentlyActiveMode', editingMode);
			}
		}
	}).create();
});