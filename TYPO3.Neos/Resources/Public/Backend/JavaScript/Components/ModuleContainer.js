Ext.ns("F3.TYPO3.Components");

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
 * @class F3.TYPO3.Components.ModuleContainer
 *
 * default component to include modules into the backend
 *
 * @namespace F3.TYPO3.Components
 * @extends Ext.Container
 */
F3.TYPO3.Components.ModuleContainer = Ext.extend(Ext.Container, {

	initComponent: function() {
		var config = {
			cls: 'F3-TYPO3-Components-ModuleContainer',
			layout: 'vbox',
			layoutConfig: {
				align: 'stretch'
			}
		};
		Ext.apply(this, config);
		F3.TYPO3.Components.ModuleContainer.superclass.initComponent.call(this);
	}
});
Ext.reg('F3.TYPO3.Components.ModuleContainer', F3.TYPO3.Components.ModuleContainer);