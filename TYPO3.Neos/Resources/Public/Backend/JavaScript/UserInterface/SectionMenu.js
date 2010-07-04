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

		this.on('tabchange', function(tabPanel, tab) {
			F3.TYPO3.History.HistoryManager.set('SectionMenu', tab.itemId);
			F3.TYPO3.Application.fireEvent('F3.TYPO3.UserInterface.SectionMenu.' + tab.itemId + '.activated', tab.itemId);
		});

		F3.TYPO3.History.HistoryManager.on('SectionMenu-changed', function(section) {
			F3.TYPO3.UserInterface.viewport.sectionMenu.setActiveTab(section);
			F3.TYPO3.Application.fireEvent('F3.TYPO3.UserInterface.SectionMenu.' + section + '.activated', section);
		});

		F3.TYPO3.History.HistoryManager.on('SectionMenu-added', function(section) {
			F3.TYPO3.UserInterface.viewport.sectionMenu.setActiveTab(section);
			F3.TYPO3.Application.fireEvent('F3.TYPO3.UserInterface.SectionMenu.' + section + '.activated', section);
		});

		F3.TYPO3.Application.on('F3.TYPO3.History.emptyToken', function() {
			F3.TYPO3.UserInterface.viewport.sectionMenu.setActiveTab('content');
		});
	},

	getBubbleTarget: function() {
		return F3.TYPO3.Application.MenuRegistry;
	},

	_getSectionMenuItems: function() {
		var modules = [];
		// TODO unset children properties and use only first level of array
		Ext.each(F3.TYPO3.Application.MenuRegistry.items.mainMenu, function(menuItem) {
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