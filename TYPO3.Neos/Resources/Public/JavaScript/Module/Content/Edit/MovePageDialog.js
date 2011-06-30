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
 * @class TYPO3.TYPO3.Module.Content.Edit.MovePageDialog
 *
 * A dialog for creating pages
 *
 * @namespace TYPO3.TYPO3.Module.Content.Edit
 * @extends TYPO3.TYPO3.Components.Module.Dialog
 */
TYPO3.TYPO3.Module.Content.Edit.MovePageDialog = Ext.extend(TYPO3.TYPO3.Components.Module.Dialog, {

	/**
	 * Initializer
	 */
	initComponent: function() {
		var config = {
				items: TYPO3.TYPO3.Components.Form.FormFactory.createForm(
					'TYPO3.TYPO3:Page',
					'move',
					{
						ref: 'form',
						autoLoad: false,
						/**
						 * Validate form and submit
						 *
						 * @return {void}
						 */
						doSubmitForm: function() {
							this.getForm().api.move.call(
								this,
								TYPO3.TYPO3.Module.ContentModule.getWebsiteContainer().getCurrentContextNodePath(),
								Ext.getCmp('TYPO3.TYPO3.Module.ContentModule.move').getContextNodePath(),
								Ext.getCmp('TYPO3.TYPO3.Module.ContentModule.move').getPosition(),
								this._onFormSubmitSuccess,
								this
							);
						},
						/**
						 * Action when success on button click action
						 * remove the dialog and load new page in frontend editor
						 *
						 * @param {} response
						 * @return {void}
						 */
						_onFormSubmitSuccess: function(response, status) {
							if (response) {
								this.ownerCt.moduleMenu.removeModuleDialog();
								TYPO3.TYPO3.Module.ContentModule.getWebsiteContainer().loadPage(response.data.nextUri);
							} else if (status.type === 'exception') {
								Ext.MessageBox.alert(TYPO3.TYPO3.Core.I18n.get('TYPO3.TYPO3', 'errorOccurred'), status.message);
							} else {
								Ext.MessageBox.alert(TYPO3.TYPO3.Core.I18n.get('TYPO3.TYPO3', 'unknownErrorOccurred'));
							}
						}
					}
				)
			};
		Ext.apply(this, Ext.apply(this.initialConfig, config));
		TYPO3.TYPO3.Module.Content.Edit.MovePageDialog.superclass.initComponent.call(this);
	},

	/**
	 * When clicking the dialog ok button, we submit the form.
	 *
	 * @return {void}
	 */
	onOk: function() {
		this.form.doSubmitForm();
	}
});
Ext.reg('TYPO3.TYPO3.Module.Content.Edit.MovePageDialog', TYPO3.TYPO3.Module.Content.Edit.MovePageDialog);