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
 * @class F3.TYPO3.UserInterface.ContentDialog
 *
 * Content dialog; which consists of an OK / cancel button.
 *
 * @namespace F3.TYPO3.UserInterface
 * @extends Ext.Container
 */
F3.TYPO3.UserInterface.ContentDialog = Ext.extend(Ext.Container, {
	showCancelButton: true,
	showOkButton: true,
	dialogPosition: 'right',
	moduleDialog: null,
	cls: 'F3-TYPO3-UserInterface-ContentDialog',

	initComponent: function() {
		var config, toolbarConfig;

		toolbarConfig = {
			xtype: 'toolbar',
			items: []
		};
		if (this.showCancelButton) {
			toolbarConfig.items.push({
				xtype: 'F3.TYPO3.Components.Button',
				cls: 'F3-TYPO3-Components-Button-link',
				itemId: 'cancelButton',
				text: 'Cancel',
				scale: 'large',
				handler: this._handleButtonClick,
				scope: this
			});
		}
		if (this.showOkButton) {
			if (toolbarConfig.items.length > 0) {
				toolbarConfig.items.push({xtype: 'tbspacer', width: 20});
			}
			toolbarConfig.items.push({
				xtype: 'F3.TYPO3.Components.Button',
				width: 40,
				itemId: 'okButton',
				text: 'OK',
				scale: 'large',
				handler: this._handleButtonClick,
				scope: this
			});
		}
		config = {
			height: 13,
			style: 'position: relative; z-index: 500; overflow: show',
			items: {
				xtype: 'panel',
				cls: 'F3-TYPO3-UserInterface-ContentDialog-Panel',
				style: 'position: absolute; top: 12px; right: 100px',
				border: false,
				tbar: toolbarConfig,
				ref: 'panel'
			}
		};
		Ext.apply(this, config);
		F3.TYPO3.UserInterface.ContentDialog.superclass.initComponent.call(this);

		this.panel.enableBubble([
			'F3.TYPO3.UserInterface.ContentDialog.buttonClick',
			'F3.TYPO3.UserInterface.ContentDialog.cancelled'
		]);

		this.panel.getBubbleTarget = function() {
			return this.ownerCt.moduleDialog;
		};
	},

	/**
	 * TODO: document
	 *
	 * @param {...} button
	 * @param {...} event
	 * @return {void}
	 * @private
	 */
	_handleButtonClick: function(button, event) {
		// TODO: it seems that these events here are on the wrong objects.
		this.panel.fireEvent('F3.TYPO3.UserInterface.ContentDialog.buttonClick', button);
		if (button.itemId === 'cancelButton') {
			this.panel.fireEvent('F3.TYPO3.UserInterface.ContentDialog.cancelled');
		}
		// TODO: How to handle validation errors?
	}
});
Ext.reg('F3.TYPO3.UserInterface.ContentDialog', F3.TYPO3.UserInterface.ContentDialog);