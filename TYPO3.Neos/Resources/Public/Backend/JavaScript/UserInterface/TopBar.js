Ext.ns('F3.TYPO3.UserInterface');

F3.TYPO3.UserInterface.TopBar = Ext.extend(Ext.Panel, {
	height: 62,
	
	initComponent: function() {
		var config = {
			layout: 'vbox',
			layoutConfig: {
				padding: '5px',
				align: 'stretch'
			},
			border: false,
			bodyStyle: 'background-color: #666666',
			items: [{
				height: 32,
				xtype: 'container',
				layout: 'hbox',
				items: [{
					xtype: 'box',
					width: 150,
					height: 32,
					flex: 0
					}, {
						xtype: 'box',
						width: 32,
						flex: 0
					}, {
						xtype: 'box',
						width: 230,
						height: 32,
						flex: 0
					}, {
						xtype: 'box',
						flex: 1
					}, {
						xtype: 'box',
						id: 'F3-TYPO3-TopBar-Logo',
						height: 32,
						width: 200,
						flex: 2
					}]
				}, {
					xtype: 'F3.TYPO3.UserInterface.LoginStatus',
					width: 250,
					height: 30,
					flex: 0
				}]


			
		};
		Ext.apply(this, config);
		F3.TYPO3.UserInterface.TopBar.superclass.initComponent.call(this);
	}

});
Ext.reg('F3.TYPO3.UserInterface.TopBar', F3.TYPO3.UserInterface.TopBar);