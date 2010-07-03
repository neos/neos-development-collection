Ext.ns('F3.TYPO3.UserInterface');

F3.TYPO3.UserInterface.TopBar = Ext.extend(Ext.Panel, {
	height: 70,
	
	initComponent: function() {
		var config = {
			layout: 'hbox',
			layoutConfig: {
				padding: '5px'
			},
			border: false,
			bodyStyle: 'background-color: #666666',
			items: [{
					xtype: 'box',
					width: 150,
					height: 25,
					flex: 0
				}, {
					xtype: 'box',
					width: 50,
					flex: 0
				}, {
					xtype: 'box',
					width: 230,
					height: 25,
					flex: 0
				}, {
					xtype: 'box',
					flex: 1
				}, {
					width: 200,
					height: 70,
					xtype: 'container',
					layout: 'vbox',
					items: [{
						xtype: 'box',
						id: 'F3-TYPO3-TopBar-Logo',
						height: 32,
						width: 100,
						flex: 0
					}, {
						xtype: 'F3.TYPO3.UserInterface.LoginStatus',
						height: 38,
						width: 200,
						flex: 0
					}]
				}]
		};
		Ext.apply(this, config);
		F3.TYPO3.UserInterface.TopBar.superclass.initComponent.call(this);
	}

});
Ext.reg('F3.TYPO3.UserInterface.TopBar', F3.TYPO3.UserInterface.TopBar);