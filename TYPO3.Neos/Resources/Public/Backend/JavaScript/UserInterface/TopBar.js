Ext.ns('F3.TYPO3.UserInterface');

F3.TYPO3.UserInterface.TopBar = Ext.extend(Ext.Panel, {
	height: 50,
	
	initComponent: function() {
		var config = {
			layout: 'hbox',
			layoutConfig: {
				padding: '5px'
			},
			border: false,
			bodyStyle: 'background-color: #666666',
			items: [{
					xtype: 'F3.TYPO3.UserInterface.DummyImage',
					backgroundImage: 'dummys/topbar_buttons.png',
					width: 150,
					height: 25,
					flex: 0
				}, {
					xtype: 'box',
					width: 50,
					flex: 0
				}, {
					xtype: 'F3.TYPO3.UserInterface.DummyImage',
					backgroundImage: 'dummys/topbar_message.png',
					width: 230,
					height: 25,
					flex: 0
				}, {
					xtype: 'box',
					flex: 1
				}, {
					xtype: 'F3.TYPO3.UserInterface.DummyImage',
					backgroundImage: 'dummys/topbar_logo.png',
					width: 100,
					height: 32,
					flex: 0
				}]
		};
		Ext.apply(this, config);
		F3.TYPO3.UserInterface.TopBar.superclass.initComponent.call(this);
	}

});
Ext.reg('F3.TYPO3.UserInterface.TopBar', F3.TYPO3.UserInterface.TopBar);