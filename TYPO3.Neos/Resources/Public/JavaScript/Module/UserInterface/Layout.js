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
 * @class TYPO3.TYPO3.Module.UserInterface.Layout
 *
 * The outermost user interface component.
 *
 * @namespace TYPO3.TYPO3.Module.UserInterface
 * @extends Ext.Viewport
 */
TYPO3.TYPO3.Module.UserInterface.Layout = Ext.extend(Ext.Viewport, {
	initComponent: function() {
		var config = {
			layout: 'vbox',
			layoutConfig: {
				align: 'stretch'
			},
			items: [{
				xtype: 'TYPO3.TYPO3.Module.UserInterface.TopBar',
				ref: 'topBar',
				flex: 0
			}, {
				xtype: 'TYPO3.TYPO3.Module.UserInterface.SectionMenu',
				ref: 'sectionMenu',
				flex: 1
			}]
		};
		Ext.apply(this, config);
		TYPO3.TYPO3.Module.UserInterface.Layout.superclass.initComponent.call(this);
		this.on('afterrender', this._addWallpaper, this);
	},

	/**
	 * Add the Wallpaper css class to the outer div of the viewport
	 *
	 * @return {void}
	 * @private
	 */
	_addWallpaper: function() {
		if (Ext.isDefined(this.layout.innerCt)) {
			this.layout.innerCt.addClass('TYPO3-TYPO3-Wallpaper');
		}
	}
});