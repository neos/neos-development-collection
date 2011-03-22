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
	specialMenu: null,

	/**
	 * The instance of the module dialog if it is currently opened.
	 *
	 * @var {F3.TYPO3.UserInterface.ModuleDialog}
	 * @private
	 */
	_moduleDialog: null,

	/**
	 * The instance of the content dialog if it is currently opened.
	 *
	 * @var {F3.TYPO3.UserInterface.ContentDialog}
	 * @private
	 */
	_contentDialog: null,

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
				xtype: 'container',
				layout: 'hbox',
				items: [{
					xtype: 'F3.TYPO3.UserInterface.BreadcrumbMenuComponent',
					itemId: this.itemId,
					ref: '../breadcrumbMenu',
					basePath: this.basePath,
					flex: 1
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
		F3.TYPO3.UserInterface.ModuleMenu.superclass.initComponent.call(this);
	},

	/**
	 * Show a module dialog inside this module menu.
	 * The xtype in the config should extend F3.TYPO3.UserInterface.ModuleDialog.
	 *
	 * Normally, this method does not need to be called explicitely. Instead,
	 * you will usually call F3.TYPO3.UserInterface.UserInterfaceModule#moduleDialogOn()
	 *
	 * @param {Object} config The module dialog component config
	 * @param {Object} contentDialogConfig Configuration for the content dialog
	 * @return {F3.TYPO3.UserInterface.ModuleDialog} the ModuleDialog instance
	 */
	showModuleDialog: function(config, contentDialogConfig) {
		var sectionMenuTab = this.findParentByType('F3.TYPO3.Components.ModuleContainer');

		this._moduleDialog = this.moduleDialogContainer.add(config);
		this._moduleDialog.moduleMenu = this;

		F3.TYPO3.UserInterface.UserInterfaceModule.viewport.doLayout();
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
		var sectionMenuTab = this.findParentByType('F3.TYPO3.Components.ModuleContainer');
		if (this._moduleDialog) {
			this.moduleDialogContainer.removeAll();
		}
		if (this._contentDialog) {
			this._contentDialog.destroy();
			this._contentDialog = null;
		}

		F3.TYPO3.UserInterface.UserInterfaceModule.viewport.doLayout();
		sectionMenuTab.doLayout();

		this.addedModuleHeight = 0;
	},

	/**
	 *
	 */
	getModuleDialog: function() {
		return this._moduleDialog;
	},

	/**
	 * 
	 */
	getContentDialog: function() {
		return this._contentDialog;
	}
});
Ext.reg('F3.TYPO3.UserInterface.ModuleMenu', F3.TYPO3.UserInterface.ModuleMenu);
