Ext.ns("F3.TYPO3.Content.Edit");

/**
 * @class F3.TYPO3.Content.Edit.DeletePageDialog
 * @namespace F3.TYPO3.Content.Edit
 * @extends F3.TYPO3.UserInterface.ModuleDialog
 *
 * An empty dialog for deleting pages
 */
F3.TYPO3.Content.Edit.DeletePageDialog = Ext.extend(F3.TYPO3.UserInterface.ModuleDialog, {
	height: 80,
	initComponent: function() {
		this.context = Ext.getCmp('F3.TYPO3.Content.FrontendEditor').getCurrentContext()
		var config = {
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
			eval(F3.TYPO3.Core.Registry.get('schema/TYPO3:Page/service/delete')).call(this, this.context, this._onOkButtonClickActionSuccess, this);
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