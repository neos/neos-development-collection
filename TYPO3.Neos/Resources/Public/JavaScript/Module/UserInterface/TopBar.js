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
 * @class TYPO3.TYPO3.Module.UserInterface.TopBar
 *
 * Top bar for the user interface
 *
 * @namespace TYPO3.TYPO3.Module.UserInterface
 * @extends Ext.Panel
 */
TYPO3.TYPO3.Module.UserInterface.TopBar = Ext.extend(Ext.Panel, {
	height: 62,

	initComponent: function() {
		var config = {
			layout: 'vbox',
			layoutConfig: {
				padding: '5px',
				align: 'stretch'
			},
			border: false,
			bodyCssClass: 'TYPO3-TYPO3-UserInterface-TopBar-Wallpaper',
			items: [{
				height: 32,
				xtype: 'container',
				layout: 'hbox',
				items: [{
						xtype: 'box',
						width: 150,
						height: 32,
						flex: 0
					}, {
						xtype: 'box',
						width: 32,
						flex: 0
					}, {
						xtype: 'box',
						width: 230,
						height: 32,
						flex: 0
					}, {
						xtype: 'box',
						flex: 1
					}, {
						xtype: 'box',
						id: 'TYPO3-TYPO3-TopBar-Logo',
						height: 34,
						width: 113,
						flex: 2
					}]
				}, {
					xtype: 'container',
					layout: 'hbox',
					items: [{
						xtype: 'box',
						height: 12,
						flex: 1
					}, {
						xtype: 'panel',
						cls: 'TYPO3-TYPO3-Version',
						width: 107,
						height: 12,
						html: 'Version ' + TYPO3.TYPO3.Configuration.Application.version,
						flex: 0
					}]
				}]
		};
		Ext.apply(this, config);
		TYPO3.TYPO3.Module.UserInterface.TopBar.superclass.initComponent.call(this);
	}
});
Ext.reg('TYPO3.TYPO3.Module.UserInterface.TopBar', TYPO3.TYPO3.Module.UserInterface.TopBar);