define(
[
	'Library/jquery-with-dependencies',
	'emberjs'
],
function($, Ember) {
	var SelectboxOption = Ember.View.extend({
		tagName: 'option',
		attributeBindings: ['value', 'selected', 'disabled'],
		valueBinding: 'content.value',
		selectedBinding: 'content.selected',
		disabled: function() {
			if (this.get('content.disabled')) {
				return 'disabled';
			}
			return null;
		}.property('content.disabled'),
		template: Ember.Handlebars.compile('{{unbound view.content.label}}')
	});

	return Ember.CollectionView.extend({
		classNames: ['typo3-form-selectbox'],

		tagName: 'select',
		contentBinding: 'options',
		itemViewClass: SelectboxOption,

		value: '',
		allowEmpty: false,
		placeholder: '',

		attributeBindings: ['size', 'disabled'],

		values: [ ],

		init: function() {
			this._super();
			this.get('options');
		},

		options: function() {
			var options = [],
				values = this.get('values'),
				currentValue = this.get('value');

			if (this.get('allowEmpty')) {
				options.push(Ember.Object.create({value: '', label: this.get('placeholder')}));
			}

			$.each(values, function(value) {
				options.push(Ember.Object.create($.extend({
					selected: value === currentValue,
					value: value,
					disabled: value && values[value] && values[value].disabled
				}, this)));
			});

			return options;
		}.property('values.@each', 'value', 'placeholder', 'allowEmpty'),

		onItemsChange: function() {
			var that = this;

			this.$().attr('data-placeholder', that.get('placeholder'));
			Ember.run.next(function() {
				that.$().trigger('chosen:updated');
			});
		}.observes('values.@each'),

		_loadValuesFromController: function(uri, callback) {
			$.getJSON(uri, function(results) {
				Ember.run(function() {
					callback(results);
				});
			});
		},

		didInsertElement: function() {
			var that = this;

			if (this.get('placeholder')) {
				this.$().attr('data-placeholder', this.get('placeholder'));
			}

			// TODO Check value binding
			var chosen;
			this.$().addClass('chosen-select').on('chosen:ready', function(event, parameters) {
				chosen = parameters.chosen;
			}).chosen({allow_single_deselect: true}).change(function() {
				that.set('value', that.$().val());
			});
			chosen.search_results.off('mousewheel.chosen DOMMouseScroll.chosen');
		}
	});
});