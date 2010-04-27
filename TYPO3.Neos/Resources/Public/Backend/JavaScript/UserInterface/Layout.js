Ext.ns("F3.TYPO3.UserInterface");

F3.TYPO3.UserInterface.Layout = Ext.extend(Ext.Viewport, {
	initComponent: function() {
		var config = {
			layout: 'vbox',
			layoutConfig: {
				align: 'stretch'
			},
			items: [{
				xtype: 'F3.TYPO3.UserInterface.TopBar',
				ref: 'topBar',
				flex: 0
			}, {
				xtype: 'F3.TYPO3.UserInterface.SectionMenu',
				ref: 'sectionMenu',
				flex: 1
			}]
		};
		Ext.apply(this, config);
		F3.TYPO3.UserInterface.Layout.superclass.initComponent.call(this);
	}
});