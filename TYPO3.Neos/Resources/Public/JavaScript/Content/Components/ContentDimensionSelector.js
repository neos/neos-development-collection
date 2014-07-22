define(
[
	'emberjs',
	'Library/jquery-with-dependencies',
	'./ContentDimensionController',
	'Shared/EventDispatcher',
	'text!./ContentDimensionSelector.html'
],
function(
	Ember,
	$,
	ContentDimensionController,
	EventDispatcher,
	template
) {
	return Ember.View.extend({
		template: Ember.Handlebars.compile(template),
		classNames: ['neos-content-dimension-selector'],
		classNameBindings: ['isActive:active'],
		isActiveBinding: 'controller.selectorIsActive',

		controller: ContentDimensionController,

		didInsertElement: function() {
			var that = this;
			this.$('.neos-content-dimension-selector-summary').on('click', function() {
				that.toggleProperty('isActive');
			});

			EventDispatcher.on('contentDimensionsChanged', function() {
				that._onContentDimensionsChanged();
			});
		},

		_initializeChosen: function() {
			Ember.run.scheduleOnce('afterRender', this, function() {
				var that = this;
				this.$('select').chosen({disable_search_threshold: 10}).change(function() {
					that._updateValue(false);
				});
			});
		}.observes('controller.dimensions'),

		_updateValue: function(skipReload) {
			var controller = this.get('controller'),
				dimensionIdentifier = this.$('select').attr('name'),
				presetIdentifier = this.$('select').val(),
				dimension = controller.get('dimensions').filter(function(dimension) {
					return dimension.get('identifier') === dimensionIdentifier;
				}).get(0);
			dimension.set('selected', dimension.get('presets').filter(function(preset) {
				return preset.get('identifier') === presetIdentifier;
			}).get(0));
			controller.checkIfSelectedDimensionExists(skipReload);
		},

		_onContentDimensionsChanged: function() {
			this.get('controller').set('selectedDimensions', $('#neos-page-metainformation').data('context-__dimensions'));
			var that = this;
			$.each(this.get('controller.dimensions'), function(dimensionIndex, dimensionConfiguration) {
				$.each(dimensionConfiguration.get('presets'), function (presetIndex, presetConfiguration) {
					if (JSON.stringify(that.get('controller.selectedDimensions.' + dimensionConfiguration.identifier)) === JSON.stringify(presetConfiguration.get('values'))) {
						that.$('select').val(presetConfiguration.identifier);
						that.$('select').trigger('chosen:updated');
					}
				});

			});

			this._updateValue(true);
		}
	});
});