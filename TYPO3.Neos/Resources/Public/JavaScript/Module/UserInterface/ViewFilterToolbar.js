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
 * @class TYPO3.TYPO3.Module.UserInterface.ViewFilterToolbar
 *
 * context menu
 *
 * @namespace TYPO3.TYPO3.Module.UserInterface
 * @extends Ext.Toolbar
 */
TYPO3.TYPO3.Module.UserInterface.ViewFilterToolbar = Ext.extend(Ext.Toolbar, {

	/**
	 * Initializer
	 */
	initComponent: function() {
		var config = {
			height: 25,
			border: 0,
			padding: 0,
			flex: 0,
			cls: 'TYPO3-TYPO3-UserInterface-ViewFilterToolbar',
			items: this._getToolbarItems()
		};
		Ext.apply(this, config);
		TYPO3.TYPO3.Module.UserInterface.ViewFilterToolbar.superclass.initComponent.call(this);
		this.on('render', this._afterRender, this);
	},

	/**
	 * @private
	 * @return void
	 */
	_afterRender: function(){
		this.getEl().on('mouseover', this._onMouseOver, this);
		this.getEl().on('mouseout', this._onMouseOut, this);
	},

	/**
	 * @private
	 * @return void
	 */
	_getToolbarItems: function() {
		var items = [];
		var config = TYPO3.TYPO3.Core.Registry.get('menu/viewFilterToolbar');
		Ext.each(config, function(component) {
			var item = {};
			Ext.apply(item, component, {});
			items.push(item);
		});
		return items;
	},

	/**
	 * @private
	 * @return void
	 */
	_onMouseOver: function(event) {
		this.removeClass('TYPO3-TYPO3-disabled');
	},

	/**
	 * @private
	 * @return void
	 */
	_onMouseOut: function(event) {
		this.addClass('TYPO3-TYPO3-disabled');
	}

});
Ext.reg('TYPO3.TYPO3.Module.UserInterface.ViewFilterToolbar', TYPO3.TYPO3.Module.UserInterface.ViewFilterToolbar);