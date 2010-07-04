Ext.ns('F3.TYPO3.UserInterface');

F3.TYPO3.UserInterface.LoginStatus = Ext.extend(Ext.Container, {
	initComponent: function() {
		var config = {
			layout: 'hbox',
			layoutConfig: {
				pack: 'end',
				align: 'middle'
			},
			items: [{
					xtype: 'box',
					width: 150,
					itemId: 'statusText',
					tpl: '<tpl for="name">Hello, {fullName}</tpl>',
					id: 'F3-TYPO3-TopBar-StatusText'
				}, {
					xtype: 'F3.TYPO3.Components.Button',
					itemId: 'logoutButton',
					text: 'Logout',
					handler: function() {
						F3.TYPO3.Login.Service.logout();
					},
					scope: this
				}]
		};
		Ext.apply(this, config);
		F3.TYPO3.UserInterface.LoginStatus.superclass.initComponent.call(this);

		F3.TYPO3.Application.on('F3.TYPO3.Login.updated', function(party) {
			this.getComponent('statusText').update(party);
			this.getComponent('statusText').el.fadeIn();
			this.doLayout();
		}, this);
	}

});
Ext.reg('F3.TYPO3.UserInterface.LoginStatus', F3.TYPO3.UserInterface.LoginStatus);