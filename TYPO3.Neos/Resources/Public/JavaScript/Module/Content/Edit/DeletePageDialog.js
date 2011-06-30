Ext.ns('TYPO3.TYPO3.Module.Content.Edit');

/*                                                                        *
 * This script belongs to the FLOW3 package "TYPO3".                      *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License as published by the Free   *
 * Software Foundation, either version 3 of the License, or (at your      *
 * option) any later version.                                             *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General      *
 * Public License for more details.                                       *
 *                                                                        *
 * You should have received a copy of the GNU General Public License      *
 * along with the script.                                                 *
 * If not, see http://www.gnu.org/licenses/gpl.html                       *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * @class TYPO3.TYPO3.Module.Content.Edit.DeletePageDialog
 *
 * An empty dialog for deleting pages
 *
 * @namespace TYPO3.TYPO3.Module.Content.Edit
 * @extends TYPO3.TYPO3.Components.Module.Dialog
 */
TYPO3.TYPO3.Module.Content.Edit.DeletePageDialog = Ext.extend(TYPO3.TYPO3.Components.Module.Dialog, {

	/**
	 * Initializer
	 */
	initComponent: function() {
		var config = {
			height: 1,
			items: []
		};
		Ext.apply(this, config);
		TYPO3.TYPO3.Module.Content.Edit.DeletePageDialog.superclass.initComponent.call(this);
	},

	/**
	 * Callback which is called when the OK button is clicked
	 *
	 * @return {void}
	 */
	onOk: function() {
		var currentContextNodePath = TYPO3.TYPO3.Module.ContentModule.getWebsiteContainer().getCurrentContextNodePath();
		TYPO3.TYPO3.Utils.getObjectByString(TYPO3.TYPO3.Core.Registry.get('schema/type/TYPO3.TYPO3:Page/service/delete')).call(this, currentContextNodePath, this._onDeleteSuccess, this);
	},

	/**
	 * Action when succes on button click action
	 * remove the dialog and refresh frontend editor
	 *
	 * @param {} response
	 * @return {void}
	 * @private
	 */
	_onDeleteSuccess: function(response) {
		this.moduleMenu.removeModuleDialog();
		TYPO3.TYPO3.Module.ContentModule.getWebsiteContainer().loadPage(response.data.nextUri);
	}

});
Ext.reg('TYPO3.TYPO3.Module.Content.Edit.DeletePageDialog', TYPO3.TYPO3.Module.Content.Edit.DeletePageDialog);