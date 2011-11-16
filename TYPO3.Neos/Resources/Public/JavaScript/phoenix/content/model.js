/**
 * T3.Content.Model
 *
 * Contains the main models underlying the content module UI.
 *
 * The most central model is the "Block" model, which shadows the Aloha Block model.
 */

define(
['text!phoenix/templates/common/launcher.html'],
function(launcherTemplate) {

	var T3 = window.T3 || {};
	T3.Content = T3.Content || {};
	T3.Content.Model = {};
	var $ = window.alohaQuery || window.jQuery;

	/**
	 * T3.Content.Model.AbstractBlock
	 */
	var AbstractBlock = SC.Object.extend({
		_hidden: false,
		__title: null,
		__originalValues: null,
		__modified: false,
		__publishable: false,
		__status: '',
		__titleAndModifcationState: '',
		__nodePath: null,
		__workspacename: null,
			// If false we don't fire any change notifications
		__valuesInitialized: false,

		/**
		 * @var {boolean}
		 * A node is publishable if it is not in the live workspace.
		 */
		__publishable: function() {
			return (this.get('__workspacename') !== 'live');
		}.property('__workspacename').cacheable(),

		/**
		 * @var {String}
		 * A concatenation of title and status for the breadcrumb menu.
		 * Triggered each time "__status" and "__title" properties change.
		 */
		__titleAndModifcationState: function() {
			return this.get('__status') === '' ? this.get('__title') : this.get('__title') + ' (' + this.get('__status') + ')';
		}.property('__status', '__title').cacheable(),

		_initializePropertiesFromSchema: function(schema) {
			var that = this;
			this.__originalValues = {};
			// HACK: Add observer for each element, as we do not know how to add one observer for *all* elements.
			$.each(schema.properties, function(key) {
				that.__originalValues[key] = null;
				that.addObserver(key, that, that._somePropertyChanged);
			});
		},

		/**
		 * Triggered each time "publishable" and "modified" properties change.
		 *
		 * If something is *publishable* and *modified*, i.e. is already saved
		 * in the current workspace AND has more local changes, the system
		 * will NOT publish the not-visible changes.
		 */
		_onStateChange: function() {
			if (this.get('__modified')) {
				this.set('__status', 'modified');
				Changes.addChange(this);
				PublishableBlocks.remove(this);
			} else if (this.get('__publishable')) {
				this.set('__status', 'publishable');
				Changes.removeChange(this);
				PublishableBlocks.add(this);
			} else {
				this.set('__status', '');
				Changes.removeChange(this);
				PublishableBlocks.remove(this);
			}
		}.observes('__publishable', '__modified'),

		// Some hack which is fired when we change a property. Should be replaced with a proper API method which should be fired *every time* a property is changed.
		_somePropertyChanged: function(that, propertyName) {
			if (!this.__valuesInitialized) {
				return;
			}
			var hasChanges = false;

			// We need to register this block as changed even if it is
			// already inside, so that the local store can be updated
			// with the new content.
			Changes.addChange(this);

			$.each(this.__originalValues, function(key, value) {
				if (that.get(key) !== value) {
					hasChanges = true;
				}
			});
			this.set('__modified', hasChanges);
		},

		/**
		 * Returns a simple JSON object containing the simple and cleaned
		 * attributes for the block
		 */
		getCleanedUpAttributes: function() {
			var that = this, cleanedAttributes = {};
			$.each(this.__originalValues, function(key) {
				cleanedAttributes[key] = that.get(key);
			});
			return cleanedAttributes;
		},

		recordCurrentStateAsOriginal: function() {
			var that = this;
			$.each(this.__originalValues, function(key) {
				that.__originalValues[key] = that.get(key);
			});
			this.__valuesInitialized = true;
		},

		setOriginalValues: function(properties) {
			var that = this;
			$.each(properties, function(key, value) {
				that.__originalValues[key] = value;
			});
			this._checkForDirtyProperties();
		},

		_checkForDirtyProperties: function() {
			var that = this, modified = false;
			$.each(this.__originalValues, function(key, value) {
				if (value !== that.get(key)) {
					modified = true;
				}
			});
			this.set('__modified', modified);
		}
	});

	/**
	 * T3.Content.Model.Block
	 *
	 * The most central model class, which represents a single content element, and wraps and
	 * extends an Aloha Block for use in SproutCore, so we can use data binding on it.
	 *
	 * We use the following conventions:
	 *
	 * (variable without prefix): TYPO3CR Node::setProperty(...)
	 * _[varname]: TYPO3CR Node::set[Varname](...)
	 * __[varname]: Purely internal stuff, like the context node path
	 */
	var Block = AbstractBlock.extend({
		__alohaBlockId: null,

			// As the DOM is all lowercase the data attribute is all lowercase and we need it in camel case,
			// that's why we use the below hack
		__nodepath: null,
		_setProperCamelCaseNodePath: function() {
			this.set('__nodePath', this.get('__nodepath'));
		}.observes('__nodepath'),

		schema: function() {
			var alohaBlock = Aloha.Block.BlockManager.getBlock(this.get('__alohaBlockId'));
			return alohaBlock.getSchema();
		}.property().cacheable(),

		/**
		 * Get the actual content element wrapper as jQuery object
		 * Note that handles are pretended to the block, and the element itself should
		 * be wrapped with a div
		 * @return {jQuery}
		 */
		getContentElement: function() {
			if (this.__alohaBlockId) {
				return $('#' + this.__alohaBlockId + ' > div:last');
			}
		},

		_onStatusChange: function() {
			var alohaBlock = Aloha.Block.BlockManager.getBlock(this.get('__alohaBlockId'));
			alohaBlock.attr('__status', this.get('__status'));
		}.observes('__status'),

		_onHiddenChange: function(that, propertyName) {
			var contentElementContainer = $('#' + that.__alohaBlockId + ' > .t3-contentelement').first();
			if (this.get('_hidden')) {
				contentElementContainer.addClass('t3-contentelement-hidden');
			} else {
				contentElementContainer.removeClass('t3-contentelement-hidden');
			}
		}.observes('_hidden'),

		_updateChangedPropertyInAlohaBlock: function(propertyName) {
			var alohaBlock = Aloha.Block.BlockManager.getBlock(this.get('__alohaBlockId'));
				// Save original property back to Aloha Block
				if (!this._disableAlohaBlockUpdate) {
					alohaBlock.attr(propertyName, this.get(propertyName));
				}
		},

		// Some hack which is fired when we change a property. Should be replaced with a proper API method which should be fired *every time* a property is changed.
		_somePropertyChanged: function(that, propertyName) {
			this._updateChangedPropertyInAlohaBlock(propertyName);
			this._super(that, propertyName);
		},

		/**
		 * Hides a handle of the block if available
		 * @return {void}
		 */
		hideHandle: function(handleIdentifier) {
			if (this.__alohaBlockId) {
				$('#' + this.__alohaBlockId + ' > .t3-' + handleIdentifier + '-handle').addClass('t3-handle-hidden');
			}
		},

		/**
		 * Shows a handle of the block if available
		 * @return {void}
		 */
		showHandle: function(handleIdentifier) {
			if (this.__alohaBlockId) {
				$('#' + this.__alohaBlockId + ' > .t3-' + handleIdentifier + '-handle').removeClass('t3-handle-hidden');
			}
		},

		// HACK for making updates of editables work...
		_doNotUpdateAlohaBlock: function() {
			this._disableAlohaBlockUpdate = true;
		},

		_enableAlohaBlockUpdateAgain: function() {
			this._disableAlohaBlockUpdate = false;
		}
	});

	var PageBlock = AbstractBlock.extend({
		__title: 'Page',

		schema: function() {
			return T3.Configuration.Schema['TYPO3.TYPO3:Page'];
		}.property().cacheable(),

		init: function() {
			var that = this,
				$pageMetainformation = $('#t3-page-metainformation');

			this._initializePropertiesFromSchema(this.get('schema'));
			$.each(this.get('schema').properties, function(key) {
				that.set(key, $pageMetainformation.data(key.toLowerCase()));
			});
			this.set('__nodePath', $pageMetainformation.attr('data-__nodepath'));
			this.set('__workspacename', $pageMetainformation.attr('data-__workspacename'));
			this.recordCurrentStateAsOriginal();
		}
	});

	/**
	 * T3.Content.Model.BlockManager
	 *
	 * Should be used to get an instance of a T3.Content.Model.Block, when
	 * an Aloha Block is given. Is only used internally.
	 *
	 * @internal
	 */
	var BlockManager = SC.Object.create({
		_blocks: {},
		_blocksByNodePath: {},
		_pageBlock: null,

		initialize: function() {
			this._blocks = {};
			this._blocksByNodePath = {};
			this._pageBlock = PageBlock.create();

			var scope = this;
			$('*[data-__nodepath]').each(function() {
				// Just fetch the block a single time so that it is initialized.
				scope.getBlockByNodePath($(this).attr('data-__nodepath'));
			});
		},

		/**
		 * @param block/block Aloha Block instance
		 * @return {T3.Content.Model.Block}
		 */
		getBlockProxy: function(alohaBlock) {
			var blockId = alohaBlock.getId();
			if (this._blocks[blockId]) {
				return this._blocks[blockId];
			}


			var attributes = {};
			var internalAttributes = {};
			$.each(alohaBlock.attr(), function(key, value) {
				// TODO: This simply converts all strings 'false' and 'true' into boolean, see if type checking has to be added
				if (value === 'false') {
					value = false;
				} else if (value === 'true') {
					value = true;
				}

				if (key[0] === '_' && key[1] === '_') {
					internalAttributes[key] = value;
				} else {
					attributes[key] = value;
				}
			});

			var blockProxy = Block.create($.extend({}, attributes, {
				__alohaBlockId: blockId,
				__title: alohaBlock.getTitle(),
				init: function() {
					this._initializePropertiesFromSchema(alohaBlock.getSchema());
					this.recordCurrentStateAsOriginal();
				}
			}));
			$.each(internalAttributes, function(key, value) {
				blockProxy.set(key, value);
			})

			this._blocks[blockId] = blockProxy;

			this._blocksByNodePath[blockProxy.get('__nodePath')] = blockProxy;
			return this._blocks[blockId];
		},

		/**
		 * Retrieve block instance for a certain TYPO3CR Node Path
		 *
		 * @param {String} nodePath
		 * @return {T3.Content.Model.Block}
		 */
		getBlockByNodePath: function(nodePath) {
			if(this._blocksByNodePath[nodePath]) {
				return this._blocksByNodePath[nodePath];
			}
			var alohaBlock = Aloha.Block.BlockManager.getBlock($('[data-__nodepath="' + nodePath + '"]'));

			if (alohaBlock) {
				return this.getBlockProxy(alohaBlock);
			}
			return undefined;
		}
	});

	/**
	 * T3.Content.Model.BlockSelection
	 *
	 * Contains the currently selected blocks.
	 *
	 * This model is the one most listened to, as when the block selection changes, the UI
	 * is responding to that.
	 */
	var BlockSelection = SC.Object.create({
		blocks: [],

		initialize: function() {
			this.updateSelection();
		},

		/**
		 * Update the selection. If we have a block activated, we add the CSS class "t3-contentelement-selected" to the body
		 * so that we can modify the appearance of the block handles.
		 */
		updateSelection: function(alohaBlocks) {
			var blocks = [];
			if (this._updating) {
				return;
			}
			this._updating = true;

			if (alohaBlocks === undefined || alohaBlocks === null || alohaBlocks === [] || alohaBlocks.length == 0) {
				alohaBlocks = [];
				$('body').removeClass('t3-contentelement-selected');
			} else {
				$('body').addClass('t3-contentelement-selected');
			}
			if (alohaBlocks.length > 0) {
				$.each(alohaBlocks, function() {
					blocks.unshift(T3.Content.Model.BlockManager.getBlockProxy(this));
				});
			}
			blocks.unshift(BlockManager.get('_pageBlock'));
			this.set('blocks', blocks);
			this._updating = false;
		},

		selectedBlock: function() {
			var blocks = this.get('blocks');
			return blocks.length > 0 ? blocks[blocks.length - 1]: null;
		}.property('blocks').cacheable(),

		selectedBlockSchema: function() {
			var selectedBlock = this.get('selectedBlock');
			if (!selectedBlock) return;
			return selectedBlock.get('schema');
		}.property('selectedBlock').cacheable(),

		selectItem: function(item) {
			if (item === BlockManager.get('_pageBlock')) {
				this.updateSelection([]);
			} else {
				var block = Aloha.Block.BlockManager.getBlock(item.get('__alohaBlockId'));
				if (block) {
					window.setTimeout(function() {
						block.activate();
					}, 10);
				}
			}
		}
	});

	var PublishableBlocks = SC.ArrayProxy.create({
		content: [],

		noChanges: function() {
			return this.get('length') === 0;
		}.property('length'),

		add: function(block) {
			if (!this.contains(block)) {
				this.pushObject(block);
			}
		},
		remove: function(block) {
			this.removeObject(block);
		},

		/**
		 * Publish all blocks which are unsaved *and* on current page.
		 */
		publishAll: function() {
			T3.Content.Controller.ServerConnection.sendAllToServer(
				this,
				function(block) {
					return [block.get('__nodePath'), 'live'];
				},
				TYPO3_TYPO3_Service_ExtDirect_V1_Controller_WorkspaceController.publishNode,
				function() {
					T3.ContentModule.reloadPage();
				}
			);

				// Flush local changes so the UI updates
			this.set('[]', []);
		}
	});

	/**
	 * T3.Content.Model.Changes
	 *
	 * Contains a list of Blocks which contain changes.
	 *
	 * TODO: On saving, should empty local storage!
	 */
	var Changes = SC.ArrayProxy.create({
		content: [],

		_loadedFromLocalStore: false,

		initialize: function() {
			// We first set this._loadedFromLocalStore to FALSE; such that the removal
			// of all changes does NOT trigger a _saveToLocalStore.
			this._loadedFromLocalStore = false;
			this.set('[]', []);
			this._readFromLocalStore();
		},

		addChange: function(block) {
			if (!this.contains(block)) {
				this.pushObject(block);
			} else {
					// We still need to update the local store, if the block is already recorded as "changed"
				this._saveToLocalStore();
			}
		},
		removeChange: function(block) {
			this.removeObject(block);
		},

		noChanges: function() {
			return this.get('length') == 0;
		}.property('length').cacheable(),

		_readFromLocalStore: function() {
			if (!this._supports_html5_storage()) return;

			var serializedBlocks = window.localStorage['page_' + $('#t3-page-metainformation').attr('data-__nodepath')];

			if (serializedBlocks) {
				var blocks = JSON.parse(serializedBlocks);

				blocks.forEach(function(serializedBlock) {
					if (serializedBlock.__nodePath === $('#t3-page-metainformation').attr('data-__nodepath')) {
							// Serialized block is the page block
						var pageBlock = BlockManager.get('_pageBlock');
						$.each(serializedBlock, function(k, v) {
							pageBlock.set(k, v);
						});
					} else {
						var blockProxy = T3.Content.Model.BlockManager.getBlockByNodePath(serializedBlock.__nodePath);
						if (blockProxy) {
							$.each(serializedBlock, function(k, v) {
								blockProxy.set(k, v);
							});
						} else {
							// TODO: warning: Somehow the block was not found on the page anymore
						}
					}
				});
			}
			this._loadedFromLocalStore = true;
		},

		// TODO This doesn't work as a second observer on [] somehow
		_saveContentOnChange: function() {
			if (window.TYPO3_TYPO3_Service_ExtDirect_V1_Controller_NodeController) {
				this.save();
			}
		},

		_saveToLocalStore: function() {
			if (!this._supports_html5_storage()) return true;
			if (!this._loadedFromLocalStore) return true;

			var cleanedUpBlocks = this.get('[]').map(function(block) {
				return $.extend(
					block.getCleanedUpAttributes(),
					{__nodePath: block.get('__nodePath')}
				);
			});

			window.localStorage['page_' + $('#t3-page-metainformation').attr('data-__nodepath')] = JSON.stringify(cleanedUpBlocks);

			this._saveContentOnChange();
		}.observes('[]'),

		_supports_html5_storage: function() {
			try {
				return 'localStorage' in window && window['localStorage'] !== null;
			} catch (e) {
				return false;
			}
		},

		save: function(callback, reloadPage) {
			if (T3.Content.Controller.ServerConnection.get('_saveRunning')) {
				T3.Content.Controller.ServerConnection.set('_pendingSave', true);
				return;
			}
			reloadPage = reloadPage || this.checkIfReloadNeededAfterSave();

			var savedAttributes = {};
			T3.Content.Controller.ServerConnection.sendAllToServer(
				this,
				// Get attributes to be updated from block
				function(block) {
					var nodePath = block.get('__nodePath');
					// block.recordCurrentStateAsOriginal();

					var attributes = block.getCleanedUpAttributes();
					delete attributes['block-type'];

					savedAttributes[nodePath] = $.extend({}, attributes);

					attributes['__contextNodePath'] = nodePath;

					return [attributes];
				},
				TYPO3_TYPO3_Service_ExtDirect_V1_Controller_NodeController.update,
				// Callback on success after all changes were saved
				function() {
					// Remove changesToBeRemoved

					if (callback) {
						callback();
					}

					if (T3.Content.Controller.ServerConnection.get('_pendingSave')) {
						T3.Content.Controller.ServerConnection.set('_pendingSave', false);
						T3.Content.Model.Changes.save();
					}

					// Check if a changed property in the schema needs
					// a server-side reload
					if (reloadPage) {
						T3.ContentModule.reloadPage();
					}
				},
				// Callback on success per block
				function(block) {
					var nodePath = block.get('__nodePath');
					if (savedAttributes[nodePath]) {
						block.setOriginalValues(savedAttributes[nodePath]);
					}
				}
			);
		},

		checkIfReloadNeededAfterSave: function() {
			var reloadPage = false;
			this.get('[]').forEach(function(change) {
				$.each(change.__originalValues, function(key, value) {
					if (change.get(key) !== value) {
						var schema = change.get('schema'),
							changedPropertyDefinition = schema.properties[key];
						if (changedPropertyDefinition && changedPropertyDefinition.reloadOnChange) {
							reloadPage = true;
						}
					}
				});
			});
			return reloadPage;
		}
	});

	T3.Content.Model = {
		Block: Block,
		BlockManager: BlockManager,
		BlockSelection: BlockSelection,
		Changes: Changes,
		PublishableBlocks: PublishableBlocks
	}
	window.T3 = T3;
});
