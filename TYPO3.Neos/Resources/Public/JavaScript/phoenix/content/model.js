/**
 * T3.Content.Model
 *
 * Contains the main models underlying the content module UI.
 *
 * The most central model is the "Block" model, which shadows the Aloha Block model.
 */

define(
['text!phoenix/common/launcher.html'],
function(launcherTemplate) {

	var T3 = window.T3 || {};
	T3.Content = T3.Content || {};
	T3.Content.Model = {};

	/**
	 * T3.Content.Model.Block
	 *
	 * The most central model class, which represents a single content element, and wraps and
	 * extends an Aloha Block for use in SproutCore, so we can use data binding on it.
	 */
	var Block = SC.Object.extend({
		alohaBlockId: null,
		_title:null,
		_originalValues: null,

		// Some hack which is fired when we change a property. Should be replaced with a proper API method which should be fired *every time* a property is changed.
		_somePropertyChanged: function(that, propertyName) {
			var alohaBlock = Aloha.Block.BlockManager.getBlock(this.get('alohaBlockId'));
			// Save original property back to Aloha Block
			if (!this._disableAlohaBlockUpdate) {
				alohaBlock.attr(propertyName, this.get(propertyName));
			}
			var hasChanges = false;
			$.each(this._originalValues, function(key, value) {
				if (that.get(key) !== value) {
					hasChanges = true;
				}
			});
			if (hasChanges) {
				alohaBlock.attr('_status', 'modified');
				Changes.addChange(this);
			} else {
				alohaBlock.attr('_status', '');
				Changes.removeChange(this);
			}
		},
		schema: function() {
			var alohaBlock = Aloha.Block.BlockManager.getBlock(this.get('alohaBlockId'));
			return alohaBlock.getSchema();
		}.property().cacheable(),

		revertChanges: function() {
			var that = this;
			$.each(this._originalValues, function(key, oldValue) {
				that.set(key, oldValue);
			});
		},

		/**
		 * Returns a simple JSON object containing the simple and cleaned
		 * attributes for the block
		 */
		getCleanedUpAttributes: function() {
			var that = this, cleanedAttributes = {};
			$.each(this._originalValues, function(key) {
				cleanedAttributes[key] = that.get(key);
			});

			return cleanedAttributes;
		},

		// HACK for making updates of editables work...
		_doNotUpdateAlohaBlock: function() {
			this._disableAlohaBlockUpdate = true;
		},
		_enableAlohaBlockUpdateAgain: function() {
			this._disableAlohaBlockUpdate = false;
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
		/**
		 * @param block/block Aloha Block instance
		 */
		getBlockProxy: function(alohaBlock) {
			var blockId = alohaBlock.getId();
			if (this._blocks[blockId]) {
				return this._blocks[blockId];
			}

			var attributes = alohaBlock.attr();

			var blockProxy = Block.create($.extend({}, attributes, {
				alohaBlockId: blockId,
				_title: alohaBlock.title,
				_originalValues: null,
				init: function() {
					var that = this;
					this._originalValues = {};

					// HACK: Add observer for each element, as we do not know how to add one observer for *all* elements.
					$.each(attributes, function(key, value) {

						// HACK: This is for supporting more data types, i.e. mostly for boolean values in checkboxes.
						if (value == "true") {
							value = true;
						} else if (value == "false") {
							value = false;
						}
						that._originalValues[key] = value;
						that.addObserver(key, that, that._somePropertyChanged);
					});
				}
			}));

			this._blocks[blockId] = blockProxy;
			return this._blocks[blockId];
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

		/**
		 * Update the selection. If we have a block activated, we add the CSS class "t3-contentelement-selected" to the body
		 * so that we can modify the appearance of the block handles.
		 */
		updateSelection: function(blocks) {
			if (this._updating) {
				return;
			}
			this._updating = true;

			if (blocks === undefined || blocks === null || blocks === [] || blocks.length == 0) {
				blocks = [];
				$('body').removeClass('t3-contentelement-selected');
			} else {
				$('body').addClass('t3-contentelement-selected');
			}
			if (blocks.length > 0 && typeof blocks[0].getSchema !== 'undefined') {
				blocks = $.map(blocks, function(alohaBlock) {

					if (alohaBlock.id === 't3-page') {
						return alohaBlock; // FIXME: Special case for editing pages
					} else {
						return T3.Content.Model.BlockManager.getBlockProxy(alohaBlock);
					}
				});
			}
			this.set('blocks', blocks);
			this._updating = false;
		},

		selectedBlock: function() {
			var blocks = this.get('blocks');
			return blocks.length > 0 ? blocks[0]: null;
		}.property('blocks').cacheable(),

		selectedBlockSchema: function() {
			var selectedBlock = this.get('selectedBlock');
			if (!selectedBlock) return;
			return selectedBlock.get('schema');
		}.property('selectedBlock').cacheable(),

		selectPage: function() {
			Aloha.Block.BlockManager._deactivateActiveBlocks();

			var blocks = [
				new T3.Content.UI.BreadcrumbPage()
			];

			this.updateSelection(blocks);
		},

		selectItem: function(item) {
			var block = Aloha.Block.BlockManager.getBlock(item.id);
			if (block) {
				// FIXME !!! This is to prevent the event triggering a refresh of the blocks which trigger an event and kill the selection
				this._updating = true;
				block.activate();
				this._updating = false;
			}
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

		addChange: function(block) {
			if (!this.contains(block)) {
				this.pushObject(block);
			}
		},
		removeChange: function(block) {
			this.removeObject(block);
		},

		noChanges: function() {
			return this.get('length') == 0;
		}.property('length'),

		_readFromLocalStore: function() {
			if (!this._supports_html5_storage()) return;

			var serializedBlocks = window.localStorage['page_' + $('body').attr('about')];

			if (serializedBlocks) {
				var blocks = JSON.parse(serializedBlocks);
				blocks.forEach(function(serializedBlock) {
					var alohaBlock = Aloha.Block.BlockManager.getBlock($('[about="' + serializedBlock.about + '"]'));
					if (alohaBlock) {
						var blockProxy = T3.Content.Model.BlockManager.getBlockProxy(alohaBlock);
						$.each(serializedBlock, function(k, v) {
							blockProxy.set(k, v);
						});
					} else {
						// TODO: warning: Somehow the block was not found on the page anymore
					}
				});
			}
			this._loadedFromLocalStore = true;
		},

		_saveToLocalStore: function() {
			if (!this._supports_html5_storage()) return;
			if (!this._loadedFromLocalStore) return;

			var cleanedUpBlocks = this.get('[]').map(function(block) {
				return block.getCleanedUpAttributes();
			});

			window.localStorage['page_' + $('body').attr('about')] = JSON.stringify(cleanedUpBlocks);
		}.observes('[]'),

		_supports_html5_storage: function() {
			try {
				return 'localStorage' in window && window['localStorage'] !== null;
			} catch (e) {
				return false;
			}
		},

		revert: function() {
			this.forEach(function(block) {
				block.revertChanges();
			}, this);
		},
		save: function() {
			// TODO: send changes to server side

			// TODO: for each block, record current state as "original"

			// Flush local changes
			this.set('[]', []);
		}
	});


	T3.Content.Model = {
		Block: Block,
		BlockManager: BlockManager,
		BlockSelection: BlockSelection,
		Changes: Changes
	}
	window.T3 = T3;
});