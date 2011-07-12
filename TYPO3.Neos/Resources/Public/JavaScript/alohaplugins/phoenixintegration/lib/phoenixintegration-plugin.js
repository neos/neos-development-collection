define(
['core/plugin', 'phoenixintegration/block', 'block/blockmanager', 'require'],
function(Plugin, block, BlockManager, require) {
    "use strict";

    var PhoenixPlugin = Plugin.create('phoenixintegration', {
    	dependencies: ['block'],
        init: function() {
			var that = this;
			BlockManager.registerBlockType('TextBlock', block.TextBlock);
			BlockManager.registerBlockType('PluginBlock', block.PluginBlock);

        	require(['phoenix/contentmodule'], function() {
				BlockManager.bind('block-selection-change', T3.ContentModule._onBlockSelectionChange, T3.ContentModule);

				Aloha.bind("aloha-editable-deactivated", that._onEditableChange);
				Aloha.bind("aloha-smart-content-changed", that._onEditableChange);
        	});
        },

		_onEditableChange: function(event, data) {
			var editable = data.editable;
			if (!editable || !editable.isModified()) {
				return;
			}

			// TODO: the following lines do NOT work!
			// When we re-render a block, we create *NEW* editables *WITHOUT* properly destroying
			// the old ones. !!! We should somehow change this maybe, such that we do not need to
			// re-build the editables all the time as this is quite costly and could result in strange user behavior.
			//var surroundingAlohaBlockDomElement = editable.originalObj.parents('.aloha-block').first();
			//var surroundingAlohaBlock = BlockManager.getBlock(surroundingAlohaBlockDomElement);
			//var blockProxy = ContentModule.BlockManager.getBlockProxy(surroundingAlohaBlock);
			//blockProxy.set(editable.obj.data('propertyname'), editable.getContents());
		},
		destroy: function() {
        }
    });
	return PhoenixPlugin;
});