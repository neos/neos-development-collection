/**
 */
define(
[
	'Library/jquery-with-dependencies',
	'vie/entity',
	'InlineEditing/InlineEditingHandles/ContentElementHandle'
],
function ($, EntityWrapper, ContentElementHandle) {
	return ContentElementHandle.extend({
		_showRemove: false,
		_showCut: false,
		_showCopy: false,
		_showHide: false,

		/**
		 * Returns the index of the content element in the current section
		 */
		_collectionIndex: function() {
			return 0;
		}.property('_selectedNode')
	});
});