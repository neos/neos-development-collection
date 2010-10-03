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
 * @class F3.TYPO3.UserInterface.BreadcrumbMenu.Loader
 *
 * @namespace F3.TYPO3.UserInterface.BreadcrumbMenu
 * @extends Ext.tree.TreeLoader
 * @author Rens Admiraal <rens@rensnel.nl>
 */
F3.TYPO3.UserInterface.BreadcrumbMenu.Loader = Ext.extend(Ext.tree.TreeLoader, {

	/**
	 * @param {Object} attr
	 * @return {Object}
	 * @public
	 */
	createNode : function(attr) {
		if (!attr.uiProvider) {
			attr.uiProvider = F3.TYPO3.UserInterface.BreadcrumbMenu.NodeUI;
		}
		attr.singleClickExpand = true;
        return Ext.tree.TreeLoader.prototype.createNode.call(this, attr);
    }
});

Ext.reg('F3.TYPO3.UserInterface.BreadcrumbMenu.Loader', F3.TYPO3.UserInterface.BreadcrumbMenu.Loader);