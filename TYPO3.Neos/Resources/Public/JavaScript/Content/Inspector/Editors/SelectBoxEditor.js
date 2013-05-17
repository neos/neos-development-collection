define(
[
	'Library/jquery-with-dependencies',
	'emberjs'
],
function($, Ember) {
	var SelectboxOption = Ember.View.extend({
		tagName: 'option',
		attributeBindings: ['value', 'selected'],
		valueBinding: 'content.value',
		selectedBinding: 'content.selected',

		template: Ember.Handlebars.compile('{{view.content.label}}')
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

		values: [],

		options: function() {
			var options = [],
				currentValue = this.get('value');

			if (this.get('allowEmpty')) {
				options.push(Ember.Object.create({value: '', label: this.get('placeholder')}));
			}
			$.each(this.get('values'), function(value) {
				options.push(Ember.Object.create($.extend({
					selected: value === currentValue,
					value: value
				}, this)));
			});
			return options;
		}.property('values', 'value', 'placeholder', 'allowEmpty'),

		onItemsChange: function() {
			// Special event for chosen
			this.$().trigger('liszt:updated');
		}.observes('values'),

		didInsertElement: function() {
			var that = this;

			if (this.get('placeholder')) {
				this.$().attr('data-placeholder', this.get('placeholder'));
			}

			// TODO Check value binding
			this.$().addClass('chzn-select').chosen({allow_single_deselect: true}).change(function() {
				that.set('value', that.$().val());
			});
		}
	});
});