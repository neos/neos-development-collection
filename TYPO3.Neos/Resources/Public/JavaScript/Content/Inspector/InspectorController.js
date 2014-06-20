/**
 * Inspector
 */
define(
[
	'emberjs',
	'Library/jquery-with-dependencies',
	'./SecondaryInspectorController',
	'Library/underscore',
	'Library/backbone',
	'Shared/LocalStorage',
	'Content/Model/NodeSelection',
	'Content/Application',
	'Content/LoadingIndicator'
], function(
	Ember,
	$,
	SecondaryInspectorController,
	_,
	Backbone,
	LocalStorage,
	NodeSelection,
	ContentModule,
	LoadingIndicator
) {
	/**
	 * The Inspector is displayed on the right side of the page.
	 *
	 * Furthermore, it contains *Editors*
	 */
	return Ember.Object.extend({
		nodeSelection: NodeSelection,

		inspectorMode: false,

		modified: false,
		unmodified: function() {
			return !this.get('modified');
		}.property('modified'),

		validationErrors: Ember.Object.create(),
		hasValidationErrors: false,

		nodeProperties: Ember.Object.create(),
		configuration: null,

		selectedNode: null,
		cleanProperties: null,

		init: function() {
			if (LocalStorage.getItem('inspectorMode') !== false) {
				this.set('inspectorMode', true);
			}
			this.set('configuration', LocalStorage.getItem('inspectorConfiguration') || {});
		},

		onConfigurationChanged: function() {
			var configuration = this.get('configuration');
			if ($.isEmptyObject(configuration) === false) {
				LocalStorage.setItem('inspectorConfiguration', configuration);
			}
		}.observes('configuration'),

		toggleInspectorMode: function() {
			var state = !this.get('inspectorMode');
			this.set('inspectorMode', state);
			LocalStorage.setItem('inspectorMode', state);
		},

		/**
		 * This is a computed property which builds up a nested array powering the
		 * Inspector. It essentially contains two levels: On the first level,
		 * the groups are displayed, while on the second level, the properties
		 * belonging to each group are displayed.
		 *
		 * Thus, the output looks possibly as follows:
		 * - Visibility
		 *   - _hidden (boolean)
		 *   - _starttime (date)
		 * - Image Settings
		 *   - image (file upload)
		 */
		groupedPropertyViews: function() {
			var selectedNodeSchema = NodeSelection.get('selectedNodeSchema');
			if (!selectedNodeSchema || !selectedNodeSchema.properties) {
				return [];
			}

			var inspectorGroups = Ember.get(selectedNodeSchema, 'ui.inspector.groups');
			if (!inspectorGroups) {
				return [];
			}

			var groupedPropertyViews = [],
				nodeProperties = this.get('nodeProperties');

			$.each(inspectorGroups, function(groupIdentifier, propertyGroupConfiguration) {
				var properties = [];
				$.each(selectedNodeSchema.properties, function(propertyName, propertyConfiguration) {
					if (propertyName in nodeProperties) {
						if (Ember.get(propertyConfiguration, 'ui.inspector.group') === groupIdentifier) {
							properties.push($.extend({key: propertyName, elementId: Ember.generateGuid(), isBoolean: propertyConfiguration.type === 'boolean'}, propertyConfiguration));
						}
					}
				});

				if (properties.length <= 0) {
					return;
				}

				properties.sort(function(a, b) {
					return (Ember.get(a, 'ui.inspector.position') || 9999) - (Ember.get(b, 'ui.inspector.position') || 9999);
				});

				groupedPropertyViews.push($.extend({}, propertyGroupConfiguration, {
					properties: properties,
					group: groupIdentifier
				}));
			});
			groupedPropertyViews.sort(function(a, b) {
				return (a.position || 9999) - (b.position || 9999);
			});

			return groupedPropertyViews;
		}.property('nodeSelection.selectedNodeSchema'),

		/**
		 * If "true", we show "save" and "cancel" and behave as if the user edited
		 * the node's properties in a "transaction" (default case for normal editors,
		 * if a node is selected).
		 *
		 * If "false", we hide "save" and "cancel", and the UI controls are responsible
		 * for saving themselves. needed for Aloha.
		 */
		_enableTransactionalInspector: true,

		/**
		 * When the selected block changes in the content model,
		 * we update this.nodeProperties
		 */
		onSelectedNodeChange: function() {
			var selectedNode = NodeSelection.get('selectedNode'),
				cleanProperties = {},
				enableTransactionalInspector = true;

			SecondaryInspectorController.hide();
			this.set('selectedNode', selectedNode);

			if (selectedNode) {
				cleanProperties = selectedNode.get('attributes');
				if (selectedNode.get('_enableTransactionalInspector') === false) {
					enableTransactionalInspector = false;
				}
			}
			if (enableTransactionalInspector) {
				this.set('_enableTransactionalInspector', true);
				this.set('cleanProperties', cleanProperties);
				this.set('nodeProperties', Ember.Object.create(cleanProperties));
			} else {
				this.set('_enableTransactionalInspector', false);
				this.set('cleanProperties', {});
				this.set('nodeProperties', {});
			}
		}.observes('nodeSelection.selectedNode').on('init'),

		/**
		 * We'd like to monitor *every* property change except inline editable ones,
		 * that's why we have to look through the list of properties...
		 */
		_registerGenericNodePropertyChangeEventListener: function() {
			var nodeProperties = this.get('nodeProperties'),
				that = this;
			$.each(this.get('cleanProperties'), function(propertyName) {
				nodeProperties.addObserver(propertyName, null, function() {
					that._somePropertyChanged();
				});
			});
		}.observes('nodeProperties'),

		// Some hack which is fired when we change a property. Should be replaced with a proper API method which should be fired *every time* a property is changed.
		_somePropertyChanged: function() {
			var that = this,
				hasChanges = false,
				hasValidationErrors = false;

			_.each(this.get('cleanProperties'), function(cleanPropertyValue, propertyName) {
				var value = that.get('nodeProperties').get(propertyName),
					errors = that.get('selectedNode._vieEntity').validateAttribute(propertyName, value),
					existingErrors = that.get('validationErrors.' + propertyName) || [];
				if (existingErrors.length !== errors.length) {
					that.set('validationErrors.' + propertyName, errors);
				}
				if (errors.length > 0) {
					hasValidationErrors = true;
				}
				if (value !== cleanPropertyValue) {
					hasChanges = true;
				}
			});

			this.set('modified', hasChanges);
			this.set('hasValidationErrors', hasValidationErrors);
		},

		isPropertyModified: function(propertyName) {
			return this.get('cleanProperties.' + propertyName) !== this.get('nodeProperties.' + propertyName);
		},

		/**
		 * Apply the edited properties back to the node proxy
		 */
		apply: function() {
			var that = this,
				cleanProperties,
				nodeTypeSchema = NodeSelection.get('selectedNodeSchema'),
				reloadPage = false,
				selectedNode = this.get('selectedNode');

			_.each(this.get('cleanProperties'), function(cleanPropertyValue, key) {
				var value = that.get('nodeProperties').get(key);
				if (value !== cleanPropertyValue) {
					selectedNode.setAttribute(key, value, {validate: false});
					if (value !== cleanPropertyValue) {
						if (Ember.get(nodeTypeSchema, 'properties.' + key + '.ui.reloadIfChanged')) {
							reloadPage = true;
						}
					}
				}
			});

			var entity = this.get('selectedNode._vieEntity');
			if (entity.isValid() !== true) {
				return;
			}

			if (reloadPage === true) {
				LoadingIndicator.start();
			}

			Backbone.sync('update', entity, {
				success: function(model, result) {
					if (reloadPage === true) {
						if (result && result.data && result.data.nextUri) {
							// It might happen that the page has been renamed, so we need to take the server-side URI
							ContentModule.loadPage(result.data.nextUri);
						} else {
							ContentModule.reloadPage();
						}
					}
				}
			});

			this.set('modified', false);

			cleanProperties = this.get('selectedNode.attributes');
			this.set('cleanProperties', cleanProperties);
			this.set('nodeProperties', Ember.Object.create(cleanProperties));
			SecondaryInspectorController.hide();
		},

		/**
		 * Revert all changed properties
		 */
		revert: function() {
			this.set('nodeProperties', Ember.Object.create(this.get('cleanProperties')));
			this.set('modified', false);
			SecondaryInspectorController.hide();
			this._somePropertyChanged();
		}
	}).create();
});