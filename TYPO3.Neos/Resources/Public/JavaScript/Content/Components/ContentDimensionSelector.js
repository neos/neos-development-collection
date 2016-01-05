define(
[
	'emberjs',
	'Library/jquery-with-dependencies',
	'./ContentDimensionController',
	'Shared/EventDispatcher',
	'Content/Model/NodeSelection',
	'text!./ContentDimensionSelector.html',
	'Shared/I18n'
],
function(
	Ember,
	$,
	ContentDimensionController,
	EventDispatcher,
	NodeSelection,
	template,
	I18n
) {
	/**
	 * Ember view which displays the content dimensions selector
	 *
	 * The view consists of two parts: the dimension selector summary, which displays the currently selected dimensions
	 * even if the dimensions selector is collapsed, and the expandable selector which shows a selector box for each
	 * dimension with more than one preset.
	 */
	return Ember.View.extend({
		template: Ember.HTMLBars.compile(template),
		classNames: ['neos-content-dimension-selector'],
		classNameBindings: ['isActive:active'],
		isActiveBinding: 'controller.selectorIsActive',

		controller: ContentDimensionController,
		nodeSelection: NodeSelection,

		/**
		 * Initialize the click handler for the dimensions selector panel
		 */
		didInsertElement: function() {
			var that = this;
			this._initialize();
			this.$('.neos-content-dimension-selector-summary').on('click', function() {
				that.toggleProperty('isActive');
			});
		},

		/**
		 * General initialisation of this view
		 */
		init: function() {
			this._super();

			ContentDimensionController._loadConfiguration();
		},

		/**
		 * Hide the dimensions selector if no dimensions can be selected anyway
		 */
		isVisible: function() {
			return ContentDimensionController.get('dimensions').length > 0;
		}.property('controller.dimensions'),

		/**
		 * The "Cancel" and "Apply" buttons only need to be shown and used when more than one dimension is available
		 */
		isMultiDimensionSelection: function() {
			return ContentDimensionController.get('dimensions').length > 1;
		}.property('controller.dimensions'),

		/**
		 * (Re-)initialize the content dimension selectors
		 *
		 * When dimensions are available (REST service delivered a response) or they changed by some other means,
		 * a function is registered which calls _updateValue() when the user selected a different dimension preset in
		 * any of the selector boxes.
		 */
		_initialize: function() {
			var that = this;
			var dimensions = ContentDimensionController.get('dimensions');

			if (!this.$()) {
				return;
			}

			Ember.run.next(this, function() {
				if (that.$()) {
					that.$('select').chosen({disable_search_threshold: 10}).change(function(event) {
						that._updateValue();
						if (that.get('isMultiDimensionSelection')) {
							ContentDimensionController._updateAvailableDimensionPresetsAfterChoosingPreset(dimensions.findBy('identifier', event.target.name));
						} else {
							ContentDimensionController.applySelection();
						}
					});
				}
			});

			dimensions.forEach(function(dimension) {
				dimension.get('presets').forEach(function(preset) {
					preset.addObserver('disabled', function() {
						Ember.run.next(this, function() {
							that.$('select#neos-content-dimension-' + dimension.get('identifier')).trigger('chosen:updated.chosen');
						});
					});
				});
			});
		}.observes('controller.dimensions'),

		/**
		 * Update the currently selected dimension(s) in the selector box(es)
		 */
		_updateValue: function() {
            var dimensions = ContentDimensionController.get('dimensions');
            var presets = ContentDimensionController.get('presets');

            if (dimensions && presets) {
                $('select').each(function () {
                    var dimensionIdentifier = $(this).attr('name');
                    var dimensionPresetIdentifier = $(this).val();
                    var dimension = dimensions.findBy('identifier', dimensionIdentifier);
                    var dimensionPreset = presets.findBy('identifier', dimensionPresetIdentifier);

                    dimension.set('selected', dimensionPreset);
                    dimensionPreset.set('selected', true);
                    ContentDimensionController.set('selectedDimensions.' + dimensionIdentifier, dimensionPreset.values);
                });
            }
        },

		_nodeTypeLabel: function() {
			return I18n.translate(this.get('nodeSelection.nodes.lastObject.nodeTypeSchema.ui.label'));
		}.property('nodeSelection.nodes.lastObject.nodeTypeSchema.ui.label')
	});
});