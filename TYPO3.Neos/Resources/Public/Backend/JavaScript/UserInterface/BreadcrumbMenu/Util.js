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

Ext.namespace('F3.TYPO3.UserInterface.BreadcrumbMenu');

/**
 * @class F3.TYPO3.UserInterface.BreadcrumbMenu.AnimationHandler
 * @namespace F3.TYPO3.UserInterface.BreadcrumbMenu
 * @author Rens Admiraal <rens@rensnel.nl>
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 * @demo http://phoenix.demo.typo3.org/
 */

F3.TYPO3.UserInterface.BreadcrumbMenu.Util = {

	// private
	convertMenuConfig: function(menu, level, menuPath, path, scope) {
		var itemStack = [];

		Ext.each(menu, function(menuItem) {

			menuItem.menuId = scope.menuId;

			if (level == 0) {
				menuItem.path = path;
				menuItem.menuPath = '';
			} else {
				menuItem.path = path + '/children/' + menuItem.key;
				menuItem.menuPath = menuPath + '/' + menuItem.key;
				menuItem.menuPath = menuItem.menuPath.replace(/(^\/|\/$)/, '');
			}

			if (menuItem.children && menuItem.children.length > 0) {
				menuItem.children = F3.TYPO3.UserInterface.BreadcrumbMenu.Util.convertMenuConfig(menuItem.children, level + 1, menuItem.menuPath, menuItem.path, scope);
			}

			itemStack.push(menuItem);
		}, this);

		return itemStack;
	},

	addItemPaths: function(items, basePath, path) {
		Ext.each(items, function (menuItem) {
			var itemPath = path.concat([menuItem.key]);

			menuItem.path = basePath + '/' + itemPath.join('/children/');
		});
	}
};
