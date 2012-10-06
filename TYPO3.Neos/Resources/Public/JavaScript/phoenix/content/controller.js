/**
 * Controllers which are not model- but appearance-related
 */

define(
['jquery', 'create', 'vie/entity', 'phoenix/common', 'phoenix/content/model'],
function($, CreateJS, Entity) {
	if (window._requirejsLoadingTrace) window._requirejsLoadingTrace.push('phoenix/content/controller');

	var T3 = window.T3 || {};
	T3.Content = T3.Content || {};

	/**
	 * This controller toggles the preview mode on and off.
	 */
	var Preview = Ember.Object.create({
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
			var isPreviewEnabled = this.get('previewMode');
			if (isPreviewEnabled) {
				CreateJS.disableEdit();
			} else {
				CreateJS.enableEdit();
			}
			$('body').toggleClass('t3-ui-previewmode t3-ui-controls');
		}.observes('previewMode'),

		onPreviewModeChange: function() {
			T3.Common.LocalStorage.setItem('previewMode', this.get('previewMode'));
		}.observes('previewMode')
	});

	/**
	 * This controller toggles the wireframe mode on and off.
	 */
	var Wireframe = Ember.Object.create({
		wireframeMode: false,

		init: function() {
			if (T3.Common.LocalStorage.getItem('wireframeMode') === true) {
				this.toggleWireframeMode();
				$('#t3-ui-createsection-input').keypress(function(e) {
					if ((e.keyCode || e.which) === 13) {
						$('#t3-ui-createsection-button').click();
					}
				});
				$('#t3-ui-createsection-button').click(function() {
					var newSectionName = $('#t3-ui-createsection-input').val();
					if (newSectionName === '') {
						T3.Common.Notification.error('You need to give a name for the new content section.');
					} else {
						T3.Content.Controller.Wireframe.createSection(newSectionName);
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
			if (typeof TYPO3_TYPO3_Service_ExtDirect_V1_Controller_UserController === 'object') {
				T3.ContentModule.showPageLoader();
				TYPO3_TYPO3_Service_ExtDirect_V1_Controller_UserController.updatePreferences({'contentEditing.wireframeMode': wireframeMode}, function() {
					T3.Common.LocalStorage.setItem('wireframeMode', wireframeMode);
					window.location.reload(false);
				});
			}
		}.observes('wireframeMode'),

		createSection: function(sectionName) {
			var pageNodePath = $('#t3-page-metainformation').attr('about');
			T3.ContentModule.showPageLoader();
			TYPO3_TYPO3_Service_ExtDirect_V1_Controller_NodeController.create(pageNodePath, {
				contentType: 'TYPO3.Phoenix.ContentTypes:Section',
				nodeName: sectionName
			}, 'into',
			function (result) {
				if (result.success == true) {
					$('#t3-ui-createsection-input').val('');
					T3.ContentModule.reloadPage();
				}
			});
		}
	});

	/**
	 * This controller toggles the inspection mode on and off.
	 *
	 * @TODO: rename differently, because it is too similar with "Inspector"
	 * @TODO: Toggling inspectMode does not show popover
	 */
	var Inspect = Ember.Object.create({
		inspectMode: false,

		onInspectModeChange: function() {
			var isInspectEnabled = this.get('inspectMode');
			if (isInspectEnabled) {
				jQuery('body').addClass('t3-inspect-active');
			} else {
				jQuery('body').removeClass('t3-inspect-active');
			}
		}.observes('inspectMode')
	});

	/**
	 * Controller for the inspector
	 */
	var Inspector = Ember.Object.create({
		_modified: false,
		_unmodified: function() {
			return !this.get('_modified');
		}.property('_modified').cacheable(),

		nodeProperties: null,

		selectedNode: null,
		cleanProperties: null,

		init: function() {
			this.set('nodeProperties', Ember.Object.create());
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
		sectionsAndViews: function() {
			var selectedNodeSchema = T3.Content.Model.NodeSelection.get('selectedNodeSchema');
			if (!selectedNodeSchema || !selectedNodeSchema.groups || !selectedNodeSchema.properties) return [];

			var sectionsAndViews = [];
			jQuery.each(selectedNodeSchema.groups, function(groupIdentifier, propertyGroupConfiguration) {
				var properties = [];
				jQuery.each(selectedNodeSchema.properties, function(propertyName, propertyConfiguration) {
					if (propertyConfiguration.group === groupIdentifier) {
						properties.push(jQuery.extend({key: propertyName, elementId: Ember.generateGuid()}, propertyConfiguration));
					}
				});

				properties.sort(function(a, b) {
					return (b.priority || 0) - (a.priority || 0);
				});

				sectionsAndViews.push(jQuery.extend({}, propertyGroupConfiguration, {
					properties: properties
				}));
			});
			sectionsAndViews.sort(function(a, b) {
				return (b.priority || 0) - (a.priority || 0);
			});

			return sectionsAndViews;
		}.property('T3.Content.Model.NodeSelection.selectedNodeSchema').cacheable(),

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
		 * We'd like to monitor *every* property change, that's why we have
		 * to look through the list of properties...
		 */
		onNodePropertiesChange: function() {
			var that = this,
				selectedNode = this.get('selectedNode'),
				selectedNodeSchema,
				editableProperties = [],
				nodeProperties;
			if (selectedNode) {
				selectedNodeSchema = selectedNode.get('contentTypeSchema');
				nodeProperties = this.get('nodeProperties');
				if (selectedNodeSchema.properties) {
					jQuery.each(selectedNodeSchema.properties, function(propertyName, propertyConfiguration) {
						if (selectedNodeSchema.inlineEditableProperties) {
							if (jQuery.inArray(propertyName, selectedNodeSchema.inlineEditableProperties) === -1) {
								editableProperties.push(propertyName);
							}
						} else {
							editableProperties.push(propertyName);
						}
					});
				}
				if (editableProperties.length > 0) {
					jQuery.each(editableProperties, function(key, propertyName) {
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
				contentTypeSchema = T3.Configuration.Schema[Entity.extractContentTypeFromVieEntity(this.getPath('selectedNode._vieEntity'))],
				reloadPage = false;

			_.each(this.get('cleanProperties'), function(cleanPropertyValue, key) {
				if (that.get('nodeProperties').get(key) !== cleanPropertyValue) {
					that.get('selectedNode').setAttribute(key, that.get('nodeProperties').get(key));
					if (contentTypeSchema && contentTypeSchema.properties && contentTypeSchema.properties[key] && contentTypeSchema.properties[key]['reloadOnChange']) {
						reloadPage = true;
					}
				}
			});

			if (reloadPage) {
				T3.ContentModule.showPageLoader();
			}
			Backbone.sync('update', this.getPath('selectedNode._vieEntity'), {success: function(model, result) {
				if (reloadPage) {
					if (result && result.data && result.data.nextUri) {
							// It might happen that the page has been renamed, so we need to take the server-side URI
						T3.ContentModule.loadPage(result.data.nextUri);
					} else {
						T3.ContentModule.reloadPage();
					}
				}
			}});

			this.set('_modified', false);

			cleanProperties = this.getPath('selectedNode.attributes');
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
	});

	/**
	 * The BlockActions is a container for numerous actions which can happen with blocks.
	 * They are normally triggered when clicking Block UI handles.
	 * Examples include:
	 * - deletion of content
	 * - creation of content
	 *
	 * @singleton
	 */
	var NodeActions = Ember.Object.create({

			// TODO: Move this to a separate controller
		_clipboard: null,

		clipboardContainsContent: function() {
			return this.get('_clipboard') !== null;
		}.property('_clipboard').cacheable(),

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
			if (this.getPath('_clipboard.type') === 'cut' && this.getPath('_clipboard.nodePath') === nodePath) {
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
			if (this.getPath('_clipboard.type') === 'copy' && this.getPath('_clipboard.nodePath') === nodePath) {
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
		 * @return {void}
		 */
		pasteAfter: function(nodePath) {
			this._paste(nodePath, 'after');
		},

		/**
		 * Paste a node on a certain location, relative to another node
		 * @param {String} nodePath the nodePath of the target node
		 * @param {jQuery} handle the clicked handle
		 * @param {String} position
		 * @return {void}
		 */
		_paste: function(nodePath, position) {
			var that = this,
				clipboard = this.get('_clipboard');

			if (!clipboard.nodePath) {
				T3.Common.Notification.notice('No node found on the clipboard');
				return;
			}
			if (clipboard.nodePath === nodePath && clipboard.type === 'cut') {
				T3.Common.Notification.notice('It is not possible to paste a node ' + position + ' itself.');
				return;
			}

			var action = clipboard.type === 'cut' ? 'move' : 'copy';
			TYPO3_TYPO3_Service_ExtDirect_V1_Controller_NodeController[action].call(
				that,
				clipboard.nodePath,
				nodePath,
				position,
				function (result) {
					if (result.success) {
						T3.Common.LocalStorage.removeItem('clipboard');
						that.set('_clipboard', null);
						T3.ContentModule.reloadPage();
					}
				}
			);
		},

		remove: function(model, $element, $handle) {
			T3.Common.Dialog.openConfirmPopover({
				title: 'Delete this element?',
				confirmLabel: 'Delete',
				confirmClass: 'btn-danger',
				positioning: 'absolute',
				onOk: function() {
					$element.fadeOut(function() {
						$element.addClass('t3-contentelement-removed');
					});
					model.set('typo3:_removed', true);
					model.save(null);
				}
			}, $handle);
		},

		addAbove: function(contentType, referenceEntity, callBack) {
			this._add(contentType, referenceEntity, 'before', callBack);
		},

		addBelow: function(contentType, referenceEntity, callBack) {
			this._add(contentType, referenceEntity, 'after', callBack);
		},

		addInside: function(contentType, referenceEntity, callBack) {
			this._add(contentType, referenceEntity, 'into', callBack);
		},

		/**
		 * Creates a node on the server. When the result is received the callback function is called.
		 * The first argument passed to the callback is the nodepath of the new node, second argument
		 * is the jQuery object containing the rendered HTML of the new node.
		 *
		 * @param {String} contentType
		 * @param {Object} referenceEntity
		 * @param {String} position
		 * @param {Function} callBack This function is called after element creation and receives the jQuery DOM element as arguments
		 * @private
		 */
		_add: function(contentType, referenceEntity, position, callBack) {
			TYPO3_TYPO3_Service_ExtDirect_V1_Controller_NodeController.createAndRender(
				referenceEntity.getSubject().substring(1, referenceEntity.getSubject().length - 1),
				referenceEntity.get('typo3:_typoscriptPath'),
				{
					contentType: contentType,
					properties: {}
				},
				position,
				function(result) {
					var template = $(result.collectionContent).find('[about="' + result.nodePath + '"]').first();
					callBack(result.nodePath, template);
				}
			);
		},

		/**
		 * Paste the current node on the clipboard before another node
		 * @param {String} nodePath the nodePath of the target node
		 * @param {jQuery} handle the clicked handle
 		 * @return {void}
		 */
		pasteBefore: function(nodePath, $handle) {
			this._paste(nodePath, $handle, 'before');
		},

		/**
		 * Paste the current node on the clipboard after another node
		 * @param {String} nodePath the nodePath of the target node
		 * @param {jQuery} handle the clicked handle
		 * @return {void}
		 */
		removeFromClipboard: function(nodePath, $handle) {
			var block = T3.Content.Model.BlockManager.getBlockByNodePath(nodePath),
				clipboard = this.get('_clipboard');

			if (clipboard.nodePath === nodePath) {
				this.set('_clipboard', {});
			}

			block.hideHandle('remove-from-cut');
			block.hideHandle('remove-from-copy');
			jQuery('.t3-paste-before-handle, .t3-paste-after-handle').addClass('t3-handle-hidden');
			jQuery('.t3-add-above-handle, .t3-add-below-handle').removeClass('t3-handle-hidden');
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
	});

	var ServerConnection = Ember.Object.create({
		_lastSuccessfulTransfer: null,
		_failedRequest: false,
		_pendingSave: false,
		_saveRunning: false,

		sendAllToServer: function(collection, transformFn, extDirectFn, callback, elementCallback) {
			var that = this,
				numberOfUnsavedRecords = collection.get('length'),
				responseCallback = function(element) {
					return function(provider, response) {
						if (response.result === null || response.result.success !== true) {
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
							callback();
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
			return 't3-connection-status-' + this.get('_failedRequest') ? 'down' : 'up';
		}.observes('_failedRequest')

	});

	T3.Content.Controller = {
		Preview: Preview,
		Wireframe: Wireframe,
		Inspect: Inspect,
		NodeActions: NodeActions,
		Inspector: Inspector,
		ServerConnection: ServerConnection
	}
	window.T3 = T3;
});
