Ext.ns("F3.TYPO3.UserInterface");

F3.TYPO3.UserInterface.SectionMenu = Ext.extend(Ext.TabPanel, {
	initComponent: function() {
		var config = {
			border: false,
			cls: 'F3-TYPO3-UserInterface-SectionMenu',
			items: this._getSectionMenuItems()
		};
		Ext.apply(this, config);
		F3.TYPO3.UserInterface.SectionMenu.superclass.initComponent.call(this);

		this.on('tabchange', function(sectionMenu, tab) {
			F3.TYPO3.History.HistoryManager.set('SectionMenu', tab.itemId);
			F3.TYPO3.UserInterface.UserInterfaceModule.fireEvent('activate-menu/main/' + tab.itemId, tab);
		}, this);

		F3.TYPO3.History.HistoryManager.on('SectionMenu-changed', function(section) {
			F3.TYPO3.UserInterface.UserInterfaceModule.viewport.sectionMenu.setActiveTab(section);
			F3.TYPO3.UserInterface.UserInterfaceModule.fireEvent('activate-menu/main/' + section, undefined);
		});

		F3.TYPO3.History.HistoryManager.on('SectionMenu-added', function(section) {
			F3.TYPO3.UserInterface.UserInterfaceModule.viewport.sectionMenu.setActiveTab(section);
			F3.TYPO3.UserInterface.UserInterfaceModule.fireEvent('activate-menu/main/' + section, undefined);
		});

		F3.TYPO3.History.HistoryModule.on('emptyToken', function() {
			this.setActiveTab('content');
		}, this);
	},

	_getSectionMenuItems: function() {
		var modules = [];
		// TODO unset children properties and use only first level of array
		var config = F3.TYPO3.Core.Registry.get('menu/main');
		Ext.each(config, function(menuItem) {
			modules.push({
				xtype: 'container',
				layout: 'vbox',
				layoutConfig: {
					align: 'stretch'
				},
				itemId: menuItem.itemId,
				title: menuItem.title,
				tabCls: menuItem.tabCls,
				items: [{
					xtype: 'F3.TYPO3.UserInterface.ModuleMenu',
					ref: 'moduleMenu',
					menuId: 'mainMenu',
					itemId: menuItem.itemId,
					basePath: 'menu/main/' + menuItem.key + '/children',
					menuConfig: menuItem.children,
					flex: 0
				}, {
					xtype: 'F3.TYPO3.UserInterface.ContentArea',
					itemId: menuItem.itemId + '-contentArea',
					ref: 'contentArea',

					flex: 1
				}]
			});
		});
		return modules;
	}
});
Ext.reg('F3.TYPO3.UserInterface.SectionMenu', F3.TYPO3.UserInterface.SectionMenu);