Ext.ns("F3.TYPO3.Content.Edit");

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
 * @class F3.TYPO3.Content.Edit.DeletePageDialog
 *
 * An empty dialog for deleting pages
 *
 * @namespace F3.TYPO3.Content.Edit
 * @extends F3.TYPO3.UserInterface.ModuleDialog
 */
F3.TYPO3.Content.Edit.DeletePageDialog = Ext.extend(F3.TYPO3.UserInterface.ModuleDialog, {

	height: 80,

	/**
	 * Context of the current page which is deleted
	 *
	 * @type {Object}
	 * @private
	 */
	_context: null,

	/**
	 * Initializer
	 */
	initComponent: function() {
		var config;

		this._context = Ext.getCmp('F3.TYPO3.Content.FrontendEditor').getCurrentContext();
		config = {
			items: []
		};
		Ext.apply(this, config);
		F3.TYPO3.Content.Edit.DeletePageDialog.superclass.initComponent.call(this);

		this.on('F3.TYPO3.UserInterface.ContentDialog.buttonClick', this._onOkButtonClickAction, this);
		F3.TYPO3.Core.Application.on('F3.TYPO3.Content.contentChanged', this._refreshFrontendEditor, this);
	},

	/**
	 * Action when clicking the dialog ok button
	 *
	 * @param {} button
	 * @return {void}
	 */
	_onOkButtonClickAction: function(button) {
		if (button.itemId == 'okButton') {
			F3.TYPO3.Utils.getObjectByString(F3.TYPO3.Core.Registry.get('schema/TYPO3:Page/service/delete')).call(this, this._context, this._onOkButtonClickActionSuccess, this);
		}
	},

	/**
	 * Action when succes on button click action
	 * remove the dialog and refresh frontend editor
	 *
	 * @return {void}
	 */
	_onOkButtonClickActionSuccess: function() {
		this.moduleMenu.removeModuleDialog();
		F3.TYPO3.Core.Application.fireEvent('F3.TYPO3.Content.contentChanged', '###pageId###');
	},

	/**
	 * refresh the frontend editor
	 *
	 *  @return {void}
	 */
	_refreshFrontendEditor: function() {
		Ext.getCmp('F3.TYPO3.Content.FrontendEditor').reload();
	}
});
Ext.reg('F3.TYPO3.Content.Edit.DeletePageDialog', F3.TYPO3.Content.Edit.DeletePageDialog);