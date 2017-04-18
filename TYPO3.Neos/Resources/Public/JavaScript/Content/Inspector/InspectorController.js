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
	'Shared/Configuration',
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
	Configuration,
	LocalStorage,
	NodeSelection,
	ContentModule,
	LoadingIndicator
) {

	/**
	 * Is used for the first level nesting in the inspector
	 */
	var InspectorTab = Ember.Object.extend({
		_inspectorController: Ember.required,
		tab: Ember.required,
		isActive: function() {
			return this.get('_inspectorController.activeTab') === this.get('tab');
		}.property('_inspectorController.activeTab')
	});

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

		activeTab: 'default',

		showBreadcrumb: true,

		init: function() {
			if (LocalStorage.getItem('inspectorMode') !== false) {
				this.set('inspectorMode', true);
			}
			this.set('configuration', LocalStorage.getItem('inspectorConfiguration') || {});
			this.set('showBreadcrumb', Configuration.get('UserInterface.inspector.showBreadcrumb'));

			var activeTab = LocalStorage.getItem('activeInspectorTab');
			if (activeTab) {
				this.set('activeTab', activeTab);
			}
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

		toggleCurrentInspectorTab: function(tab) {
			this.set('activeTab', tab.get('tab'));
			LocalStorage.setItem('activeInspectorTab', tab.get('tab'));
		},

		/**
		 * This is a computed property which builds up a nested array powering the Inspector.
		 * It essentially contains three levels: On the first level, the tabs are configured,
		 * on the second level the groups are displayed, while on the third level, the properties
		 * belonging to each group are displayed.
		 *
		 * Thus, the output looks possibly as follows:
		 * - Default
		 *    - Visibility
		 *       - _hidden (boolean)
		 *       - _starttime (date)
		 *    - Image Settings
		 *    - image (file upload)
		 */
		groupedPropertyViews: function() {
			var selectedNodeSchema,
				propertiesArray,
				sortedPropertiesArray,
				groupsObject,
				tabsObject,
				sortedGroupsArray,
				sortedTabsArray;

			selectedNodeSchema = NodeSelection.get('selectedNodeSchema');
			if (!selectedNodeSchema || !selectedNodeSchema.properties) {
				return [];
			}

			// properties
			propertiesArray = [];
			for (var property in selectedNodeSchema.properties) {
				if (selectedNodeSchema.properties.hasOwnProperty(property) && selectedNodeSchema.properties[property]) {
					var isBoolean = selectedNodeSchema.properties[property].type === 'boolean';
					propertiesArray.push($.extend({
						key: property,
						elementId: Ember.generateGuid(),
						isBoolean: isBoolean
					}, selectedNodeSchema.properties[property]));
				}
			}
			sortedPropertiesArray = propertiesArray.sort(function(a, b) {
				return (Ember.get(a, 'ui.inspector.position') || 9999) - (Ember.get(b, 'ui.inspector.position') || 9999);
			});

			// groups
			groupsObject = $.extend(true, {}, Ember.get(selectedNodeSchema, 'ui.inspector.groups'));

			// tabs
			tabsObject = $.extend(true, {}, Ember.get(selectedNodeSchema, 'ui.inspector.tabs'));

			// build nested structure
			sortedGroupsArray = this._assignPropertiesToGroups(sortedPropertiesArray, groupsObject);
			sortedTabsArray = this._assignGroupsToTabs(sortedGroupsArray, tabsObject);

			return sortedTabsArray;
		}.property('nodeSelection.selectedNodeSchema'),

		_assignPropertiesToGroups: function(sortedPropertiesArray, groupsObject) {
			var groupsArray;

			// 1. assign properties to groups
			sortedPropertiesArray.forEach(function(property) {
				var groupIdentifier = Ember.get(property, 'ui.inspector.group');
				if (groupIdentifier in groupsObject) {
					if (groupsObject.hasOwnProperty(groupIdentifier) && groupsObject[groupIdentifier]) {
						if (!groupsObject[groupIdentifier].properties) {
							groupsObject[groupIdentifier].properties = [];
						}
						groupsObject[groupIdentifier].properties.push(property);
					}
				}
			});

			// 2. transform object into array
			groupsArray = [];
			for (var groupIdentifier in groupsObject) {
				if (groupsObject.hasOwnProperty(groupIdentifier) && groupsObject[groupIdentifier] && groupsObject[groupIdentifier].properties && groupsObject[groupIdentifier].properties.length) {
					groupsArray.push($.extend({group: groupIdentifier}, groupsObject[groupIdentifier]));
				}
			}

			// 3. sort
			groupsArray.sort(function(a, b) {
				return (Ember.get(a, 'position') || 9999) - (Ember.get(b, 'position') || 9999);
			});

			return groupsArray;
		},

		_assignGroupsToTabs: function(sortedGroupsArray, tabsObject) {
			var tabsArray;

			// 1. assign groups to tabs
			sortedGroupsArray.forEach(function(group) {
				var tabIdentifier = Ember.get(group, 'tab');
				// if a group is not assigned to a tab it is placed inside the 'default' group
				if (!tabIdentifier) {
					tabIdentifier = 'default';
				}
				if (tabIdentifier in tabsObject) {
					if (tabsObject.hasOwnProperty(tabIdentifier) && tabsObject[tabIdentifier]) {
						if (!tabsObject[tabIdentifier].groups) {
							tabsObject[tabIdentifier].groups = [];
						}
						tabsObject[tabIdentifier].groups.push(group);
					}
				} else {
					console.warn('The tab ' + tabIdentifier + ' does not exist!');
				}
			});

			// 2. transform object into array
			tabsArray = [];
			for (var tabIdentifier in tabsObject) {
				if (tabsObject.hasOwnProperty(tabIdentifier) && tabsObject[tabIdentifier] && tabsObject[tabIdentifier].groups && tabsObject[tabIdentifier].groups.length) {
					var inspectorTab = InspectorTab.create($.extend({tab: tabIdentifier, _inspectorController: this}, tabsObject[tabIdentifier]));
					tabsArray.push(inspectorTab);
				}
			}

			// 3. sort
			tabsArray.sort(function(a, b) {
				return (Ember.get(a, 'position') || 9999) - (Ember.get(b, 'position') || 9999);
			});

			return tabsArray;
		},

		_ensureTabSelection: function() {
			var groupedPropertyViews = this.get('groupedPropertyViews');
			if (this.get('preferredTab')) {
				var preferredTab = groupedPropertyViews.findBy('tab', this.get('preferredTab'));
				if (preferredTab) {
					this.set('activeTab', preferredTab.get('tab'));
					this.set('preferredTab', null);
					return;
				}
			}
			var activeTabExists = groupedPropertyViews.findBy('tab', this.get('activeTab'));
			if (!activeTabExists) {
				this.set('preferredTab', this.get('activeTab'));
				var firstTab = groupedPropertyViews.objectAt(0);
				if (firstTab) {
					this.set('activeTab', firstTab.get('tab'));
				}
			}
		}.observes('nodeSelection.selectedNodeSchema'),

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