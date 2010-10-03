Ext.ns("F3.TYPO3.UserInterface");

F3.TYPO3.UserInterface.ModuleDialog = Ext.extend(Ext.Panel, {
	layout: 'fit',
	bodyStyle: 'background: #656565',
	removeOnContentDialogCancel: true,

	initComponent: function() {
		var config = {
			border: false,
			flex: 0,
			cls: 'F3-TYPO3-UserInterface-ModuleDialog'
		};
		Ext.apply(this, config);
		F3.TYPO3.UserInterface.ModuleDialog.superclass.initComponent.call(this);

		if (this.removeOnContentDialogCancel) {
			this.on('F3.TYPO3.UserInterface.ContentDialog.cancelled', function() {
				this.moduleMenu.removeModuleDialog();
			});
		}
	}
});
Ext.reg('F3.TYPO3.UserInterface.ModuleDialog', F3.TYPO3.UserInterface.ModuleDialog);