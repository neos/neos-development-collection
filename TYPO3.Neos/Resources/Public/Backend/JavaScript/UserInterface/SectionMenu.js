Ext.ns("F3.TYPO3.UserInterface");

/*                                                                        *
 * This script belongs to the FLOW3 package "TYPO3".                      *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License as published by the Free   *
 * Software Foundation, either version 3 of the License, or (at your      *
 * option) any later version.                                             *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General      *
 * Public License for more details.                                       *
 *                                                                        *
 * You should have received a copy of the GNU General Public License      *
 * along with the script.                                                 *
 * If not, see http://www.gnu.org/licenses/gpl.html                       *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * @class F3.TYPO3.UserInterface.SectionMenu
 *
 * the section menu
 *
 * @namespace F3.TYPO3.UserInterface
 * @extends Ext.TabPanel
 */
F3.TYPO3.UserInterface.SectionMenu = Ext.extend(Ext.TabPanel, {

	/**
	 * @return {void}
	 */
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

	/**
	 * @private
	 * @return {Array} an array of section menu items, fetched from the registry.
	 */
	_getSectionMenuItems: function() {
		var modules = [];
		// TODO unset children properties and use only first level of array
		var config = F3.TYPO3.Core.Registry.get('menu/main');
		Ext.each(config, function(menuItem) {
			modules.push(this._getTabComponentForMenuItem(menuItem));
		}, this);
		return modules;
	},

	/**
	 * @private
	 * @return {Object}
	 */
	_getTabComponentForMenuItem: function(menuItem) {
		var viewFilter = menuItem.viewFilter ? menuItem.viewFilter : {xtype: 'container', 'height': 0},
			specialMenu = menuItem.specialMenu ? menuItem.specialMenu : {xtype: 'box', 'width': 0};
		return {
			xtype: 'F3.TYPO3.Components.ModuleContainer',
			layout: 'vbox',
			layoutConfig: {
				align: 'stretch'
			},
			itemId: menuItem.itemId,
			title: menuItem.title,
			tabCls: menuItem.tabCls,
			listeners: menuItem.listeners,
			items: [{
				xtype: 'F3.TYPO3.UserInterface.ModuleMenu',
				ref: 'moduleMenu',
				menuId: 'mainMenu',
				itemId: menuItem.itemId,
				basePath: 'menu/main/' + menuItem.key,
				menuConfig: menuItem.children,
				viewFilter: viewFilter,
				specialMenu: specialMenu,
				flex: 0
			}, {
				xtype: 'F3.TYPO3.UserInterface.ContentArea',
				itemId: menuItem.itemId + '-contentArea',
				ref: 'contentArea',
				flex: 1
			}]
		};
	}
});
Ext.reg('F3.TYPO3.UserInterface.SectionMenu', F3.TYPO3.UserInterface.SectionMenu);