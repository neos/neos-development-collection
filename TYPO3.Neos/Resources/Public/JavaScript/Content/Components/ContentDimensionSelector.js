define(
[
	'emberjs',
	'Library/jquery-with-dependencies',
	'./ContentDimensionController',
	'Shared/EventDispatcher',
	'Content/Model/NodeSelection',
	'text!./ContentDimensionSelector.html'
],
function(
	Ember,
	$,
	ContentDimensionController,
	EventDispatcher,
	NodeSelection,
	template
) {

	/**
	 * Ember view which displays the content dimensions selector
	 *
	 * The view consists of two parts: the dimension selector summary, which displays the currently selected dimensions
	 * even if the dimensions selector is collapsed, and the expandable selector which shows a selector box for each
	 * dimension with more than one preset.
	 */
	return Ember.View.extend({
		template: Ember.Handlebars.compile(template),
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
			this.$('.neos-content-dimension-selector-summary').on('click', function() {
				that.toggleProperty('isActive');
			});
		},

		/**
		 * Hide the dimensions selector if no dimensions can be selected anyway
		 */
		isVisible: function() {
			return this.get('controller.dimensions').length > 0;
		}.property('controller.dimensions'),

		init: function() {
			this._super();
			this._initialize();
		},

		/**
		 * (Re-)initialize the content dimension selectors
		 *
		 * When dimensions are available (REST service delivered a response) or they changed by some other means,
		 * a function is registered which calls _updateValue() when the user selected a different dimension preset in
		 * any of the selector boxes.
		 */
		_initialize: function() {
			Ember.run.next(this, function() {
				var that = this;
				this.$('select').chosen({disable_search_threshold: 10}).change(function() {
					that._updateValue();
					that.get('controller').reloadDocument();
				});
			});

		}.observes('controller.dimensions'),

		/**
		 * Update the currently selected dimension(s) in the selector box(es)
		 */
		_updateValue: function() {
			var controller = this.get('controller');
			this.$('select').each(function() {
				var dimensionIdentifier = $(this).attr('name');
				var dimensionPresetIdentifier = $(this).val();
				var dimension = controller.get('dimensions').filter(function(dimension) {
					return dimension.get('identifier') === dimensionIdentifier;
				}).get(0);

				dimension.set('selected', dimension.get('presets').filter(function(preset) {
					return preset.get('identifier') === dimensionPresetIdentifier;
				}).get(0));
			});
		}
	});
});