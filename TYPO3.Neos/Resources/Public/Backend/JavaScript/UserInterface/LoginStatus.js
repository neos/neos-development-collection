Ext.ns('F3.TYPO3.UserInterface');

F3.TYPO3.UserInterface.LoginStatus = Ext.extend(Ext.Container, {
	initComponent: function() {
		var config = {
			layout: 'hbox',
			items: [{
					xtype: 'box',
					itemId: 'statusText',
					tpl: '<tpl for="name">Hello, {fullName}</tpl>'
				}, {
					xtype: 'button',
					itemId: 'logoutButton',
					text: 'Logout',
					handler: function() {
						
					},
					scope: this
				}]
		};
		Ext.apply(this, config);
		F3.TYPO3.UserInterface.LoginStatus.superclass.initComponent.call(this);

		F3.TYPO3.Application.on('F3.TYPO3.Login.updated', function(party) {
			this.getComponent('statusText').update(party);
			this.doLayout();
		}, this);
	}

});
Ext.reg('F3.TYPO3.UserInterface.LoginStatus', F3.TYPO3.UserInterface.LoginStatus);