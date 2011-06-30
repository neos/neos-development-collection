Ext.ns('TYPO3.TYPO3.Module.UserInterface');

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
 * @class TYPO3.TYPO3.Module.UserInterface.SectionMenu
 *
 * the section menu
 *
 * @namespace TYPO3.TYPO3.Module.UserInterface
 * @extends Ext.TabPanel
 */
TYPO3.TYPO3.Module.UserInterface.SectionMenu = Ext.extend(Ext.TabPanel, {

	/**
	 * @return {void}
	 */
	initComponent: function() {
		var config = {
			border: false,
			cls: 'TYPO3-TYPO3-UserInterface-SectionMenu',
			items: this._getSectionMenuItems(),
			itemTpl: new Ext.XTemplate('<li class="{cls}" id="{id}"><a class="x-tab-strip-close"></a>',
                 '<a class="x-tab-right" href="#"><em class="x-tab-left">',
                 '<span class="x-tab-strip-inner"><span class="x-tab-strip-text {iconCls}">',
				 // Begin Modification, added iconWrapper
			     '<span class="iconWrapper"></span>',
				 // End Modification
				 '{text}</span></span>',
                 '</em></a></li>')
		};
		Ext.apply(this, config);
		TYPO3.TYPO3.Module.UserInterface.SectionMenu.superclass.initComponent.call(this);

		this.on('tabchange', function(sectionMenu, tab) {
			TYPO3.TYPO3.Core.HistoryManager.set('SectionMenu', tab.itemId);
			TYPO3.TYPO3.Module.UserInterfaceModule.fireEvent('activate-menu/main/' + tab.itemId, tab);

		}, this);

		TYPO3.TYPO3.Core.HistoryManager.on('SectionMenu-changed', function(section) {
			TYPO3.TYPO3.Module.UserInterfaceModule.viewport.sectionMenu.setActiveTab(section);
			TYPO3.TYPO3.Module.UserInterfaceModule.fireEvent('activate-menu/main/' + section, undefined);
		});

		TYPO3.TYPO3.Core.HistoryManager.on('SectionMenu-added', function(section) {
			TYPO3.TYPO3.Module.UserInterfaceModule.viewport.sectionMenu.setActiveTab(section);
			TYPO3.TYPO3.Module.UserInterfaceModule.fireEvent('activate-menu/main/' + section, undefined);
		});

		TYPO3.TYPO3.Core.HistoryManager.on('emptyToken', function() {
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
		var config = TYPO3.TYPO3.Core.Registry.get('menu/main');
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
			xtype: 'TYPO3.TYPO3.Components.Module.Container',
			layout: 'vbox',
			layoutConfig: {
				align: 'stretch'
			},
			itemId: menuItem.itemId,
			title: menuItem.title,
			iconCls: menuItem.iconCls,
			tabCls: menuItem.tabCls,
			listeners: menuItem.listeners,
			items: [{
				xtype: 'TYPO3.TYPO3.Module.UserInterface.ModuleMenu',
				ref: 'moduleMenu',
				menuId: 'mainMenu',
				itemId: menuItem.itemId,
				basePath: 'menu/main/' + menuItem.key,
				menuConfig: menuItem.children,
				viewFilter: viewFilter,
				specialMenu: specialMenu,
				flex: 0
			}, {
				xtype: 'TYPO3.TYPO3.Components.Content.Area',
				itemId: menuItem.itemId + '-contentArea',
				ref: 'contentArea',
				flex: 1
			}]
		};
	}
});
Ext.reg('TYPO3.TYPO3.Module.UserInterface.SectionMenu', TYPO3.TYPO3.Module.UserInterface.SectionMenu);