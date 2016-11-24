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
	'Shared/HelpMessage',
	'Shared/LocalStorage',
	'Content/Model/NodeSelection',
	'Content/Application',
	'Content/LoadingIndicator',
	'Shared/I18n',
	'create',
	'vie',
	'Shared/EventDispatcher',
	'Shared/Notification'
], function(
	Ember,
	$,
	SecondaryInspectorController,
	_,
	Backbone,
	HelpMessage,
	LocalStorage,
	NodeSelection,
	ContentModule,
	LoadingIndicator,
	I18n,
	CreateJS,
	vie,
	EventDispatcher,
	Notification
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
		listeners: Ember.Object.create(),
		registeredEditors: Ember.Object.create(),

		activeTab: 'default',

		init: function() {
			if (LocalStorage.getItem('inspectorMode') !== false) {
				this.set('inspectorMode', true);
			}
			this.set('configuration', LocalStorage.getItem('inspectorConfiguration') || {});

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
		 * and views belonging to each group are displayed.
		 *
		 * Thus, the output looks possibly as follows:
		 * - Default
		 *    - Visibility
		 *       - _hidden (boolean)
		 *       - _starttime (date)
		 *    - Image Settings
		 *    - image (file upload)
		 */
		groupedViews: function() {
			var selectedNodeSchema,
				propertiesArray,
				sortedPropertiesArray,
				declaredViews,
				viewsArray,
				sortedViewsArray,
				groupsObject,
				tabsObject,
				sortedGroupsArray,
				sortedTabsArray,
				listeners;

			selectedNodeSchema = NodeSelection.get('selectedNodeSchema');
			if (!selectedNodeSchema || !selectedNodeSchema.properties) {
				return [];
			}

			// properties
			propertiesArray = [];
			listeners = Ember.Object.create();
			for (var property in selectedNodeSchema.properties) {
				if (selectedNodeSchema.properties.hasOwnProperty(property) && selectedNodeSchema.properties[property]) {
					var isBoolean = selectedNodeSchema.properties[property].type === 'boolean';
					propertiesArray.push($.extend({
						key: property,
						elementId: Ember.generateGuid(),
						isBoolean: isBoolean
					}, selectedNodeSchema.properties[property]));

					// we need to register any configured listeners for other properties.
					if (selectedNodeSchema.properties[property].ui && selectedNodeSchema.properties[property].ui.inspector && selectedNodeSchema.properties[property].ui.inspector.editorListeners) {
						for (var listenerName in selectedNodeSchema.properties[property].ui.inspector.editorListeners) {
							var observedProperty = selectedNodeSchema.properties[property].ui.inspector.editorListeners[listenerName].property;
							var handler = selectedNodeSchema.properties[property].ui.inspector.editorListeners[listenerName].handler;
							var options = selectedNodeSchema.properties[property].ui.inspector.editorListeners[listenerName].handlerOptions || {};
							if (!listeners[observedProperty]) {
								listeners.set(observedProperty, Ember.Object.create());
							}
							if (!listeners.get(observedProperty + '.' + property)) {
								listeners.set(observedProperty + '.' + property, Ember.Object.create());
							}
							listeners.set(observedProperty + '.' + property + '.' + listenerName, {handler: handler, options: options});
						}
					}
				}
			}

			this.set('listeners', listeners);

			sortedPropertiesArray = propertiesArray.sort(function(a, b) {
				return (Ember.get(a, 'ui.inspector.position') || 9999) - (Ember.get(b, 'ui.inspector.position') || 9999);
			});

			declaredViews = Ember.get(selectedNodeSchema, 'ui.inspector.views') || [];

			viewsArray = [];
			for (var key in declaredViews) {
				if (declaredViews.hasOwnProperty(key)) {
					viewsArray.push($.extend({
						key: key,
						elementId: Ember.generateGuid()
					}, declaredViews[key]));
				}
			}

			sortedViewsArray = viewsArray.sort(function(a, b) {
				return (Ember.get(a, 'position') || 9999) - (Ember.get(b, 'position') || 9999);
			});

			// groups
			groupsObject = $.extend(true, {}, Ember.get(selectedNodeSchema, 'ui.inspector.groups'));
			for (groupName in groupsObject) {
				groupsObject[groupName].label = I18n.translate(groupsObject[groupName].label);
			}

			// tabs
			tabsObject = $.extend(true, {}, Ember.get(selectedNodeSchema, 'ui.inspector.tabs'));
			for (tabName in tabsObject) {
				tabsObject[tabName].label = I18n.translate(tabsObject[tabName].label);
			}

			// build nested structure
			sortedGroupsArray = this._assignPropertiesAndViewsToGroups(sortedPropertiesArray, sortedViewsArray, groupsObject);
			sortedTabsArray = this._assignGroupsToTabs(sortedGroupsArray, tabsObject);

			return sortedTabsArray;
		}.property('nodeSelection.selectedNodeSchema'),

		_assignPropertiesAndViewsToGroups: function(sortedPropertiesArray, sortedViewsArray, groupsObject) {
			var groupsArray;

			// 1. assign properties to groups
			sortedPropertiesArray.forEach(function(property) {
				var groupIdentifier = Ember.get(property, 'ui.inspector.group');

				if (groupIdentifier in groupsObject) {
					if (groupsObject.hasOwnProperty(groupIdentifier) && groupsObject[groupIdentifier]) {
						if (!groupsObject[groupIdentifier].properties) {
							groupsObject[groupIdentifier].properties = [];
						}
						property.ui.label = I18n.translate(property.ui.label);

						if (property.ui.help) {
							helpMessage = HelpMessage(property.ui.help, property.ui.label);
							if (helpMessage !== '') {
								property.ui.help.message = helpMessage;
							}
						}

						groupsObject[groupIdentifier].properties.push(property);
					}
				}
			});

			// 2. assign views to groups
			sortedViewsArray.forEach(function(view) {
				var groupIdentifier = Ember.get(view, 'group');
				if (groupIdentifier in groupsObject) {
					if (groupsObject.hasOwnProperty(groupIdentifier) && groupsObject[groupIdentifier]) {
						if (!groupsObject[groupIdentifier].views) {
							groupsObject[groupIdentifier].views = [];
						}
						groupsObject[groupIdentifier].views.push(view);
					}
				}
			});

			// 3. transform object into array
			groupsArray = [];
			for (var groupIdentifier in groupsObject) {
				if (groupsObject.hasOwnProperty(groupIdentifier) && groupsObject[groupIdentifier]
					&& ((groupsObject[groupIdentifier].properties && groupsObject[groupIdentifier].properties.length)
					|| (groupsObject[groupIdentifier].views && groupsObject[groupIdentifier].views.length))) {
					groupsArray.push($.extend({group: groupIdentifier}, groupsObject[groupIdentifier]));
				}
			}

			// 4. sort
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
			var groupedViews = this.get('groupedViews');
			if (this.get('preferredTab')) {
				var preferredTab = groupedViews.findBy('tab', this.get('preferredTab'));
				if (preferredTab) {
					this.set('activeTab', preferredTab.get('tab'));
					this.set('preferredTab', null);
					return;
				}
			}
			var activeTabExists = groupedViews.findBy('tab', this.get('activeTab'));
			if (!activeTabExists) {
				this.set('preferredTab', this.get('activeTab'));
				var firstTab = groupedViews.objectAt(0);
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

			this.set('registeredEditors', Ember.Object.create());

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
		 * To make change listeners between editors work, the editor views are registered via this function, so that the inspector knows about all editors.
		 */
		registerPropertyEditor: function(propertyName, editor) {
			this.set('registeredEditors.' + propertyName, editor);
		},

		/**
		 * This is triggered when one of the editors was changed even before the change was applied.
		 */
		registerPendingChange: function(propertyName, value) {
			var registeredListeners;

			registeredListeners = this.get('listeners.' + propertyName);
			if (!registeredListeners) {
				return;
			}

			for (var observerProperty in registeredListeners) {
				if (registeredListeners.hasOwnProperty(observerProperty)) {
					var listenersInProperty = registeredListeners.get(observerProperty);
					for (var listenerName in listenersInProperty) {
						if (listenersInProperty.hasOwnProperty(listenerName)) {
							var handlerConfiguration = listenersInProperty[listenerName];
							this._applyChangeHandler(handlerConfiguration, this.get('registeredEditors.' + observerProperty + '.currentView'), listenerName, propertyName, value);
						}
					}
				}
			}
		},

		_applyChangeHandler: function(handlerConfiguration, editor, listenerName, observedProperty, newValue) {
			require({context: 'neos'}, [handlerConfiguration.handler], function (handlerClass) {
				Ember.run(function () {
					var handler = handlerClass.create(handlerConfiguration.options);
					handler.handle(editor, newValue, observedProperty, listenerName);
				});
			}, function () {
				if (window.console && window.console.error) {
					window.console.error('Couldn\'t create handler "' + handlerConfiguration.handler + '" assigned to "' + listenerName + '"! Please check your configuration.');
				}
				Notification.error('Error in inspector', 'Inspector change handler could not be loaded. See console for further details.');
			});
		},

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
				reloadElement = false,
				reloadPage = false,
				selectedNode = this.get('selectedNode'),
				propertyValuePostprocessPromises = [],
				propertyPromise,
				changedAttributes = {};

			_.each(this.get('cleanProperties'), function(cleanPropertyValue, key) {
				var valueOrPromise = that.get('nodeProperties').get(key);

				// check if the value is actually a promise
				if (valueOrPromise._propertyChangePromiseClosure) {
					propertyPromise = valueOrPromise._propertyChangePromiseClosure();
				} else {
					propertyPromise = Ember.RSVP.Promise(function (resolve, reject) {
						resolve(valueOrPromise);
					});
				}

				propertyValuePostprocessPromises.push(propertyPromise.then(function(value) {
					if (value !== cleanPropertyValue) {
						changedAttributes[key] = value;
						if (Ember.get(nodeTypeSchema, 'properties.' + key + '.ui.reloadPageIfChanged')) {
							reloadPage = true;
						} else if (Ember.get(nodeTypeSchema, 'properties.' + key + '.ui.reloadIfChanged')) {
							reloadElement = true;
						}
					}
				}));
			});

			Ember.RSVP.all(propertyValuePostprocessPromises).then(function() {
				selectedNode.setAttributes(changedAttributes, {validate: false});
				var entity = that.get('selectedNode._vieEntity');
				if (entity.isValid() !== true) {
					return;
				}

				// Reload element is only possible if the element is inside a content collection
				var isInsideCollection = typeof entity._enclosingCollectionWidget !== 'undefined';
				if (!isInsideCollection) {
					reloadPage = true;
					reloadElement = false;
				}

				if (reloadPage === true || reloadElement === true) {
					LoadingIndicator.start();
				}
				Backbone.sync('update', entity, {
					success: function (model, result, xhr) {
						if (reloadPage !== true && reloadElement !== true) {
							return;
						}
						if (reloadPage === true) {
							if (result && result.data && result.data.nextUri) {
								// It might happen that the page has been renamed, so we need to take the server-side URI
								ContentModule.loadPage(result.data.nextUri);
								return;
							}
						} else if (reloadElement === true) {
							if (result && result.data && result.data.collectionContent) {
								LoadingIndicator.done();
								var id = entity.id.slice(1, -1);
									$element = $('[about="' + id + '"]').first(),
									content = $(result.data.collectionContent).find('[about="' + xhr.getResponseHeader('X-Neos-AffectedNodePath') + '"]').first();
								if (content.length === 0) {
									console.warn('Node could not be found in rendered collection.');
									ContentModule.reloadPage();
									return;
								}

								var entityView = vie.service('rdfa')._getViewForElement($element);
								entityView.$el.replaceWith(content);
								var $newElement = $('[about="' + id + '"]');
								entityView.el = $newElement.get(0);
								entityView.$el = $newElement;
								CreateJS.refreshEdit(entityView.el);
								NodeSelection.replaceEntityWrapper($newElement, true);
								NodeSelection.updateSelection($newElement);
								EventDispatcher.trigger('contentChanged');
								return;
							}
						}
						ContentModule.reloadPage();
					},
					render: (isInsideCollection && reloadElement)
				});

				that.set('modified', false);

				cleanProperties = that.get('selectedNode.attributes');
				that.set('cleanProperties', cleanProperties);
				that.set('nodeProperties', Ember.Object.create(cleanProperties));
				SecondaryInspectorController.hide();
			}).fail(function(error) {
				console.error(error);
			});
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
