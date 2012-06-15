/**
 * Controllers which are not model- but appearance-related
 */

define(
['jquery', 'phoenix/common'],
function(jQuery) {

	var T3 = window.T3 || {};
	T3.Content = T3.Content || {};
	var $ = jQuery;

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

			$('body').toggleClass('t3-ui-previewmode t3-ui-controls');

			if (isPreviewEnabled) {
				Aloha.editables.forEach(function(editable) {
					editable.disable();
				});
			} else {
				Aloha.editables.forEach(function(editable) {
					editable.enable();
				});
			}
		}.observes('previewMode'),

		onPreviewModeChange: function() {
			T3.Common.LocalStorage.setItem('previewMode', this.get('previewMode'));
		}.observes('previewMode')
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

		blockProperties: null,

		selectedBlock: null,
		cleanProperties: null,

		init: function() {
			this.set('blockProperties', Ember.Object.create());
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
			var selectedBlockSchema = T3.Content.Model.BlockSelection.get('selectedBlockSchema');
			if (!selectedBlockSchema || !selectedBlockSchema.groups || !selectedBlockSchema.properties) return [];

			var sectionsAndViews = [];
			jQuery.each(selectedBlockSchema.groups, function(groupIdentifier, propertyGroupConfiguration) {
				var properties = [];
				jQuery.each(selectedBlockSchema.properties, function(propertyName, propertyConfiguration) {
					if (propertyConfiguration.group === groupIdentifier) {
						properties.push(jQuery.extend({key: propertyName}, propertyConfiguration));
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
		}.property('T3.Content.Model.BlockSelection.selectedBlockSchema').cacheable(),

		/**
		 * When the selected block changes in the content model,
		 * we update this.blockProperties
		 */
		onSelectedBlockChange: function() {
			this.selectedBlock = T3.Content.Model.BlockSelection.get('selectedBlock');
			this.cleanProperties = this.selectedBlock.getCleanedUpAttributes();
			this.set('blockProperties', Ember.Object.create(this.cleanProperties));
		}.observes('T3.Content.Model.BlockSelection.selectedBlock'),


		/**
		 * We'd like to monitor *every* property change, that's why we have
		 * to look through the list of properties...
		 */
		onBlockPropertiesChange: function() {
			var that = this,
				selectedBlock = this.get('selectedBlock');
			if (selectedBlock) {
				var selectedBlockSchema = T3.Content.Model.BlockSelection.get('selectedBlockSchema'),
					editableProperties = [],
					blockProperties = this.get('blockProperties');
				if (selectedBlockSchema.properties) {
					jQuery.each(selectedBlockSchema.properties, function(propertyName, propertyConfiguration) {
						if (selectedBlockSchema.inlineEditableProperties) {
							if (jQuery.inArray(propertyName, selectedBlockSchema.inlineEditableProperties) === -1) {
								editableProperties.push(propertyName);
							}
						} else {
							editableProperties.push(propertyName);
						}
					});
				}
				if (editableProperties.length > 0) {
					jQuery.each(editableProperties, function(key, propertyName) {
						blockProperties.addObserver(propertyName, null, function(property, propertyName, value) {
							that._somePropertyChanged();
						});
					});
				}
			}
		}.observes('blockProperties'),

		// Some hack which is fired when we change a property. Should be replaced with a proper API method which should be fired *every time* a property is changed.
		_somePropertyChanged: function() {
			var that = this,
				hasChanges = false;
			jQuery.each(this.selectedBlock.getCleanedUpAttributes(), function(key, value) {
				if (that.get('blockProperties').get(key) !== value) {
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
		 * Apply the edited properties back to the block
		 */
		apply: function() {
			var that = this;
			Ember.beginPropertyChanges();
			Ember.keys(this.cleanProperties).forEach(function(key) {
				that.selectedBlock.set(key, that.blockProperties.get(key));
			});

			this.set('_modified', false);
			Ember.endPropertyChanges();
		},

		/**
		 * Revert all changed properties
		 */
		revert: function() {
			this.cleanProperties = this.selectedBlock.getCleanedUpAttributes();
			this.set('blockProperties', Ember.Object.create(this.cleanProperties));
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
	var BlockActions = Ember.Object.create({

		// TODO: Move this to a separate controller
		_clipboard: null,

		/**
		 * Initialization lifecycle method. Here, we connect the create-new-content button
		 * which is displayed when a ContentArray is empty.
		 */
		init: function() {
			if (T3.Common.LocalStorage.getItem('clipboard')) {
				this.set('_clipboard', T3.Common.LocalStorage.getItem('clipboard'));
			}
		},
		deleteBlock: function(nodePath, $handle) {
			var that = this;
			T3.Common.Dialog.openConfirmPopover({
				title: 'Are you sure you want to remove this content element?',
				content: 'If you remove this element you can restore it using undo',
				positioning: 'absolute',
				onOk: function() {
					TYPO3_TYPO3_Service_ExtDirect_V1_Controller_NodeController['delete'].call(
						that,
						nodePath,
						function (result) {
							if (result.success) {
								T3.ContentModule.reloadPage();
							}
						}
					);
				}
			}, $handle);
		},

		addAbove: function(nodePath, $handle) {
			this._add(nodePath, 'above', $handle);
		},
		addBelow: function(nodePath, $handle) {
			this._add(nodePath, 'below', $handle);
		},
		addInside: function(nodePath, $handle) {
			this._add(nodePath, 'inside', $handle);
		},
		_add: function(nodePath, position, $handle) {
			if ($handle !== undefined) {
				$handle.addClass('t3-handle-loading');

				$handle.bind('showPopover', function() {
					jQuery('.contentTypeSelectorTabs.notInitialized').each(function(index) {
						var newDate = new Date();
						var uniqueId = 't3-content-tabs-' + Math.random() * Math.pow(10, 17) + '-' + newDate.getTime();
						jQuery(this).attr('id', uniqueId);

						jQuery(this).children('ul').find('li a').each(function (index) {
							jQuery(this).attr('href', '#' + uniqueId + '-' + index.toString());
						});

						jQuery(this).children('div').each(function (index) {
							jQuery(this).attr('id', uniqueId + '-' + index.toString());
						})
						jQuery(this).tabs();
						jQuery(this).removeClass('notInitialized');
					});
					jQuery('.t3-handle-loading').removeClass('t3-handle-loading');
				});
			}

			T3.Common.Dialog.openFromUrl(
				'/typo3/content/new',
				{
					position: position,
					referenceNode: nodePath
				},
				{
					'created-new-content': function($callbackDomElement) {
						T3.ContentModule.reloadPage();
					}
				},
				$handle,
				{
					positioning: 'absolute'
				}
			);

		},

		/**
		 * Cut a node and put it on the clipboard
		 * TODO: Decide if we move cut copy paste to another controller
		 * @return {void}
		 */
		cut: function(nodePath, $handle) {
			var block = T3.Content.Model.BlockManager.getBlockByNodePath(nodePath);
			block.hideHandle('remove-from-copy');
			block.showHandle('copy');
			this.set('_clipboard', {
				type: 'cut',
				nodePath: nodePath
			});
		},

		/**
		 * Copy a node and put it on the clipboard
		 * @return {void}
		 */
		copy: function(nodePath, $handle) {
			var block = T3.Content.Model.BlockManager.getBlockByNodePath(nodePath);
			block.hideHandle('remove-from-cut');
			block.showHandle('cut');
			this.set('_clipboard', {
				type: 'copy',
				nodePath: nodePath
			});
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
		pasteAfter: function(nodePath, $handle) {
			this._paste(nodePath, $handle, 'after');
		},

		/**
		 * Paste a node on a certain location, relative to another node
		 * @param {String} nodePath the nodePath of the target node
		 * @param {jQuery} handle the clicked handle
		 * @param {String} position
		 * @return {void}
		 */
		_paste: function(nodePath, $handle, position) {
			var that = this,
				clipboard = this.get('_clipboard');

			if (!clipboard.nodePath) {
				T3.Common.Notification.notice('No node found on the clipboard');
				return;
			}
			if (clipboard.nodePath === nodePath) {
				T3.Common.Notification.notice('It is not possible to paste a node "' + position + '" at itself');
				return;
			}

			var action = (position == 'before') ? 'moveBefore' : 'moveAfter';
			TYPO3_TYPO3_Service_ExtDirect_V1_Controller_NodeController[action].call(
				that,
				clipboard.nodePath,
				nodePath,
				function (result) {
					if (result.success) {
						T3.Common.LocalStorage.removeItem('clipboard');
						T3.ContentModule.reloadPage();
					}
				}
			);
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
			try {
				var clipboard = this.get('_clipboard');
				T3.Common.LocalStorage.setItem('_clipboard', clipboard);
				var block = T3.Content.Model.BlockManager.getBlockByNodePath(clipboard.nodePath);

				if (clipboard.type === 'cut') {
					// TODO: Make a sproutcore binding to andle this
					jQuery('.t3-contentelement-cut').each(function() {
						jQuery(this).removeClass('t3-contentelement-cut');
						jQuery(this).parent().find('.t3-cut-handle').removeClass('t3-handle-hidden');
					});

					// Handle cut
					block.getContentElement().addClass('t3-contentelement-cut');
					block.hideHandle('cut');
					block.showHandle('remove-from-cut');
				} else if (clipboard.type === 'copy') {
					// Handle copy
					block.hideHandle('copy');
					block.showHandle('remove-from-copy');
				}
				jQuery('.t3-paste-before-handle, .t3-paste-after-handle').removeClass('t3-handle-hidden');
				jQuery('.t3-add-above-handle, .t3-add-below-handle').addClass('t3-handle-hidden');
			} catch (error) {
				// TODO: HACK! Somehow this is a DOMWindow on first load of the page
				setTimeout(this.onClipboardChange, 500);
			}
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
						if (response.status === false) {
							that.set('_failedRequest', true);
							that.set('_saveRunning', false);
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
			var className = 't3-connection-status-';
			className += this.get('_failedRequest') ? 'down' : 'up';
		}.observes('_failedRequest')

	});

	T3.Content.Controller = {
		Preview: Preview,
		Inspect: Inspect,
		BlockActions: BlockActions,
		Inspector: Inspector,
		ServerConnection: ServerConnection
	}
	window.T3 = T3;
});
