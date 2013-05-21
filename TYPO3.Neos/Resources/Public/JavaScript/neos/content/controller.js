/**
 * Controllers which are not model- but appearance-related
 */

define(
['Content/Application', 'Library/jquery-with-dependencies', 'Library/underscore', 'Library/backbone', 'create', 'emberjs', 'vie/entity', 'neos/common', 'neos/content/model'],
function(ContentModule, $, _, Backbone, CreateJS, Ember, Entity) {
	if (window._requirejsLoadingTrace) window._requirejsLoadingTrace.push('neos/content/controller');

	var T3 = window.T3 || {};
	T3.Content = T3.Content || {};

	/**
	 * This controller toggles the preview mode on and off.
	 */
	var Preview = Ember.Object.extend({
		previewMode: false,

		init: function() {
			if (T3.Common.LocalStorage.getItem('previewMode') === true) {
				this.togglePreview();
			}
		},

		togglePreview: function() {
			this.set('previewMode', !this.get('previewMode'));
		},

		onTogglePreviewMode: function() {
			var that = this,
				isPreviewEnabled = this.get('previewMode'),
				previewCloseClass = 'neos-preview-close';
			if (isPreviewEnabled) {
				$('body')
					.append($('<div class="neos" />').addClass(previewCloseClass).append($('<button class="neos-button btn btn-mini pressed"><i class="icon-fullscreen"></i></button>'))
					.on('click', function() {
						that.set('previewMode', false);
					}));
				$(document).on('keyup.wireframe', function(e) {
					if (e.keyCode === 27) {
						that.set('previewMode', false);
					}
				});
				CreateJS.disableEdit();
			} else {
				$('body > .' + previewCloseClass).remove();
				$(document).off('keyup.wireframe');
				CreateJS.enableEdit();
			}
			$('body').toggleClass('neos-previewmode neos-controls');
		}.observes('previewMode'),

		onPreviewModeChange: function() {
			T3.Common.LocalStorage.setItem('previewMode', this.get('previewMode'));
		}.observes('previewMode')
	}).create();

	/**
	 * This controller toggles the wireframe mode on and off.
	 */
	var Wireframe = Ember.Object.extend({
		wireframeMode: false,

		init: function() {
			if (T3.Common.LocalStorage.getItem('wireframeMode') === true) {
				this.toggleWireframeMode();
				$('#neos-createcontentcollection-input').keypress(function(e) {
					if ((e.keyCode || e.which) === 13) {
						$('#neos-createcontentcollection-button').click();
					}
				});
				$('#neos-createcontentcollection-button').click(function() {
					var newContentCollectionName = $('#neos-createcontentcollection-input').val();
					if (newContentCollectionName === '') {
						T3.Common.Notification.error('You need to give a name for the new content collection.');
					} else {
						T3.Content.Controller.Wireframe.createContentCollection(newContentCollectionName);
					}
				});
			}
		},

		toggleWireframeMode: function() {
			this.set('wireframeMode', !this.get('wireframeMode'));
		},

		onWireframeModeChange: function () {
			var wireframeMode;
			wireframeMode = this.get('wireframeMode');
			if (typeof TYPO3_Neos_Service_ExtDirect_V1_Controller_UserController === 'object') {
				ContentModule.showPageLoader();
				TYPO3_Neos_Service_ExtDirect_V1_Controller_UserController.updatePreferences({'contentEditing.wireframeMode': wireframeMode}, function() {
					T3.Common.LocalStorage.setItem('wireframeMode', wireframeMode);
					window.location.reload(false);
				});
			}
		}.observes('wireframeMode'),

		createContentCollection: function(contentCollectionName) {
			var pageNodePath = $('#neos-page-metainformation').attr('about');
			ContentModule.showPageLoader();
			TYPO3_Neos_Service_ExtDirect_V1_Controller_NodeController.create(pageNodePath, {
				nodeType: 'TYPO3.Neos:ContentCollection',
				nodeName: contentCollectionName
			}, 'into',
			function (result) {
				if (result.success == true) {
					$('#neos-createcontentcollection-input').val('');
					ContentModule.reloadPage();
				}
			});
		}
	}).create();

	/**
	 * This controller toggles the page tree visibility on and off.
	 */
	var PageTree = Ember.Object.extend({
		pageTreeMode: false,

		init: function() {
			if (T3.Common.LocalStorage.getItem('pageTreeMode') === true) {
				$('body').addClass('neos-tree-panel-open');
				this.togglePageTreeMode();
			}
		},

		togglePageTreeMode: function() {
			this.set('pageTreeMode', !this.get('pageTreeMode'));
		},

		onPageTreeModeChange: function() {
			var pageTreeMode = this.get('pageTreeMode');
			if (typeof TYPO3_Neos_Service_ExtDirect_V1_Controller_UserController === 'object') {
				if (pageTreeMode === true) {
					$('body').addClass('neos-tree-panel-open');
				} else {
					$('body').removeClass('neos-tree-panel-open');
				}
				TYPO3_Neos_Service_ExtDirect_V1_Controller_UserController.updatePreferences({'contentEditing.pageTreeMode': pageTreeMode}, function() {
					T3.Common.LocalStorage.setItem('pageTreeMode', pageTreeMode);
				});
			}
		}.observes('pageTreeMode')
	}).create();

	/**
	 * This controller toggles the inspection mode on and off.
	 *
	 * @TODO: rename differently, because it is too similar with "Inspector"
	 * @TODO: Toggling inspectMode does not show popover
	 */
	var Inspect = Ember.Object.extend({
		inspectMode: false,

		onInspectModeChange: function() {
			var isInspectEnabled = this.get('inspectMode');
			if (isInspectEnabled) {
				$('body').addClass('neos-inspect-active');
			} else {
				$('body').removeClass('neos-inspect-active');
			}
		}.observes('inspectMode')
	}).create();

	/**
	 * Controller for the inspector
	 */
	var Inspector = Ember.Object.extend({
		_modified: false,
		_unmodified: function() {
			return !this.get('_modified');
		}.property('_modified'),

		inspectorMode: true,

		nodeProperties: null,
		configuration: null,

		selectedNode: null,
		cleanProperties: null,

		init: function() {
			this.set('nodeProperties', Ember.Object.create());
			this.set('configuration', T3.Common.LocalStorage.getItem('inspectorConfiguration') || {});
			Ember.addObserver(this, 'configuration', function() {
				if ($.isEmptyObject(this.get('configuration')) === false) {
					T3.Common.LocalStorage.setItem('inspectorConfiguration', this.get('configuration'));
				}
			});
			if (T3.Common.LocalStorage.getItem('inspectorMode') === false) {
				this.toggleInspectorMode();
			} else {
				$('body').addClass('neos-inspector-panel-open');
			}
		},

		/**
		 * Toggle inspector mode
		 */
		toggleInspectorMode: function() {
			this.set('inspectorMode', !this.get('inspectorMode'));
		},

		/**
		 * When inspector mode is changing
		 */
		onInspectorModeChange: function() {
			var inspectorMode = this.get('inspectorMode');
			if (typeof TYPO3_Neos_Service_ExtDirect_V1_Controller_UserController === 'object') {
				if (inspectorMode === true) {
					$('body').addClass('neos-inspector-panel-open');
				} else {
					$('body').removeClass('neos-inspector-panel-open');
				}
				TYPO3_Neos_Service_ExtDirect_V1_Controller_UserController.updatePreferences({
					'contentEditing.inspectorMode': inspectorMode
				}, function() {
					T3.Common.LocalStorage.setItem('inspectorMode', inspectorMode);
				});
			}
		}.observes('inspectorMode'),

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
		contentCollectionsAndViews: function() {
			var selectedNodeSchema = T3.Content.Model.NodeSelection.get('selectedNodeSchema');
			if (!selectedNodeSchema || !selectedNodeSchema.properties) {
				return [];
			}

			var inspectorGroups = Ember.get(selectedNodeSchema, 'ui.inspector.groups');
			if (!inspectorGroups) {
				return [];
			}

			var contentCollectionsAndViews = [];
			$.each(inspectorGroups, function(groupIdentifier, propertyGroupConfiguration) {
				var properties = [];
				$.each(selectedNodeSchema.properties, function(propertyName, propertyConfiguration) {
					if (Ember.get(propertyConfiguration, 'ui.inspector.group') === groupIdentifier) {
						properties.push($.extend({key: propertyName, elementId: Ember.generateGuid(), isBoolean: propertyConfiguration.type === 'boolean'}, propertyConfiguration));
					}
				});

				properties.sort(function(a, b) {
					return (Ember.get(a, 'ui.inspector.position') || 9999) - (Ember.get(b, 'ui.inspector.position') || 9999);
				});

				contentCollectionsAndViews.push($.extend({}, propertyGroupConfiguration, {
					properties: properties,
					group: groupIdentifier
				}));
			});
			contentCollectionsAndViews.sort(function(a, b) {
				return (a.position || 9999) - (b.position || 9999);
			});

			return contentCollectionsAndViews;
		}.property('T3.Content.Model.NodeSelection.selectedNodeSchema'),

		/**
		 * When the selected block changes in the content model,
		 * we update this.nodeProperties
		 */
		onSelectedNodeChange: function() {
			var selectedNode = T3.Content.Model.NodeSelection.get('selectedNode'),
				cleanProperties = {};
			this.set('selectedNode', selectedNode);
			if (selectedNode) {
				cleanProperties = selectedNode.get('attributes');
			}
			this.set('cleanProperties', cleanProperties);
			this.set('nodeProperties', Ember.Object.create(cleanProperties));
		}.observes('T3.Content.Model.NodeSelection.selectedNode'),

		/**
		 * We'd like to monitor *every* property change except inline editable ones,
		 * that's why we have to look through the list of properties...
		 */
		onNodePropertiesChange: function() {
			var that = this,
				selectedNode = this.get('selectedNode'),
				selectedNodeSchema,
				editableProperties = [],
				nodeProperties;
			if (selectedNode) {
				selectedNodeSchema = selectedNode.get('nodeTypeSchema');
				nodeProperties = this.get('nodeProperties');
				if (selectedNodeSchema.properties) {
					$.each(selectedNodeSchema.properties, function(propertyName, propertyConfiguration) {
						if (!propertyConfiguration.ui || propertyConfiguration.ui.inlineEditable !== true) {
							editableProperties.push(propertyName);
						}
					});
				}
				if (editableProperties.length > 0) {
					$.each(editableProperties, function(key, propertyName) {
						nodeProperties.addObserver(propertyName, null, function() {
							that._somePropertyChanged();
						});
					});
				}
			}
		}.observes('nodeProperties'),

			// Some hack which is fired when we change a property. Should be replaced with a proper API method which should be fired *every time* a property is changed.
		_somePropertyChanged: function() {
			var that = this,
				hasChanges = false;

			_.each(this.get('cleanProperties'), function(cleanPropertyValue, key) {
				if (that.get('nodeProperties').get(key) !== cleanPropertyValue) {
					hasChanges = true;
				}
			});
			this.set('_modified', hasChanges);
		},

		/**
		 * When the edit button is toggled, we apply the modified properties back
		 */
		onApplyButtonToggle: function(isModified) {
			if (isModified) {
				this.apply();
			}
		},

		/**
		 * Apply the edited properties back to the node proxy
		 */
		apply: function() {
			var that = this,
				cleanProperties,
				nodeTypeSchema = T3.Content.Model.NodeSelection.get('selectedNodeSchema'),
				reloadPage = false;

			_.each(this.get('cleanProperties'), function(cleanPropertyValue, key) {
				if (that.get('nodeProperties').get(key) !== cleanPropertyValue) {
					that.get('selectedNode').setAttribute(key, that.get('nodeProperties').get(key));
					if (Ember.get(nodeTypeSchema, 'properties.' + key + '.ui.reloadIfChanged')) {
						reloadPage = true;
					}
				}
			});

			if (reloadPage === true) {
				ContentModule.showPageLoader();
			}
			Backbone.sync('update', this.get('selectedNode._vieEntity'), {
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

			this.set('_modified', false);

			cleanProperties = this.get('selectedNode.attributes');
			this.set('cleanProperties', cleanProperties);
			this.set('nodeProperties', Ember.Object.create(cleanProperties));
		},

		/**
		 * Revert all changed properties
		 */
		revert: function() {
			this.set('nodeProperties', Ember.Object.create(this.get('cleanProperties')));
			this.set('_modified', false);
		}
	}).create();

	/**
	 * The BlockActions is a container for numerous actions which can happen with blocks.
	 * They are normally triggered when clicking Block UI handles.
	 * Examples include:
	 * - deletion of content
	 * - creation of content
	 *
	 * @singleton
	 */
	var NodeActions = Ember.Object.extend({
			// TODO: Move this to a separate controller
		_clipboard: null,
		_elementIsAddingNewContent: null,

		clipboardContainsContent: function() {
			return this.get('_clipboard') !== null;
		}.property('_clipboard'),

		/**
		 * Initialization lifecycle method. Here, we re-fill the clipboard as needed
		 */
		init: function() {
			if (T3.Common.LocalStorage.getItem('clipboard')) {
				this.set('_clipboard', T3.Common.LocalStorage.getItem('clipboard'));
			}
		},

		/**
		 * Cut a node and put it on the clipboard
		 * TODO: Decide if we move cut copy paste to another controller
		 * @return {void}
		 */
		cut: function(nodePath) {
			if (this.get('_clipboard.type') === 'cut' && this.get('_clipboard.nodePath') === nodePath) {
				this.set('_clipboard', null);
			} else {
				this.set('_clipboard', {
					type: 'cut',
					nodePath: nodePath
				});
			}
		},

		/**
		 * Copy a node and put it on the clipboard
		 * @return {void}
		 */
		copy: function(nodePath) {
			if (this.get('_clipboard.type') === 'copy' && this.get('_clipboard.nodePath') === nodePath) {
				this.set('_clipboard', null);
			} else {
				this.set('_clipboard', {
					type: 'copy',
					nodePath: nodePath
				});
			}
		},

		/**
		 * Paste the current node on the clipboard after another node
		 * @param {String} nodePath the nodePath of the target node
		 * @return {boolean}
		 */
		pasteAfter: function(nodePath) {
			return this._paste(nodePath, 'after');
		},

		/**
		 * Paste a node on a certain location, relative to another node
		 * @param {String} nodePath the nodePath of the target node
		 * @param {String} position
		 * @return {boolean}
		 */
		_paste: function(nodePath, position) {
			var that = this,
				clipboard = this.get('_clipboard');

			if (!clipboard.nodePath) {
				T3.Common.Notification.notice('No node found on the clipboard');
				return false;
			}
			if (clipboard.nodePath === nodePath && clipboard.type === 'cut') {
				T3.Common.Notification.notice('It is not possible to paste a node ' + position + ' itself.');
				return false;
			}

			var action = clipboard.type === 'cut' ? 'move' : 'copy';
			TYPO3_Neos_Service_ExtDirect_V1_Controller_NodeController[action].call(
				that,
				clipboard.nodePath,
				nodePath,
				position,
				function (result) {
					if (result.success) {
						that.set('_clipboard', null);
						ContentModule.reloadPage();
					}
				}
			);
			return true;
		},

		remove: function(model) {
			model.set('typo3:_removed', true);
			model.save(null);
			T3.Content.Model.NodeSelection.updateSelection();
		},

		addAbove: function(nodeType, referenceEntity, callBack) {
			this._add(nodeType, referenceEntity, 'before', callBack);
		},

		addBelow: function(nodeType, referenceEntity, callBack) {
			this._add(nodeType, referenceEntity, 'after', callBack);
		},

		addInside: function(nodeType, referenceEntity, callBack) {
			this._add(nodeType, referenceEntity, 'into', callBack);
		},

		/**
		 * Creates a node on the server. When the result is received the callback function is called.
		 * The first argument passed to the callback is the nodepath of the new node, second argument
		 * is the $ object containing the rendered HTML of the new node.
		 *
		 * @param {String} nodeType
		 * @param {Object} referenceEntity
		 * @param {String} position
		 * @param {Function} callBack This function is called after element creation and receives the $ DOM element as arguments
		 * @private
		 */
		_add: function(nodeType, referenceEntity, position, callBack) {
			var that = this;
			TYPO3_Neos_Service_ExtDirect_V1_Controller_NodeController.createAndRender(
				referenceEntity.getSubject().substring(1, referenceEntity.getSubject().length - 1),
				referenceEntity.get('typo3:_typoscriptPath'),
				{
					nodeType: nodeType,
					properties: {}
				},
				position,
				function(result) {
					var template = $(result.collectionContent).find('[about="' + result.nodePath + '"]').first();
					callBack(result.nodePath, template);

					// Remove the loading icon from the parent content element where current element was created from.
					that.set('_elementIsAddingNewContent', null);
				}
			);
		},

		/**
		 * Paste the current node on the clipboard before another node
		 *
		 * @param {String} nodePath the nodePath of the target node
		 * @param {$} $handle the clicked handle
		 * @return {boolean}
		 */
		pasteBefore: function(nodePath, $handle) {
			return this._paste(nodePath, $handle, 'before');
		},

		/**
		 * Paste the current node on the clipboard after another node
		 *
		 * @param {String} nodePath the nodePath of the target node
		 * @param {$} $handle the clicked handle
		 * @return {void}
		 */
		removeFromClipboard: function(nodePath, $handle) {
			var block = T3.Content.Model.BlockManager.getBlockByNodePath(nodePath),
				clipboard = this.get('_clipboard');

			if (clipboard.nodePath === nodePath) {
				this.set('_clipboard', null);
			}

			block.hideHandle('remove-from-cut');
			block.hideHandle('remove-from-copy');
			$('.neos-paste-before-handle, .neos-paste-after-handle').addClass('neos-handle-hidden');
			$('.neos-add-above-handle, .neos-add-below-handle').removeClass('neos-handle-hidden');
			block.showHandle('cut');
			block.showHandle('copy');
		},

		/**
		 * Observes the _clipboard property and processes changes
		 * @return {void}
		 */
		onClipboardChange: function() {
			var clipboard = this.get('_clipboard');
			T3.Common.LocalStorage.setItem('clipboard', clipboard);
		}.observes('_clipboard')
	}).create();

	var ServerConnection = Ember.Object.extend({
		_lastSuccessfulTransfer: null,
		_failedRequest: false,
		_pendingSave: false,
		_saveRunning: false,

		sendAllToServer: function(collection, transformFn, extDirectFn, callback, elementCallback) {
			var that = this,
				numberOfUnsavedRecords = collection.get('length'),
				responseCallback = function(element) {
					return function(provider, response) {
						if (!response.result || response.result.success !== true) {
								// TODO: Find a way to avoid this notice
							T3.Common.Notification.error('Server communication error, reload the page to return to a safe state if another publish does not work');
							that.set('_failedRequest', true);
							return;
						} else {
							that.set('_failedRequest', false);
							that.set('_lastSuccessfulTransfer', new Date());
						}

						if (elementCallback) {
							elementCallback(element, response);
						}
						numberOfUnsavedRecords--;
						if (numberOfUnsavedRecords <= 0) {
							that.set('_saveRunning', false);
							if (callback) {
								callback();
							}
						}
					};
				};
			collection.forEach(function(element) {
					// Force copy of array
				var args = transformFn(element).slice();
				args.push(responseCallback(element));
				that.set('_saveRunning', true);
				extDirectFn.apply(window, args);
			})
		},

		statusClass: function() {
			this.set('_saveRunning', false);
			return 'neos-connection-status-' + this.get('_failedRequest') ? 'down' : 'up';
		}.observes('_failedRequest')

	}).create();

	T3.Content.Controller = {
		Preview: Preview,
		PageTree: PageTree,
		Wireframe: Wireframe,
		Inspect: Inspect,
		NodeActions: NodeActions,
		Inspector: Inspector,
		ServerConnection: ServerConnection
	}
	window.T3 = T3;
});
