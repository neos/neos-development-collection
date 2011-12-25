define(
['aloha/plugin', 'phoenixintegration/block', 'block/blockmanager', 'require'],
function(Plugin, block, BlockManager, require) {
    "use strict";

    var PhoenixPlugin = Plugin.create('phoenixintegration', {
    	dependencies: ['block'],

		/**
		 * Aloha Plugin Lifecycle method
		 */
        init: function() {
			BlockManager.registerBlockType('TYPO3Block', block.TYPO3Block);
        },

		/**
		 * Starter -- called *after* content module is initialized (and T3.ContentModule._onBlockSelectionChange is available)
		 */
		start: function() {
			BlockManager.bind('block-selection-change', T3.ContentModule._onBlockSelectionChange, T3.ContentModule);

			Aloha.bind('aloha-editable-deactivated', this._onEditableChange);
			Aloha.bind('aloha-smart-content-changed', this._onEditableChange);
		},

		_onEditableChange: function(event, data) {
			var editable = data.editable;
			if (!editable || !editable.isModified()) {
				return;
			}

			var surroundingAlohaBlockDomElement = editable.originalObj.parents('.aloha-block').first();
			var surroundingAlohaBlock = BlockManager.getBlock(surroundingAlohaBlockDomElement);
			var blockProxy = T3.Content.Model.BlockManager.getBlockProxy(surroundingAlohaBlock);

			blockProxy._doNotUpdateAlohaBlock();
			blockProxy.set(editable.obj.data('propertyname'), editable.getContents());
			blockProxy._enableAlohaBlockUpdateAgain();
		},
		destroy: function() {
        }
    });
	// We need a global reference here to call the start() method on the phoenix plugin
	// from the contentmodule bootstrap
	window.PhoenixAlohaPlugin = PhoenixPlugin;
	return PhoenixPlugin;
});