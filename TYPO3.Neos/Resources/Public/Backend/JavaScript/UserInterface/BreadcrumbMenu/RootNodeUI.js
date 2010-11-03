Ext.namespace('F3.TYPO3.UserInterface.BreadcrumbMenu');

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
 * @class F3.TYPO3.UserInterface.BreadcrumbMenu.RootNodeUI
 *
 * Provides the UI implementation of the root node of the menu
 *
 * This class extends ExtJS and therefore not all private methods are starting with a _
 *
 * @namespace F3.TYPO3.UserInterface.BreadcrumbMenu
 * @extends Ext.tree.RootTreeNodeUI
 */
F3.TYPO3.UserInterface.BreadcrumbMenu.RootNodeUI = Ext.extend(Ext.tree.RootTreeNodeUI, {

	/**
	 * @return {void}
	 * @private
	 */
	render : function(){
		if(!this.rendered) {
			var targetNode = this.node.ownerTree.innerCt.dom;
			this.node.expanded = true;
			targetNode.innerHTML = '<div class="F3-TYPO3-UserInterface-BreadcrumbMenu-root-node"></div>';
			this.wrap = this.ctNode = targetNode.firstChild;
		}
	},
	collapse : Ext.emptyFn,
	expand : Ext.emptyFn
});

Ext.reg('F3.TYPO3.UserInterface.BreadcrumbMenu.RootNodeUI', F3.TYPO3.UserInterface.BreadcrumbMenu.RootNodeUI);