Ext.ns('F3.TYPO3.Module.Content.Edit');

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
 * @class F3.TYPO3.Module.Content.Edit.CreatePageDialog
 *
 * A dialog for creating pages
 *
 * @namespace F3.TYPO3.Module.Content.Edit
 * @extends F3.TYPO3.Components.Module.Dialog
 */
F3.TYPO3.Module.Content.Edit.CreatePageDialog = Ext.extend(F3.TYPO3.Components.Module.Dialog, {

	/**
	 * Initializer
	 */
	initComponent: function() {
		var config = {
			items: F3.TYPO3.Components.Form.FormFactory.createForm(
				'TYPO3:Page',
				'create',
				{
					ref: 'form',
					autoLoad: false,
					/**
					 * Validate form and submit
					 *
					 * @return {void}
					 */
					doSubmitForm: function() {
						if (!this.getForm().isValid()) return;
						var data = this.getForm().getValues();
						data = this._convertFlatPropertiesToNestedData(data);
						data['contentType'] = 'TYPO3:Page';
						this.getForm().api.create.call(
							this,
							Ext.getCmp('F3.TYPO3.Module.ContentModule.create').getContextNodePath(),
							data,
							Ext.getCmp('F3.TYPO3.Module.ContentModule.create').getPosition(),
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
							F3.TYPO3.Module.ContentModule.getWebsiteContainer().loadPage(response.data.nextUri);
						} else if (status.type == 'exception') {
							Ext.MessageBox.alert(F3.TYPO3.Core.I18n.get('TYPO3', 'errorOccurred'), status.message);
						} else {
							Ext.MessageBox.alert(F3.TYPO3.Core.I18n.get('TYPO3', 'unknownErrorOccurred'));
						}
					}
				}
			)
		};
		Ext.apply(this, Ext.apply(this.initialConfig, config));
		F3.TYPO3.Module.Content.Edit.CreatePageDialog.superclass.initComponent.call(this);
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
Ext.reg('F3.TYPO3.Module.Content.Edit.CreatePageDialog', F3.TYPO3.Module.Content.Edit.CreatePageDialog);