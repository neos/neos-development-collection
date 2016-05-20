/**
 * Controller for the Edit / Preview Panel
 *
 * Singleton
 */
define(
[
	'emberjs',
	'Library/jquery-with-dependencies',
	'Shared/LocalStorage',
	'Shared/Configuration',
	'Shared/ResourceCache',
	'Shared/Notification',
	'Shared/EventDispatcher',
	'Content/LoadingIndicator',
	'./EditingMode',
	'./PreviewMode',
	'Shared/Endpoint/UserPreferenceEndpoint'
], function(
	Ember,
	$,
	LocalStorage,
	Configuration,
	ResourceCache,
	Notification,
	EventDispatcher,
	LoadingIndicator,
	EditingMode,
	PreviewMode,
	UserPreferenceEndpoint
) {
	return Ember.Controller.extend({
		editPreviewPanelMode: false,
		currentlyActiveMode: null,
		previousActiveMode: null,

		configuration: null,
		nodeType: 'TYPO3.Neos:Document',
		editingModes: [],
		previewModes: [],

		neosEditingFrameClass: 'neos-edit',
		neosPreviewFrameClass: 'neos-preview',

		_deactivatePreviouslyActiveMode: function() {
			if (this.get('currentlyActiveMode')) {
				Ember.sendEvent(this.get('currentlyActiveMode'), 'deactivate');
				this.set('currentlyActiveMode.active', false);
				this.set('previousActiveMode', this.get('currentlyActiveMode'));
			}
		}.observesBefore('currentlyActiveMode'),

		_activateCurrentlyActiveMode: function() {
			this.set('currentlyActiveMode.active', true);
			Ember.sendEvent(this.get('currentlyActiveMode'), 'activate');
		}.observes('currentlyActiveMode'),

		isEditingModeActive: function() {
			return this.get('currentlyActiveMode.isEditingMode');
		}.property('currentlyActiveMode'),

		isPreviewModeActive: function() {
			return this.get('currentlyActiveMode.isPreviewMode');
		}.property('currentlyActiveMode'),

		_saveCurrentlyActiveModeInLocalStorage: function() {
			if (!this.get('previousActiveMode')) {
				return;
			}
			var identifier = this.get('currentlyActiveMode.identifier'),
				reloadRequired = false;

			LocalStorage.setItem('editPreviewMode', identifier);
			if (this.get('previousActiveMode') && this.get('previousActiveMode.typoScriptRenderingPath') !== this.get('currentlyActiveMode.typoScriptRenderingPath')) {
				reloadRequired = true;
			}
			if (reloadRequired) {
				LoadingIndicator.start();
			}
			UserPreferenceEndpoint.updatePreference('contentEditing.editPreviewMode', identifier).then(
				function() {
					if (reloadRequired) {
						window.location.reload(false);
					}
				}
			);
		}.observes('currentlyActiveMode'),

		_initialize: function() {
			var that = this;
			EventDispatcher.on('nodesUpdated', function() {
				that._load();
			});
		}.on('init'),

		_load: function() {
			var nodeType = $('#neos-document-metadata').data('node-_nodeType');
			this.set('nodeType', nodeType);
			var item = Configuration.get('EditPreviewDataUri') + '&nodeType=' + encodeURIComponent(nodeType);
			var that = this;
			ResourceCache.getItem(item).then(
				function(data) {
					that.set('configuration', data);
				}
			);

			this.set('editPreviewPanelMode', LocalStorage.getItem('editPreviewPanelMode'));
		},

		toggleEditPreviewPanelMode: function() {
			this.toggleProperty('editPreviewPanelMode');
		},

		_onConfigurationChanged: function() {
			if (!this.get('configuration')) {
				return;
			}
			var editingModes = [],
				previewModes = [],
				editPreviewConfiguration = this.get('configuration'),
				storedActiveViewModeIdentifier = LocalStorage.getItem('editPreviewMode'),
				currentlyActiveMode = null;
			$.each(editPreviewConfiguration, function(identifier, configuration) {
				var modeConfiguration = $.extend({identifier: identifier}, configuration),
					editingMode,
					previewMode;
				delete modeConfiguration.position;
				if (modeConfiguration.isEditingMode === true) {
					delete modeConfiguration.editingMode;
					editingMode = EditingMode.create(modeConfiguration);
					editingModes.push(editingMode);
				}
				if (modeConfiguration.isPreviewMode === true) {
					delete modeConfiguration.previewMode;
					previewMode = PreviewMode.create(modeConfiguration);
					previewModes.push(previewMode);
				}
				if (identifier === storedActiveViewModeIdentifier) {
					currentlyActiveMode = editingMode || previewMode;
				}
			});
			this.set('editingModes', editingModes);
			this.set('previewModes', previewModes);

			if (editingModes.length > 0) {
				this.set('neosEditingFrameClass', 'neos-edit');
			} else {
				this.set('neosEditingFrameClass', 'neos-edit neos-hide');
			}
			if (previewModes.length > 0) {
				this.set('neosPreviewFrameClass', 'neos-edit');
			} else {
				this.set('neosPreviewFrameClass', 'neos-edit neos-hide');
			}

			currentlyActiveMode = currentlyActiveMode ? currentlyActiveMode : editingModes[0];
			if (!currentlyActiveMode) {
				return;
			}
			this.set('currentlyActiveMode', currentlyActiveMode);
			Ember.sendEvent(currentlyActiveMode, 'activateOnPageLoad');
			EventDispatcher.trigger('editPreviewModeConfigurationChanged');
		}.observes('configuration').on('init'),

		_editPreviewPanelModeChanged: function() {
			LocalStorage.setItem('editPreviewPanelMode', this.get('editPreviewPanelMode'));
		}.observes('editPreviewPanelMode'),

		actions: {
			activateMode: function(mode) {
				this.set('currentlyActiveMode', mode);
			}
		}
	}).create();
});
