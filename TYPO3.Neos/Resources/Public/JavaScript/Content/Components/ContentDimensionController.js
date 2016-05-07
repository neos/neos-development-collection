define(
[
	'emberjs',
	'Library/jquery-with-dependencies',
	'Shared/Configuration',
	'Shared/ResourceCache',
	'Shared/Notification',
	'Shared/EventDispatcher',
	'Shared/HttpRestClient',
	'vie',
	'Content/Application',
	'Shared/I18n'
],
function(
	Ember,
	$,
	Configuration,
	ResourceCache,
	Notification,
	EventDispatcher,
	HttpRestClient,
	vie,
	ContentModule,
	I18n
) {
	var Dimension, Preset;

	/**
	 * Helper class for a Content Dimension
	 */
	Dimension = Ember.Object.extend({
		identifier: Ember.required(),
		defaultPreset: Ember.required(),
		label: Ember.required(),
		icon: Ember.required(),
		options: Ember.required(),
		selected: null,
		_presetsDidChange: function() {
			this.set('selected', this.get('presets').findBy('selected', true));
		}.observes('presets').on('init')
	});

	/**
	 * Helper class for a Dimension Preset
	 */
	Preset = Ember.Object.extend({
		identifier: Ember.required(),
		label: Ember.required(),
		disabled: false,
		values: Ember.required()
	});

	/**
	 * Controller displaying the dimension selector
	 */
	return Ember.Controller.extend({
		classNames: 'neos-dimension-selector',
		selectedDimensions: {},
		disableOverlayButtons: false,
		selectorIsActive: false,

		// if active, will not be set to TRUE, but instead to an object with the following properties:
		// - numberOfNodesMissingInRootline
		showInitialTranslationDialog: false,

		/**
		 * Retrieve the available content dimension presets via the REST service and set the local configuration accordingly.
		 * Also fetches the "currently selected dimensions" from the meta data of the currently shown document.
		 */
		_loadConfiguration: function() {
			var that = this;
			ResourceCache.getItem(Configuration.get('ContentDimensionsUri')).then(function(configuration) {
				that.set('configuration', configuration);
			});
		},

		/**
		 * Updates the "selectedDimensions" property by retrieving the currently active dimensions from the document markup
		 *
		 * Note that "selectedDimensions" contains the dimension _values_ for each dimension, not the dimension _presets_
		 */
		_updateSelectedDimensionsFromCurrentDocument: function() {
			var that = this;
			var firstDimensionSkipped = false;

			// Hint: if we do not clone the selected dimensions, the switch-back to older dimensions does not properly work (e.g. inside cancelCreateAction)
			this.set('selectedDimensions', $.extend(true, {}, $('#neos-document-metadata').data('neos-context-dimensions')));

			that._updateAvailableDimensionPresetsAfterChoosingPreset(this.get('dimensions').get(0));
		}.observes('configuration'),

		/**
		 * If a preset has been chosen, the list of allowed dimension presets in other dimensions might change.
		 */
		_updateAvailableDimensionPresetsAfterChoosingPreset: function(changedDimension) {
			var chosenDimensionPresets = {};
			var dimensions = this.get('dimensions');

			$.each(dimensions, function(key, dimension) {
				chosenDimensionPresets[dimension.get('identifier')] = dimension.get('selected.identifier');
			});

			var passedChangedDimension = false;
			$.each(dimensions, function(key, dimension) {
				if (passedChangedDimension) {
					HttpRestClient.getResource('neos-service-contentdimensions', dimension.get('identifier'), {data: {chosenDimensionPresets: chosenDimensionPresets}}).then(function (result) {
						$.each(dimension.get('presets'), function (key, preset) {
							if ($('.contentdimension-preset-identifier:contains("' + preset.get('identifier') + '")', result.resource).length === 0) {
								preset.set('disabled', true);
								if (preset.get('selected')) {
									preset.set('selected', false);
								}
							} else {
								preset.set('disabled', false);
							}
						});

						// If no preset is selected anymore (because it has been disabled above), select the first not-disabled presets:
						if (dimension.get('presets').findBy('selected', true) === undefined) {
							var substituteDimensionPreset = dimension.get('presets').findBy('disabled', false);
							if (substituteDimensionPreset !== undefined) {
								dimension.set('selected', substituteDimensionPreset);
								substituteDimensionPreset.set('selected', true);
							}
						}
					}, function (error) {
						console.error('Failed loading dimension presets data for dimension ' + dimension.get('identifier') + '.', error);
					});
				}
				if (dimension.get('identifier') === changedDimension.get('identifier')) {
					passedChangedDimension = true;
				}
			});
		},

		/**
		 * Computed property: available dimensions and their presets
		 */
		dimensions: function() {
			var dimensions = [];
			var selectedDimensions = this.get('selectedDimensions');

			if (!this.get('configuration')) {
				return dimensions;
			}

			$.each(this.get('configuration'), function(dimensionIdentifier, dimensionConfiguration) {
				var presets = [];
				var selectedDimensionValues = selectedDimensions[dimensionIdentifier];

				$.each(dimensionConfiguration.presets, function(presetIdentifier, presetConfiguration) {
					var selected = false;

					if (selectedDimensionValues) {
						if (JSON.stringify(selectedDimensionValues) === JSON.stringify(presetConfiguration.values)) {
							selected = true;
						}
					} else {
						selected = (presetIdentifier === dimensionConfiguration.defaultPreset);
					}
					presets.push(Preset.create($.extend(true, {selected: selected, identifier: presetIdentifier}, presetConfiguration)));
				});

				if (presets.length > 1) {
					presets.sort(function(a, b) {
						return a.get('position') > b.get('position') ? 1 : -1;
					});
					dimensions.push(Dimension.create($.extend(true, {}, dimensionConfiguration, {identifier: dimensionIdentifier, presets: presets})));
				}
			});

			dimensions.sort(function(a, b) {
				return a.get('position') > b.get('position') ? 1 : -1;
			});

			return dimensions;
		}.property('selectedDimensions'),

		/**
		 * Computed property of selected dimension values
		 */
		dimensionValues: function() {
			var dimensions = {};
			$.each(this.get('dimensions'), function(index, dimension) {
				dimensions[dimension.get('identifier')] = dimension.get('selected.values');
			});
			return dimensions;
		}.property('dimensions.@each.selected'),

		currentDimensionChoiceText: function() {
			var dimensionText = [];
			$.each(this.get('dimensions'), function(index, dimension) {
				var translatedLabel = I18n.translate(dimension.get('label'));
				dimensionText.push(translatedLabel + ' ' + dimension.get('selected.label'));
			});
			return dimensionText.join(', ');
		}.property('dimensions.@each.selected'),

		currentDocumentDimensionChoiceText: '',

		_initiallyUpdateDocumentDimensionChoiceText: function() {
			if (!this.get('currentDocumentDimensionChoiceText')) {
				this.set('currentDocumentDimensionChoiceText', this.get('currentDimensionChoiceText'));
			}
		}.observes('currentDimensionChoiceText'),

		/**
		 * Revert the selected dimension values to those stored in the current document and close the dimension selector
		 */
		cancelSelection: function () {
			this._updateSelectedDimensionsFromCurrentDocument();
			this.set('selectorIsActive', false);
		},

		/**
		 * Apply the currently selected dimensions and reload the document to reflect these changes
		 *
		 * This method sends an AJAX request for querying the Nodes service to check if the current node already exists
		 * in the newly selected dimensions. If it does, the document is reloaded with the new dimension values, if such
		 * a combination does not exist yet, a dialog is shown asking the user if such a variant should be created.
		 *
		 * Also triggers "contentDimensionsSelectionChanged" if the document could be reloaded.
		 */
		applySelection: function () {
			var that = this,
				$documentMetadata = $('#neos-document-metadata'),
				nodeIdentifier = $documentMetadata.data('node-_identifier'),
				parameters = {
					dimensions: this.get('dimensionValues'),
					workspaceName: $documentMetadata.data('neos-context-workspace-name')
				};

			this.set('showInitialTranslationDialog', false);
			ContentModule.set('httpClientFailureHandling', false);
			HttpRestClient.getResource('neos-service-nodes', nodeIdentifier, {data: parameters}).then(function(result) {
				ContentModule.set('httpClientFailureHandling', true);
				that.set('selectorIsActive', false);
				ContentModule.loadPage($('.node-frontend-uri', result.resource).attr('href'), false, function() {
					EventDispatcher.trigger('contentDimensionsSelectionChanged');

					that._updateSelectedDimensionsFromCurrentDocument();
					that.set('currentDocumentDimensionChoiceText', that.get('currentDimensionChoiceText'));
				});
			}, function(error) {
				ContentModule.set('httpClientFailureHandling', true);
				if (error.xhr.status === 404 && error.xhr.getResponseHeader('X-Neos-Node-Exists-In-Other-Dimensions')) {
					that.set('showInitialTranslationDialog', {numberOfNodesMissingInRootline: parseInt(error.xhr.getResponseHeader('X-Neos-Nodes-Missing-On-Rootline'))});
				} else {
					Notification.error('Unexpected error while while fetching alternative content variants.');
				}
			});
		},

		cancelCreateAction: function() {
			this.set('showInitialTranslationDialog', false);
		},

		createEmptyDocumentAction: function() {
			this._createDocumentAndOptionallyCopy('adoptFromAnotherDimension');
		},

		createDocumentAndCopyContentAction: function() {
			this._createDocumentAndOptionallyCopy('adoptFromAnotherDimensionAndCopyContent');
		},

		_createDocumentAndOptionallyCopy: function(mode) {
			var that = this,
				$documentMetadata = $('#neos-document-metadata'),
				nodeIdentifier = $documentMetadata.data('node-_identifier'),
				parameters = {
					identifier: nodeIdentifier,
					dimensions: this.get('dimensionValues'),
					sourceDimensions: $documentMetadata.data('neos-context-dimensions'),
					workspaceName: $documentMetadata.data('neos-context-workspace-name'),
					mode: mode
				};

			HttpRestClient.createResource('neos-service-nodes', {data: parameters}).then(function(result) {
				that.set('selectorIsActive', false);
				that.set('showInitialTranslationDialog', false);

				ContentModule.loadPage($('.node-frontend-uri', result.resource).attr('href'), false, function() {
					that._updateSelectedDimensionsFromCurrentDocument();
					that.set('currentDocumentDimensionChoiceText', that.get('currentDimensionChoiceText'));
					Notification.ok('Created ' + that.get('currentDimensionChoiceText'));
					EventDispatcher.trigger('contentDimensionsSelectionChanged');
				});
			});
		}
	}).create();
});
