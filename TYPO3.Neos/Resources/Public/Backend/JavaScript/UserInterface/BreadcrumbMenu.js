/*                                                                        *
 * This script belongs to the TYPO3 project.                              *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License as published by the *
 * Free Software Foundation, either version 3 of the License, or (at your *
 * option) any later version.                                             *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Lesser       *
 * General Public License for more details.                               *
 *                                                                        *
 * You should have received a copy of the GNU Lesser General Public       *
 * License along with the script.                                         *
 * If not, see http://www.gnu.org/licenses/lgpl.html                      *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

Ext.namespace('F3.TYPO3.UserInterface');

/**
 * @namespace F3.TYPO3.UserInterface
 * @extends Ext.tree.TreePanel
 * @author Rens Admiraal <rens@rensnel.nl>
 */
F3.TYPO3.UserInterface.BreadcrumbMenu = function() {
	F3.TYPO3.UserInterface.BreadcrumbMenu.superclass.constructor.apply(this, arguments);
};

Ext.extend(F3.TYPO3.UserInterface.BreadcrumbMenu, Ext.tree.TreePanel, {
	/**
	 * @event F3.TYPO3.UserInterface.BreadcrumbMenu.afterInit
	 * @param {F3.TYPO3.UserInterface.BreadcrumbMenu} a reference to the submenu.
	 * Event triggered after initialization of the menu. Should be used
	 * to add elements to the menu.
	 */

	menuConfig: {},

	basePath: null,

	util: {},

	/**
	 * @cfg menu Menu as defined in {@link F3.TYPO3.Core.Application.MenuRegistry}
	 */
	
	initComponent: function() {

		this.util = F3.TYPO3.UserInterface.BreadcrumbMenu.Util;

		var rootNodeConfig = {
			expanded: true,
			leaf: false,
			text: 'Tree Root',
			children: this._getMenuItems()
		};

		var config = {
			cls: 'F3-TYPO3-UserInterface-BreadcrumbMenu',
			loader: new F3.TYPO3.UserInterface.BreadcrumbMenu.Loader(),
			root: new F3.TYPO3.UserInterface.BreadcrumbMenu.AsyncNode(rootNodeConfig),
			singleExpand: 1,
			animate: true,
			enableDD: false,
			containerScroll: true,
			border: false,
			rootVisible: false
		};
		Ext.apply(this, config);

		if(!this.eventModel){
            this.eventModel = new F3.TYPO3.UserInterface.BreadcrumbMenu.EventModel(this);
        }

		F3.TYPO3.UserInterface.BreadcrumbMenu.superclass.initComponent.call(this);

		this.on('afterrender', function(menu) {
			menu.getRootNode().expand();
			menu.items.each(function(menuItem, i) {
				menuItem.addListener('afterrender',	function() {
					var task = new Ext.util.DelayedTask(function () {
						this.el.fadeIn({
							duration: .2
						});
					}, this);
					task.delay(200 * i);
				});
			}, this);
		});
	},

	/**
	 * @private
	 */
	_getMenuItems: function() {
		var menu = F3.TYPO3.Utils.clone(this.menuConfig);
		return this.util.convertMenuConfig(menu, 1, '', 'menu/main/content', {sectionId:'',menuId:'mainMenu',menuPath:''});
	},

	deactivateNodesFrameNode: function(node) {
		var scope = this;

		if (node.childNodes && node.childNodes.length > 0) {
			Ext.each(node.childNodes, function() {
				F3.TYPO3.Core.Application.fireEvent('F3.TYPO3.UserInterface.BreadcrumbMenu.deactivateNode', this);
				this.active = false;
				scope.deactivateNodesFrameNode(this);
			});
		}
	}
});

Ext.reg('F3.TYPO3.UserInterface.BreadcrumbMenu', F3.TYPO3.UserInterface.BreadcrumbMenu);