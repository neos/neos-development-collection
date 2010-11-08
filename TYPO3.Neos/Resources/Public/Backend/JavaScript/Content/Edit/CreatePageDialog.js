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
 * @class F3.TYPO3.Content.Edit.CreatePageDialog
 *
 * A dialog for creating pages
 *
 * @namespace F3.TYPO3.Content.Edit
 * @extends F3.TYPO3.UserInterface.ModuleDialog
 */
F3.TYPO3.Content.Edit.CreatePageDialog = Ext.extend(F3.TYPO3.UserInterface.ModuleDialog, {

	height: 80,

	/**
	 * Context of the parent page of the page to be created
	 *
	 * @type {Object}
	 * @private
	 */
	_context: null,

	/**
	 * Initializer
	 */
	initComponent: function() {
		var	context, config = {};

		config.items = F3.TYPO3.UserInterface.Form.FormFactory.createForm(
			'TYPO3:Page',
			'create',
			{
				ref: 'form',
				autoLoad: false,
				doSubmitForm: function() {
					var data = this.getForm().getValues();
					data = this._convertFlatPropertiesToNestedData(data);
					data['contentType'] = 'TYPO3:Page';
					this.getForm().api.create.call(this, this.getSubmitIdentifier(), data, this._onOkButtonClickActionSuccess, this);
				},
				/**
				 * Do not load any data, but store the initial context when
				 * the form was loaded.
				 *
				 * @return {void}
				 */
				onRenderLoad: function() {
					context = Ext.getCmp('F3.TYPO3.Content.FrontendEditor').getCurrentContext();
				},
				getSubmitIdentifier: function() {
					return context;
				},
				/**
				 * Action when succes on button click action
				 * remove the dialog and refresh frontend editor
				 *
				 * @return {void}
				 */
				_onOkButtonClickActionSuccess: function() {
					this.ownerCt.moduleMenu.removeModuleDialog();
					F3.TYPO3.Core.Application.fireEvent('F3.TYPO3.Content.contentChanged');
				}
			}
		);
		Ext.apply(this, config);
		F3.TYPO3.Content.Edit.CreatePageDialog.superclass.initComponent.call(this);

		this.on('F3.TYPO3.UserInterface.ContentDialog.buttonClick', this._onButtonClick, this);
		F3.TYPO3.Core.Application.on('F3.TYPO3.Content.contentChanged', this._refreshFrontendEditor, this);
	},

	/**
	 * When clicking the dialog ok button, we submit the form.
	 *
	 * @param {Ext.Component} button
	 * @private
	 */
	_onButtonClick: function(button) {
		if (button.itemId == 'okButton') {
			this.form.doSubmitForm();
		}
	},

	/**
	 * refresh the frontend editor
	 *
	 * @private
	 */
	_refreshFrontendEditor: function() {
		Ext.getCmp('F3.TYPO3.Content.FrontendEditor').reload();
	}
});
Ext.reg('F3.TYPO3.Content.Edit.CreatePageDialog', F3.TYPO3.Content.Edit.CreatePageDialog);