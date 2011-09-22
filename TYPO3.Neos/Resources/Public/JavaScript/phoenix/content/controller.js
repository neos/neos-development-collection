/**
 * Controllers which are not model- but appearance-related
 */

define(
[],
function() {

	var T3 = window.T3 || {};
	T3.Content = T3.Content || {};
	var $ = window.alohaQuery || window.jQuery;

	/**
	 * This controller toggles the preview mode on and off.
	 */
	var Preview = SC.Object.create({
		previewMode: false,

		init: function() {
			if (window.localStorage.previewMode == 'true') {
				$('body').removeClass('t3-ui-controls-active');
				$('body').addClass('t3-ui-controls-inactive');
				$('body').addClass('typo3-previewmode-enabled');
				this.set('previewMode', true);
			}
		},

		togglePreview: function() {
			var isPreviewEnabled = this.get('previewMode');
			var i = 0, count = 5, allDone = function() {
				i++;
				if (i >= count) {
					if (isPreviewEnabled) {
						$('body').removeClass('t3-ui-controls-active');
						$('body').addClass('t3-ui-controls-inactive');
						Aloha.editables.forEach(function(editable) {
							editable.disable();
						});
					} else {
						$('body').addClass('t3-ui-controls-active');
						$('body').removeClass('t3-ui-controls-inactive');

						Aloha.editables.forEach(function(editable) {
							editable.enable();
						});
					}
				}
			};
			if (isPreviewEnabled) {
				$('body').animate({
					'margin-top': 30,
					'margin-right': 0
				}, 'fast', allDone);
				$('#t3-footer').animate({
					height: 0
				}, 'fast', allDone);
				$('#t3-toolbar').animate({
					top: 0,
					right: 0
				}, 'fast', allDone);
				$('#t3-ui-top').slideUp('fast', allDone);
				$('#t3-inspector').animate({
					width: 0
				}, 'fast', allDone);
				$('body').animate({
					'margin-right': 0
				}, 'fast', allDone);
			} else {
				// TODO Cleanup the 'hidden' workaround for previewMode with a CSS transition
				$('#t3-footer, #t3-ui-top, #t3-inspector').css('display', 'block');

				// TODO Store initial sizes and reuse, to remove concrete values
				$('body').animate({
					'margin-top': 55,
					'margin-right': 200
				}, 'fast', allDone);
				$('#t3-footer').animate({
					height: 30
				}, 'fast', allDone);
				$('#t3-toolbar').animate({
					top: 50,
					right: 200
				}, 'fast', allDone);
				$('#t3-ui-top').slideDown('fast', allDone);
				$('#t3-inspector').animate({
					width: 200
				}, 'fast', allDone);
				$('body').animate({
					'margin-right': 200
				}, 'fast', allDone);
			}
		}.observes('previewMode'),

		onPreviewModeChange: function() {
			window.localStorage.previewMode = this.get('previewMode') ? 'true' : 'false';
		}.observes('previewMode')
	});

	/**
	 * This controller toggles the inspection mode on and off.
	 *
	 * @TODO: rename differently, because it is too similar with "Inspector"
	 */
	var Inspect = SC.Object.create({
		inspectMode: false,

		onInspectModeChange: function() {
			var isInspectEnabled = this.get('inspectMode');
			if (isInspectEnabled) {
				$('body').addClass('t3-inspect-active');
			} else {
				$('body').removeClass('t3-inspect-active');
			}
		}.observes('inspectMode')
	});

	/**
	 * Controller for the inspector
	 */
	var Inspector = SC.Object.create({
		_modified: false,
		_unmodified: function() {
			return !this.get('_modified');
		}.property('_modified').cacheable(),

		blockProperties: null,

		selectedBlock: null,
		cleanProperties: null,

		init: function() {
			this.set('blockProperties', SC.Object.create());
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
			if (!selectedBlockSchema || !selectedBlockSchema.propertyGroups || !selectedBlockSchema.properties) return [];

			var sectionsAndViews = [];
			$.each(selectedBlockSchema.propertyGroups, function(propertyGroupIdentifier, propertyGroupConfiguration) {
				var properties = [];
				$.each(selectedBlockSchema.properties, function(propertyName, propertyConfiguration) {
					if (propertyConfiguration.category === propertyGroupIdentifier) {
						properties.push($.extend({key: propertyName}, propertyConfiguration));
					}
				});

				properties.sort(function(a, b) {
					return (b.priority || 0) - (a.priority || 0);
				});

				sectionsAndViews.push($.extend({}, propertyGroupConfiguration, {
					properties: properties
				}));
			});
			sectionsAndViews.sort(function(a, b) {
				return (b.priority || 0) - (a.priority || 0);
			})

			return sectionsAndViews;
		}.property('T3.Content.Model.BlockSelection.selectedBlockSchema').cacheable(),

		/**
		 * When the selected block changes in the content model,
		 * we update this.blockProperties
		 */
		onSelectedBlockChange: function() {
			this.selectedBlock = T3.Content.Model.BlockSelection.get('selectedBlock');
			this.cleanProperties = this.selectedBlock.getCleanedUpAttributes();
			this.set('blockProperties', SC.Object.create(this.cleanProperties));
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
					$.each(selectedBlockSchema.properties, function(propertyName, propertyConfiguration) {
						if (selectedBlockSchema.inlineEditableProperties) {
							if ($.inArray(propertyName, selectedBlockSchema.inlineEditableProperties) === -1) {
								editableProperties.push(propertyName);
							}
						} else {
							editableProperties.push(propertyName);
						}
					});
				}
				if (editableProperties.length > 0) {
					$.each(editableProperties, function(key, propertyName) {
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
			$.each(this.selectedBlock.getCleanedUpAttributes(), function(key, value) {
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
			SC.keys(this.cleanProperties).forEach(function(key) {
				that.selectedBlock.set(key, that.blockProperties.get(key));
			});
			this.set('_modified', false);
		},

		/**
		 * Revert all changed properties
		 */
		revert: function() {
			this.cleanProperties = this.selectedBlock.getCleanedUpAttributes();
			this.set('blockProperties', SC.Object.create(this.cleanProperties));
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
	var BlockActions = SC.Object.create({

		// TODO: Move this to a separete controller
		_clipboard: null,

		/**
		 * Initialization lifecycle method. Here, we connect the create-new-content button
		 * which is displayed when a ContentArray is empty.
		 */
		init: function() {
			if (window.localStorage.clipboard) {
				this.set('_clipboard', JSON.parse(window.localStorage.clipboard));
			}
		},
		deleteBlock: function(nodePath, $handle) {
			var that = this;
			$handle.addClass('t3-handle-loading');

			T3.Common.Dialog.openConfirmPopover({
				title: 'Are you sure you want to remove this content element?',
				content: 'If you remove this element you can restore it using undo',
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
				},
				onDialogOpen: function() {
					$handle.removeClass('t3-handle-loading');
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
					$('.contentTypeSelectorTabs.notInitialized').each(function(index) {
						var newDate = new Date();
						var uniqueId = 't3-content-tabs-' + Math.random() * Math.pow(10, 17) + '-' + newDate.getTime();
						$(this).attr('id', uniqueId);

						$(this).children('ul').find('li a').each(function (index) {
							$(this).attr("href", '#' + uniqueId + '-' + index.toString());
						});

						$(this).children('div').each(function (index) {
							$(this).attr("id", uniqueId + '-' + index.toString());
						})
						$(this).tabs();
						$(this).removeClass('notInitialized');
					});
					$('.t3-handle-loading').removeClass('t3-handle-loading');
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
			var that = this;
			var clipboard = this.get('_clipboard');

			if (!clipboard.nodePath) {
				T3.Common.Notification.notice('No node found on the clipboard');
				return;
			}
			if (clipboard.nodePath === nodePath) {
				T3.Common.Notification.notice("It's not possible to paste a node " + position + " itself");
				return;
			}

			var action = (position == 'before') ? 'moveBefore' : 'moveAfter';
			TYPO3_TYPO3_Service_ExtDirect_V1_Controller_NodeController[action].call(
				that,
				clipboard.nodePath,
				nodePath,
				function (result) {
					if (result.success) {
						delete window.localStorage.clipboard;
						T3.ContentModule.reloadPage();
					}
				}
			);
		},

		/**
		 * Observes the _clipboard property and processes changes
		 * @return {void}
		 */
		onClipboardChange: function() {
			try {
				var clipboard = this.get('_clipboard');
				window.localStorage.clipboard = JSON.stringify(clipboard);
				var block = T3.Content.Model.BlockManager.getBlockByNodePath(clipboard.nodePath);

				if (clipboard.type === 'cut') {
					// TODO: Make a sproutcore binding to andle this
					$('.t3-contentelement-cut').each(function() {
						$(this).removeClass('t3-contentelement-cut');
						$(this).parent().find('.t3-cut-handle').removeClass('t3-handle-hidden');
					});

					// Handle cut
					block.getContentElement().addClass('t3-contentelement-cut');
					block.hideHandle('cut');
				} else if (clipboard.type === 'copy') {
					// Handle copy
					block.hideHandle('copy');
				}

				$('.t3-paste-before-handle, .t3-paste-after-handle').removeClass('t3-handle-hidden');
			} catch (error) {
				// TODO: HACK! Somehow this is a DOMWindow on first load of the page
				setTimeout(this.onClipboardChange, 500);
			}
		}.observes('_clipboard')
	});

	T3.Content.Controller = {
		Preview: Preview,
		Inspect: Inspect,
		BlockActions: BlockActions,
		Inspector: Inspector
	}
	window.T3 = T3;
});
