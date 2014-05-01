/**
 */
define(
[
	'Library/jquery-with-dependencies',
	'Content/Model/Node',
	'InlineEditing/InlineEditingHandles/ContentElementHandle',
	'InlineEditing/ContentCommands'
],
function ($, EntityWrapper, ContentElementHandle, ContentCommands) {
	return ContentElementHandle.extend({
		_showRemove: false,
		_showCut: false,
		_showCopy: false,
		_showHide: false,

		_pasteTitle: 'Paste into',

		newAfter: function() {
			ContentCommands.create('after', null, 0);
		}
	});
});