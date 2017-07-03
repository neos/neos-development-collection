define([
	'Library/jquery-with-dependencies',
	'emberjs',
	'Shared/HttpClient',
	'Content/Inspector/InspectorController',
	'Shared/I18n',
	'Shared/Utility',
	'Shared/MapObject',
	'Library/sortable/Sortable'
], function($, Ember, HttpClient, InspectorController, I18n, Utility, MapObject, Sortable) {
	/**
	 * Allow for options without a group
	 */
	Ember.Select.reopen({
		groupedContent: Ember.computed(function() {
			var groupPath = this.get('optionGroupPath');
			var groupedContent = Ember.A();

			this.get('content').forEach(function(item) {
				var label = Ember.get(item, groupPath);

				if (label) {
					if (Ember.get(groupedContent, 'lastObject.label') !== label) {
						groupedContent.pushObject({
							label: label,
							content: Ember.A()
						});
					}

					Ember.get(groupedContent, 'lastObject.content').push(item);
				} else {
					groupedContent.push(item);
				}
			});

			return groupedContent;
		}).property('optionGroupPath', 'content.@each'),

		groupView: Ember.ContainerView.extend({
			childViews: ['view'],
			init: function() {
				if (this.get('content')) {
					this.set('view', Ember.SelectOptgroup.extend({
						contentBinding: 'parentView.content',
						labelBinding: 'parentView.label',
						selectionBinding: 'parentView.parentView.selection',
						multipleBinding: 'parentView.parentView.multiple',
						optionLabelPathBinding: 'parentView.parentView.optionLabelPath',
						optionValuePathBinding: 'parentView.parentView.optionValuePath',

						itemViewClassBinding: 'parentView.parentView.optionView'
					}));
				} else {
					this.set('view', Ember.SelectOption.extend({
						content: this
					}));
				}
				this._super();
			}
		})
	});

	/** Allow for disabled options */
	Ember.SelectOption.reopen({
		attributeBindings: ['value', 'selected', 'disabled', 'icon'],

		disabled: function() {
			var content = this.get('content');
			return content.disabled || false;
		}.property('content'),

		icon: function() {
			return this.get('content.icon');
		}.property('content')
	});

	return Ember.Select.extend({
		value: null,
		values: [],
		allowEmpty: false,
		multiple: false,

		placeholder: '',
		_placeholder: function () {
			return I18n.translate(this.get('placeholder'));
		}.property('placeholder'),

		dataSourceIdentifier: null,
		dataSourceUri: null,
		dataSourceAdditionalData: {},
		elementInserted: false,
		initialLoadDone: false,

		attributeBindings: ['size', 'disabled', 'multiple'],
		optionLabelPath: 'content.label',
		optionValuePath: 'content.value',
		minimumResultsForSearch: 5,

		init: function() {
			this._super();
			this.set('dataSourceAdditionalData', MapObject.create(this.get('dataSourceAdditionalData')));
			this.set('elementInserted', false);
			this.off('didInsertElement', this, this._triggerChange);
			this.on('change', function() {
				this._change();
				this._updateValue();
			});
		},

		content: function() {
			var that = this,
				options = [],
				values = this.get('values'),
				currentValue = this.get('value');

			if (Ember.get(values, Object.keys(values)[0] + '.group')) {
				this.set('optionGroupPath', 'group');
			}

			if (this.get('allowEmpty')) {
				var emptyOptionValues = {value: ''};
				options.push(Ember.Object.create(emptyOptionValues));
			}

			$.each(values, function(value, configuration) {
				if (configuration === null) {
					return;
				}

				if (configuration.label) {
					configuration.label = I18n.translate(configuration.label);
				}

				options.push(Ember.Object.create($.extend({
					selected: that.get('multiple') && Array.isArray(currentValue) ? currentValue.indexOf(value) !== -1 : (that.get('propertyType') === 'integer' ? parseInt(value, 10) === parseInt(currentValue, 10) : value === currentValue),
					value: value,
					disabled: configuration.disabled
				}, configuration)));
			});

			Ember.run.next(function() {
				that.valueDidChange();
			});

			return options;
		}.property('values.@each'),

		valueDidChange: function() {
			var that = this,
				content = this.get('content'),
				value = this.get('multiple') && this.get('value') ? JSON.parse(this.get('value')) : this.get('value'),
				valuePath = this.get('optionValuePath').replace(/^content\.?/, ''),
				selection = content ? content.filter(function(object) {
					var optionValue = valuePath ? Ember.get(object, valuePath) : object;
					return Array.isArray(value) ? value.indexOf(optionValue) !== -1 : (that.get('propertyType') === 'integer' ? parseInt(value, 10) === parseInt(optionValue, 10) : value === optionValue);
				}) : null;
			if (!selection) {
				return;
			}
			if (this.get('multiple') || (value !== (valuePath ? this.get('selection.' + valuePath) : this.get('selection')))) {
				this.set('selection', this.get('multiple') ? selection : selection[0]);
				Ember.run.next(function() {
					if (that.$()) {
						if (that.get('multiple')) {
							var data = value.length > 0 ? value.map(function (val) {
								return {id: val, text: selection.length > 0 ? selection.findBy('value', val).label : val};
							}) : null;
							that.$().select2('data', data);
						} else {
							that.$().trigger('change');
						}
					}
				});
			}
		}.observes('value'),

		_updateValue: function() {
			if (this.get('values').length === 0) {
				return;
			}
			var selection = this.get('selection');
			if (this.get('multiple')) {
				var selectedValues = selection.length > 0 ? this.$().select2('data').map(function(option) {
					return option.id;
				}) : null;
				this.set('value', selectedValues ? JSON.stringify(selectedValues) : '');
			} else if (selection) {
				this.set('value', this.get('propertyType') === 'integer' ? parseInt(selection.value, 10) : selection.value);
			}
		},

		didInsertElement: function() {
			this._initializeSelect2();
			this.set('elementInserted', true);
		},

		_refreshDataFromDataSource: function() {
			if (!this.get('elementInserted') || !(this.get('dataSourceUri') || this.get('dataSourceIdentifier'))) {
				return;
			}

			var that = this,
				dataSourceUri = this.get('dataSourceUri') || HttpClient._getEndpointUrl('neos-data-source') + '/' + this.get('dataSourceIdentifier'),
				parameters = Utility.getKeyValueArray(this.get('dataSourceAdditionalData').getAllProperties());

			parameters.push({
				name: 'node',
				value: InspectorController.nodeSelection.get('selectedNode.nodePath')
			});

			this._loadValuesFromController(dataSourceUri, parameters, function(options) {
				that.set('values', options);

				if (that.get('initialLoadDone')) {
					that._matchValueAgainstOptions(options);
				} else {
					that.set('initialLoadDone', true);
					// trigger listeners that might be registered
					that.get('inspector').registerPendingChange(that.get('property'), that.get('value'));
				}
			});
		}.observes('elementInserted', 'dataSourceUri', 'dataSourceIdentifier', 'dataSourceAdditionalData.changed'),

		// this is used to remove options no longer available after a data source refresh
		_matchValueAgainstOptions: function(options) {
			var value, availableValues, newValue;

			value = this.get('multiple') && this.get('value') ? JSON.parse(this.get('value')) : this.get('value');

			if (this.get('multiple')) {
				availableValues = options.filter(function (option) {
					return value.filter(function (value) {
							return value.value === option.value;
						}).length > 0
				});
				newValue = availableValues.length > 0 ? JSON.stringify(availableValues) : '';
			} else {
				newValue = options.filter(
					function (option) {
						return option.value === value;
					}).length > 0 ? value : '';
			}

			if (newValue !== this.get('value')) {
				this.set('value', newValue);
			}
		},

		_initializeSelect2: function() {
			this.$().select2('destroy').select2({
				maximumSelectionSize: this.get('multiple') ? 0 : 1,
				minimumResultsForSearch: this.get('minimumResultsForSearch'),
				allowClear: this.get('allowEmpty') || this.get('content.0.value') === '',
				placeholder: this.get('_placeholder'),
				relative: true,
				formatSelection: function (data, container, escapeMarkup) {
					var icon = $(data.element).attr('icon');
					return data ? '<span title="' + escapeMarkup(data.text) + '">' + (icon ? '<i class="' + icon + '"></i>' : '') + escapeMarkup(data.text) + '</span>' : undefined;
				},
				formatResult: function (result, container, query, escapeMarkup) {
					var markup = [],
						icon = $(result.element).attr('icon');
					Utility.Select2.util.markMatch(result.text, query.term, markup, escapeMarkup);
					container.attr('title', escapeMarkup(result.text));
					return (icon ? '<i class="' + icon + '"></i>' : '') + markup.join('');
				}
			}).on('select2-open', function() {
				var that = this,
					parent = $(this).parent(),
					document = $('#neos-application');
				$('.neos-select2-offscreen', '#neos-inspector').not(this).each(function() {
					$(this).select2('close');
				});
				document.on('click.select2-custom', document, function(event) {
					if (!$.contains(parent, event.target)) {
						$(that).select2('close');
						document.off('click.select2-custom');
					}
				});
				parent.css('padding-bottom', $('#neos-select2-drop').height());
			}).on('select2-close', function() {
				$(this).parent().css('padding-bottom', 0);
				$('#neos-application').off('click.select2-custom');
			});

			if (this.get('multiple')) {
				this._makeSortable();
			}
		},

		_makeSortable: function() {
			var select2Ul, sortable, that = this;
			select2Ul = this.$().select2('container').find('ul.neos-select2-choices').first().addClass('neos-sortable');
			sortable = Sortable.create(select2Ul.get(0), {
				ghostClass: 'neos-sortable-ghost',
				chosenClass: 'neos-sortable-chosen',
				draggable: '.neos-select2-search-choice',
				onUpdate: function (event) {
					var values = [];
					select2Ul.find('.neos-select2-search-choice').each(function() {
						values.push($(this).data('select2-data').id);
					});
					that.set('value', JSON.stringify(values));
				}
			});
		},

		_placeholderDidChange: function() {
			if (this.$()) {
				this._initializeSelect2();
			}
		}.observes('_placeholder'),

		/**
		 * @param {string} dataSourceUri
		 * @param {array|function} parameters
		 * @param {function} callback
		 * @private
		 */
		_loadValuesFromController: function(dataSourceUri, parameters, callback) {
			if (typeof parameters === 'function') {
				callback = parameters;
				parameters = [];
			}
			if (parameters.length === 0) {
				parameters = [{name: 'node', value: InspectorController.nodeSelection.get('selectedNode.nodePath')}];
			}
			HttpClient.getResource(dataSourceUri + (parameters ? '?' + $.param(parameters) : ''), {dataType: 'json'}).then(callback);
		}
	});
});
