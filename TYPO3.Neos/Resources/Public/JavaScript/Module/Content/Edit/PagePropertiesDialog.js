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
 * @class TYPO3.TYPO3.Module.Content.Edit.PagePropertiesDialog
 *
 * The dialog for editing page properties, e.g. title and navigation title
 *
 * @namespace TYPO3.TYPO3.Module.Content.Edit
 * @extends TYPO3.TYPO3.Components.Module.Dialog
 */
TYPO3.TYPO3.Module.Content.Edit.PagePropertiesDialog = Ext.extend(TYPO3.TYPO3.Components.Module.Dialog, {

	/**
	 * Initializer
	 */
	initComponent: function() {
		var currentContextNodePath, config = {};

		config.items = TYPO3.TYPO3.Components.Form.FormFactory.createForm(
			'TYPO3.TYPO3:Page',
			'pageProperties',
			{
				ref: 'form',
				getLoadIdentifier: function() {
					currentContextNodePath = TYPO3.TYPO3.Module.ContentModule.getWebsiteContainer().getCurrentContextNodePath();
					return {__contextNodePath: currentContextNodePath};
				},
				getSubmitIdentifier: function() {
					return currentContextNodePath;
				},
				onSubmitSuccess: this._onPagePropertiesSaved.createDelegate(this)
			}
		);

		Ext.apply(this, config);
		TYPO3.TYPO3.Module.Content.Edit.PagePropertiesDialog.superclass.initComponent.call(this);
	},

	/**
	 * Action when clicking the dialog ok button
	 *
	 * @return {void}
	 */
	onOk: function() {
		this.form.doSubmitForm();
	},

	/**
	 * Action when succes on button click action
	 * remove the dialog and refresh frontend editor
	 *
	 * @private
	 */
	_onPagePropertiesSaved: function() {
		this.moduleMenu.removeModuleDialog();

		TYPO3.TYPO3.Module.ContentModule.getWebsiteContainer().reload();
	}
});
Ext.reg('TYPO3.TYPO3.Module.Content.Edit.PagePropertiesDialog', TYPO3.TYPO3.Module.Content.Edit.PagePropertiesDialog);