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
		iconClass: 'icon-plus',
		type: 'new',
		init: function() {
			this._super();
			this.set('title', I18n.translate('Neos.Neos:Main:createNew', 'Create new'));
		}
	});
});
