Ext.ns("F3.TYPO3.Content.Edit");

/**
 * @class F3.TYPO3.Content.Edit.PagePropertiesDialog
 * @namespace F3.TYPO3.Content.Edit
 * @extends F3.TYPO3.UserInterface.ModuleDialog
 *
 * The dialog for editing page properties, e.g. title and navigation title
 */
F3.TYPO3.Content.Edit.PagePropertiesDialog = Ext.extend(F3.TYPO3.UserInterface.ModuleDialog, {
	height: 80,
	initComponent: function() {
		var config = {
			layout: 'fit',
			bodyStyle: 'background: #656565',
			items: {
				xtype: 'form',
				ref: 'form',
				api: {
					load: F3.TYPO3_Controller_Service_PageController.show,
					submit: F3.TYPO3_Controller_Service_PageController.update
				},
				paramsAsHash: true,
				border: false,
				style: 'padding: 10px',
				bodyStyle: 'background: transparent',
				items: [{
					xtype: 'textfield',
					fieldLabel: 'Page title',
					name: 'title',
					width: 400
				}, {
					xtype: 'checkbox',
					fieldLabel: 'Visibility',
					boxLabel: 'hidden',
					name: 'hidden',
					width: 400
				}]
			}
		};
		Ext.apply(this, config);
		F3.TYPO3.Content.Edit.PagePropertiesDialog.superclass.initComponent.call(this);

		// add event listener
		this.form.on('beforeaction', this.onFormBeforeaction , this);
		this.form.on('actioncomplete', this.onFormActioncomplete, this);
		this.form.on('actionfailed', this.onFormActioncomplete, this);
		this.on('F3.TYPO3.UserInterface.ContentDialog.buttonClick', this.onOkButtonClickAction, this);
		F3.TYPO3.Application.on('F3.TYPO3.Content.contentChanged', this.refreshFrontendEditor, this);
	},

	// privat
	onRender : function(ct, position) {
		this.pageIdentity = Ext.getCmp('F3.TYPO3.Content.FrontendEditor').getCurrentPageIdentity();
		F3.TYPO3.Content.Edit.PagePropertiesDialog.superclass.onRender.call(this, ct, position);
		this.form.load({
			params: this.pageIdentity
		});
	},

	/**
	 * on form before action
	 *
	 * @param {} form
	 * @param {} action
	 * ®return {void}
	 */
	onFormBeforeaction: function(form, action) {
		if (action.type === 'directload') {
			this.el.mask('Loading...');
		}
		if (action.type === 'directsubmit') {
			this.el.mask('Saving...');
		}
	},

	/**
	 * on form action complete
	 *
	 * @param {} form
	 * @param {} action
	 * ®return {void}
	 */
	onFormActioncomplete: function(form, action) {
		if (action.type === 'directload') {
			this.el.unmask();
		}
		if (action.type === 'directsubmit') {
			this.el.unmask();
		}
	},

	/**
	 * Action when click the dialog ok button
	 * submit the dialog form
	 *
	 * @param {} button
	 * @return {void}
	 */
	onOkButtonClickAction: function(button) {
		if (button.itemId == 'okButton') {
			this.form.getForm().submit({
				additionalValues: this.pageIdentity,
				success: this.onOkButtonClickActionSuccess,
				scope: this
			});
		}
	},

	/**
	 * Action when succes on button click action
	 * remove the dialog and refresh frontend editor
	 *
	 * @return {void}
	 */
	onOkButtonClickActionSuccess: function() {
		this.moduleMenu.removeModuleDialog();
		F3.TYPO3.Application.fireEvent('F3.TYPO3.Content.contentChanged', '###pageId###');
	},

	/**
	 * refresh the frontend editor
	 *
	 *  @return {void}
	 */
	refreshFrontendEditor: function() {
		Ext.getCmp('F3.TYPO3.Content.FrontendEditor').reload();
	}
});
Ext.reg('F3.TYPO3.Content.Edit.PagePropertiesDialog', F3.TYPO3.Content.Edit.PagePropertiesDialog);