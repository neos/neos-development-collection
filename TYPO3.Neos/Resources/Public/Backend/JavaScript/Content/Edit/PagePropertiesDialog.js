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
					load: F3.TYPO3_Controller_Backend_PageController.show,
					submit: F3.TYPO3_Controller_Backend_PageController.update
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

		this.form.on('beforeaction', function(form, action) {
			if (action.type === 'directload') {
				console.log('Loading');
				this.el.mask('Loading...');
			}
			if (action.type === 'directsubmit') {
				this.el.mask('Saving...');
			}
		}, this);

		this.form.on('actioncomplete', function(form, action) {
			console.log('Action complete');
			if (action.type === 'directload') {
				console.log('Done Loading');
				this.el.unmask();
			}
			if (action.type === 'directsubmit') {
				this.el.unmask();
			}
		}, this);

		this.on('render', function() {
			this.form.load({
				params: {
					// TODO Get identity from data attribute of iFrame or global context
					'__identity': 'ef8eb808-cfb4-4aa5-8d2d-37ee02ec885d'
				}
			});
		}, this);

		this.on('F3.TYPO3.UserInterface.ContentDialog.buttonClick', function(button) {
			if (button.itemId == 'okButton') {
				this.form.getForm().submit({
					additionalValues: {
						// TODO Get identity from data attribute of iFrame or global context
						'__identity': 'ef8eb808-cfb4-4aa5-8d2d-37ee02ec885d'
					},
					success: function() {
						this.moduleMenu.removeModuleDialog();
						F3.TYPO3.Application.fireEvent('F3.TYPO3.Content.contentChanged', '###pageId###');
					},
					scope: this
				});
			}
		}, this);
	}
});
Ext.reg('F3.TYPO3.Content.Edit.PagePropertiesDialog', F3.TYPO3.Content.Edit.PagePropertiesDialog);
