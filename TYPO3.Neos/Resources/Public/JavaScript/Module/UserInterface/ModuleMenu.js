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
 * @class TYPO3.TYPO3.Module.UserInterface.ModuleMenu
 *
 * TODO: Description
 *
 * @namespace TYPO3.TYPO3.Module.UserInterface
 * @extends Ext.Panel
 */
TYPO3.TYPO3.Module.UserInterface.ModuleMenu = Ext.extend(Ext.Container, {
	basePath: null,
	viewFilter: {},
	specialMenu: null,

	/**
	 * The instance of the module dialog if it is currently opened.
	 *
	 * @var {TYPO3.TYPO3.Components.Module.Dialog}
	 * @private
	 */
	_moduleDialog: null,

	/**
	 * The instance of the content dialog if it is currently opened.
	 *
	 * @var {TYPO3.TYPO3.Components.Content.Dialog}
	 * @private
	 */
	_contentDialog: null,

	initComponent: function() {
		var config = {
			cls: 'TYPO3-TYPO3-UserInterface-ModuleMenu',
			autoHeight: true,
			bodyCfg: {
				cls: 'TYPO3-TYPO3-Overflow-Visible'
			},
			bwrapCfg: {
				cls: 'TYPO3-TYPO3-Overflow-Visible'
			},
			layoutConfig: {
				align: 'stretch'
			},
			items: [this.viewFilter, {
				xtype: 'container',
				layout: 'hbox',
				items: [{
					xtype: 'TYPO3.TYPO3.Components.BreadcrumbMenuComponent',
					itemId: this.itemId,
					ref: '../breadcrumbMenu',
					basePath: this.basePath,
					flex: 1,
					listeners: {
						activate: function(currentlyClickedMenuPath, breadcrumbMenu) {
							TYPO3.TYPO3.Module.UserInterfaceModule.fireEvent('activate-' + currentlyClickedMenuPath, breadcrumbMenu);
						},
						deactivate: function(currentlyClickedMenuPath, breadcrumbMenu) {
							TYPO3.TYPO3.Module.UserInterfaceModule.fireEvent('deactivate-' + currentlyClickedMenuPath, breadcrumbMenu);
						}
					}
				}, this.specialMenu],
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
		TYPO3.TYPO3.Module.UserInterface.ModuleMenu.superclass.initComponent.call(this);
	},

	/**
	 * Show a module dialog inside this module menu.
	 * The xtype in the config should extend TYPO3.TYPO3.Components.Module.Dialog.
	 *
	 * Normally, this method does not need to be called explicitely. Instead,
	 * you will usually call TYPO3.TYPO3.Module.UserInterfaceModule#moduleDialogOn()
	 *
	 * @param {Object} config The module dialog component config
	 * @param {Object} contentDialogConfig Configuration for the content dialog
	 * @return {TYPO3.TYPO3.Components.Module.Dialog} the ModuleDialog instance
	 */
	showModuleDialog: function(config, contentDialogConfig) {
		var sectionMenuTab = this.findParentByType('TYPO3.TYPO3.Components.Module.Container');

		this._moduleDialog = this.moduleDialogContainer.add(config);
		this._moduleDialog.moduleMenu = this;

		TYPO3.TYPO3.Module.UserInterfaceModule.viewport.doLayout();
		sectionMenuTab.doLayout();

		if (contentDialogConfig) {
			this._contentDialog = Ext.ComponentMgr.create(contentDialogConfig);
			this.add(this._contentDialog);
			this._contentDialog.on('_okButtonClick', this._moduleDialog.onOk, this._moduleDialog);
			this._contentDialog.on('_cancelButtonClick', this._moduleDialog.onCancel, this._moduleDialog);
		}

		return this._moduleDialog;
	},

	/**
	 * Remove the existing ModuleDialog AND ContentDialog from the module menu.
	 *
	 * @return {void}
	 */
	removeModuleDialog: function() {
		var sectionMenuTab = this.findParentByType('TYPO3.TYPO3.Components.Module.Container');
		if (this._moduleDialog) {
			this.moduleDialogContainer.removeAll();
		}
		if (this._contentDialog) {
			this._contentDialog.destroy();
			this._contentDialog = null;
		}

		TYPO3.TYPO3.Module.UserInterfaceModule.viewport.doLayout();
		sectionMenuTab.doLayout();

		this.addedModuleHeight = 0;
	},

	/**
	 * @return {TYPO3.TYPO3.Components.Module.Dialog}
	 */
	getModuleDialog: function() {
		return this._moduleDialog;
	},

	/**
	 * @return {TYPO3.TYPO3.Components.Module.Dialog}
	 */
	getContentDialog: function() {
		return this._contentDialog;
	}
});
Ext.reg('TYPO3.TYPO3.Module.UserInterface.ModuleMenu', TYPO3.TYPO3.Module.UserInterface.ModuleMenu);