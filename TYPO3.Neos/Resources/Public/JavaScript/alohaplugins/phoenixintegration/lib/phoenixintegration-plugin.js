define(
['aloha/plugin', 'phoenixintegration/block', 'block/blockmanager', 'require'],
function(Plugin, block, BlockManager, require) {
    "use strict";

	var $ = window.alohaQuery || window.jQuery;

    var PhoenixPlugin = Plugin.create('phoenixintegration', {
    	dependencies: ['block'],
        init: function() {
			var that = this;
			BlockManager.registerBlockType('TextBlock', block.TextBlock);
			BlockManager.registerBlockType('PluginBlock', block.PluginBlock);
			BlockManager.registerBlockType('TextWithImageBlock', block.TextWithImageBlock);

        	require(['phoenix/contentmodule'], function() {
				BlockManager.bind('block-selection-change', T3.ContentModule._onBlockSelectionChange, T3.ContentModule);

				Aloha.bind('aloha-editable-deactivated', that._onEditableChange);
				Aloha.bind('aloha-smart-content-changed', that._onEditableChange);
        	});
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
	return PhoenixPlugin;
});