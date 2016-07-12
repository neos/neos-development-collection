/**
 * NewPositionSelectorButton, used to select position for new
 * operations in the tree and in inline editing handles.
 */
define([
	'./AbstractPositionSelectorButton',
	'Shared/I18n'
],
function (
	AbstractPositionSelectorButton,
	I18n
) {
	return AbstractPositionSelectorButton.extend({
		iconClass: 'icon-paste',
		type: 'paste',
		init: function() {
			this._super();
			this.set('title', I18n.translate('TYPO3.Neos:Main:paste', 'Paste'));
		}
	});
});
