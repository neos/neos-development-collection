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
 * @class F3.TYPO3.UserInterface.ModuleMenu
 *
 * TODO: Description
 *
 * @namespace F3.TYPO3.UserInterface
 * @extends Ext.Panel
 */
F3.TYPO3.UserInterface.ModuleMenu = Ext.extend(Ext.Container, {
	basePath: null,
	viewFilter: {},

	initComponent: function() {
		var config = {
			cls: 'F3-TYPO3-UserInterface-ModuleMenu',
			autoHeight: true,
			bodyCfg: {
				cls: 'F3-TYPO3-Overflow-Visible'
			},
			bwrapCfg: {
				cls: 'F3-TYPO3-Overflow-Visible'
			},
			layoutConfig: {
				align: 'stretch'
			},
			items: [this.viewFilter, {
				xtype: 'F3.TYPO3.UserInterface.BreadcrumbMenuComponent',
				itemId: this.itemId,
				ref: 'breadcrumbMenu',
				basePath: this.basePath,
				flex: 0
			}, {
				xtype: 'container',
				autoHeight: true,
				layoutConfig: {
					align: 'stretch'
				},
				itemId: 'moduleDialogContainer',
				ref: 'moduleDialogContainer',
				flex: 1
			}]
		};
		Ext.apply(this, config);
		F3.TYPO3.UserInterface.ModuleMenu.superclass.initComponent.call(this);
	},

	/**
	 * Show a module dialog inside this module menu.
	 * The xtype in the config should extend F3.TYPO3.UserInterface.ModuleDialog.
	 *
	 * The module dialog will be automatically removed, if any button in the menu
	 * gets clicked.
	 *
	 * @param {Object} config The module dialog component config
	 * @param {Object} contentDialogConfig Configuration for the content dialog
	 */
	showModuleDialog: function(config, contentDialogConfig) {
		var dialogRemoved = false,
			sectionMenuTab = this.findParentByType('F3.TYPO3.Components.ModuleContainer');
		config = Ext.apply(config, {
			listeners: {
				removed: function() {
					dialogRemoved = true;
				}
			}
		});
		if (config) {
			this.moduleDialog = this.moduleDialogContainer.add(config);
			this.moduleDialog.moduleMenu = this;
		}
		F3.TYPO3.UserInterface.UserInterfaceModule.viewport.doLayout();
		sectionMenuTab.doLayout();

		if (contentDialogConfig) {
			this.contentDialog = Ext.ComponentMgr.create(contentDialogConfig);
			this.contentDialog.moduleDialog = this.moduleDialog;
			this.add(this.contentDialog);
		}

		return this.moduleDialog;
	},

	/**
	 * Remove an existing module dialog AND content dialog from the module menu.
	 *
	 */
	removeModuleDialog: function() {
		var sectionMenuTab = this.findParentByType('F3.TYPO3.Components.ModuleContainer');
		if (this.moduleDialog) {
			this.moduleDialogContainer.removeAll();
		}
		if (this.contentDialog) {
			this.contentDialog.destroy();
			delete this.contentDialog;
		}

		F3.TYPO3.UserInterface.UserInterfaceModule.viewport.doLayout();
		sectionMenuTab.doLayout();

		this.addedModuleHeight = 0;
	}
});
Ext.reg('F3.TYPO3.UserInterface.ModuleMenu', F3.TYPO3.UserInterface.ModuleMenu);