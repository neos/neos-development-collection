Ext.ns('TYPO3.TYPO3.Components.Module');

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
 * @class TYPO3.TYPO3.Components.Module.Dialog
 *
 * A module dialog
 *
 * @namespace TYPO3.TYPO3.Module.UserInterface
 * @extends Ext.Panel
 */
TYPO3.TYPO3.Components.Module.Dialog = Ext.extend(Ext.Panel, {
	bodyStyle: 'background: #656565',

	/**
	 * The parent module menu, which is automatically set before the ModuleDialog
	 * is displayed.
	 *
	 * @var {TYPO3.TYPO3.Module.UserInterface.ModuleMenu}
	 */
	moduleMenu: null,

	initComponent: function() {
		var config = {
			border: false,
			flex: 0,
			cls: 'TYPO3-TYPO3-UserInterface-ModuleDialog'
		};
		Ext.apply(this, config);
		TYPO3.TYPO3.Components.Module.Dialog.superclass.initComponent.call(this);
	},

	/**
	 * Callback which is executed when the OK button is pressed.
	 * Override to implement custom behavior!
	 *
	 * @return {void}
	 */
	onOk: function() {
		this.moduleMenu.removeModuleDialog();
	},

	/**
	 * Callback which is executed after the cancel button is pressed.
	 * The default behavior just removes the module dialog.
	 *
	 * @return {void}
	 */
	onCancel: function() {
		this.moduleMenu.removeModuleDialog();
	}
});
Ext.reg('TYPO3.TYPO3.Components.Module.Dialog', TYPO3.TYPO3.Components.Module.Dialog);