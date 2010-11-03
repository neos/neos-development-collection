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
 * @class F3.TYPO3.UserInterface.Layout
 *
 * The outermost user interface component.
 *
 * @namespace F3.TYPO3.UserInterface
 * @extends Ext.Viewport
 */
F3.TYPO3.UserInterface.Layout = Ext.extend(Ext.Viewport, {
	initComponent: function() {
		var config = {
			layout: 'vbox',
			layoutConfig: {
				align: 'stretch'
			},
			items: [{
				xtype: 'F3.TYPO3.UserInterface.TopBar',
				ref: 'topBar',
				flex: 0
			}, {
				xtype: 'F3.TYPO3.UserInterface.SectionMenu',
				ref: 'sectionMenu',
				flex: 1
			}]
		};
		Ext.apply(this, config);
		F3.TYPO3.UserInterface.Layout.superclass.initComponent.call(this);
	}
});