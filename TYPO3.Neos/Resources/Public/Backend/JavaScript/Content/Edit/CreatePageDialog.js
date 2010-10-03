Ext.ns("F3.TYPO3.Content.Edit");

/**
 * @class F3.TYPO3.Content.Edit.CreatePageDialog
 * @namespace F3.TYPO3.Content.Edit
 * @extends F3.TYPO3.UserInterface.ModuleDialog
 *
 * A dialog for creating pages
 */
F3.TYPO3.Content.Edit.CreatePageDialog = Ext.extend(F3.TYPO3.UserInterface.ModuleDialog, {
	height: 80,
	initComponent: function() {
		var	context,
			config = {
			items: F3.TYPO3.UserInterface.Form.FormFactory.createForm('TYPO3:Page', 'create', {
				ref: 'form',
				autoLoad: false,
				doSubmitForm: function() {
					var data = this.getForm().getValues();
					data = this._convertFlatPropertiesToNestedData(data);
					data['contentType'] = 'TYPO3:Page';
					this.getForm().api.create.call(this, this.getSubmitIdentifier(), data, this._onOkButtonClickActionSuccess, this);
				},
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
					F3.TYPO3.Core.Application.fireEvent('F3.TYPO3.Content.contentChanged', '###pageId###');
				}
			})
		};
		Ext.apply(this, config);
		F3.TYPO3.Content.Edit.CreatePageDialog.superclass.initComponent.call(this);

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
			this.form.doSubmitForm();
		}
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
Ext.reg('F3.TYPO3.Content.Edit.CreatePageDialog', F3.TYPO3.Content.Edit.CreatePageDialog);