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
	'Content/Application'
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
	ContentModule
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
			this.set('selected', this.get('presets').filter(function(preset) {
				return preset.get('selected') === true;
			}).get(0));
		}.observes('presets').on('init')
	});

	/**
	 * Helper class for a Dimension Preset
	 */
	Preset = Ember.Object.extend({
		identifier: Ember.required(),
		label: Ember.required(),
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

		/**
		 * Initialization
		 */
		init: function() {
			var that = this;
			this._updateSelectedDimensionsFromCurrentDocument();
			this._loadConfiguration();
		},

		/**
		 * Retrieve the available content dimension presets via the REST service and set the local configuration accordingly.
		 * Also fetches the "currently selected dimensions" from the meta data of the currently shown document.
		 */
		_loadConfiguration: function() {
			var that = this;
			HttpRestClient.getResource('neos-service-contentdimensions').then(function(result) {
				var configuration = {};

				$.each($('.contentdimensions', result.resource).children('li'), function(key, contentDimensionSnippet) {

					var presets = {};
					$.each($('.contentdimension-preset', contentDimensionSnippet), function(key, contentDimensionPresetSnippet) {

						var values = [];
						$.each($('.contentdimension-preset-values li', contentDimensionPresetSnippet), function(key, contentDimensionPresetValuesSnippet) {
							values.push($(contentDimensionPresetValuesSnippet).text());
						});

						var presetIdentifier = $('.contentdimension-preset-identifier', contentDimensionPresetSnippet).text();
						presets[presetIdentifier] = {
							label: $('.contentdimension-preset-label', contentDimensionPresetSnippet).text(),
							values: values
						};
					});

					var dimensionIdentifier = $('.contentdimension-identifier', contentDimensionSnippet).text();
					configuration[dimensionIdentifier] = {
						label: $('.contentdimension-label', contentDimensionSnippet).text(),
						icon: $('.contentdimension-icon', contentDimensionSnippet).text(),
						defaultPreset: $('.contentdimension-defaultpreset .contentdimension-preset-identifier', contentDimensionSnippet).text(),
						presets: presets
					}
				});
				that.set('configuration', configuration);
			}, function(error) {
				console.error('Failed loading dimension presets data.', error);
			});
		},

		/**
		 * Updates the "selectedDimensions" property by retrieving the currently active dimensions from the document markup
		 */
		_updateSelectedDimensionsFromCurrentDocument: function() {
			this.set('selectedDimensions', $('#neos-document-metadata').data('neos-context-dimensions'));
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
					presets.push(Preset.create($.extend({selected: selected, identifier: presetIdentifier}, presetConfiguration)));
				});

				if (presets.length > 1) {
					presets.sort(function(a, b) {
						return a.get('position') > b.get('position') ? 1 : -1;
					});
					dimensions.push(Dimension.create($.extend(dimensionConfiguration, {identifier: dimensionIdentifier, presets: presets})));
				}
			});

			dimensions.sort(function(a, b) {
				return a.get('position') > b.get('position') ? 1 : -1;
			});

			return dimensions;
		}.property('configuration', 'selectedDimensions'),

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

		/*
		 * Send an AJAX request for querying the Nodes service to check if the current node can be displayed in the
		 * currently configured dimensions and if so, reloads the current document with the new context.
		 *
		 * Also triggers "contentDimensionsSelectionChanged" if the document could be reloaded.
		 */
		reloadDocument: function() {
			var that = this,
				$documentMetadata = $('#neos-document-metadata'),
				nodeIdentifier = $documentMetadata.data('node-_identifier'),
				parameters = {
					dimensions: this.get('dimensionValues'),
					workspaceName: $documentMetadata.data('neos-context-workspace-name')
				};

			HttpRestClient.getResource('neos-service-nodes', nodeIdentifier, {data: parameters}).then(function(result) {
				that.set('selectorIsActive', false);
				ContentModule.loadPage($("link[rel='node-frontend']", result.resource).attr('href'), false, function() {
					EventDispatcher.trigger('contentDimensionsSelectionChanged');
				});
			});
		}
	}).create();
});