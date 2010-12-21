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

	/**
	 * Cancel button label, if false no cancel button is displayed.
	 *
	 * @cfg {String}
	 */
	cancelButton: false,

	/**
	 * OK button label, if false no OK button is displayed.
	 *
	 * @cfg {String}
	 */
	okButton: false,

	/**
	 * additional info text for buttons, if false nothing is displayed.
	 *
	 * @cfg {String}
	 */
	infoText: false,

	/**
	 * Mode. If "success" (the default), the content dialog has a dark background
	 * and the buttons are on the left. If "warning", the content dialog
	 * has a red background, and the buttons are on the right.
	 *
	 * @cfg {String}
	 */
	mode: 'success',
	moduleDialog: null,

	/**
	 * @event _okButtonClick
	 */

	/**
	 * @event _cancelButtonClick
	 */
	initComponent: function() {
		var config, toolbarConfig, itemsConfig, keyMap;

		itemsConfig = [];
		keyMap = new Ext.KeyMap(document, []);

		toolbarConfig = {
			xtype: 'toolbar',
			items: []
		};
		if (this.cancelButton != false) {
			toolbarConfig.items.push({
				xtype: 'F3.TYPO3.Components.Button',
				cls: 'F3-TYPO3-Components-Button',
				itemId: 'cancelButton',
				text: this.cancelButton,
				scale: 'large',
				handler: this._fireCancelEvent,
				scope: this
			});
			keyMap.on(Ext.EventObject.ESC, this._fireCancelEvent, this);
		}
		if (this.okButton != false) {
			if (toolbarConfig.items.length > 0) {
				toolbarConfig.items.push({xtype: 'tbspacer', width: 20});
			}
			toolbarConfig.items.push({
				xtype: 'F3.TYPO3.Components.Button',
				itemId: 'okButton',
				text: this.okButton,
				scale: 'large',
				handler: this._fireOkEvent,
				scope: this,
				cls: 'F3-TYPO3-Components-Button-type-' + this.mode
			});
			keyMap.on(Ext.EventObject.ENTER, this._fireOkEvent, this);
		}
		if (this.infoText != false) {
			itemsConfig.push({
				xtype: 'panel',
				cls: 'F3-TYPO3-UserInterface-ContentDialog-InfoText',
				border: false,
				html: this.infoText,
				flex: 0
			});
		}

		itemsConfig.push({
			xtype: 'panel',
			cls: 'F3-TYPO3-UserInterface-ContentDialog-Panel',
			border: false,
			tbar: toolbarConfig,
			ref: 'panel'
		});

		config = {
			height: 22,
			style: 'position: relative; z-index: 500; overflow: show',
			items: itemsConfig,
			cls: 'F3-TYPO3-UserInterface-ContentDialog F3-TYPO3-UserInterface-ContentDialog-mode-' + this.mode
		};
		Ext.apply(this, config);
		F3.TYPO3.UserInterface.ContentDialog.superclass.initComponent.call(this);
	},

	/**
	 * fire the cancel event
	 *
	 * @return {void}
	 * @private
	 */
	_fireCancelEvent: function() {
		this.fireEvent('_cancelButtonClick');
	},

	/**
	 * fire the ok event
	 *
	 * @return {void}
	 * @private
	 */
	_fireOkEvent: function() {
		this.fireEvent('_okButtonClick');
	}
});
Ext.reg('F3.TYPO3.UserInterface.ContentDialog', F3.TYPO3.UserInterface.ContentDialog);