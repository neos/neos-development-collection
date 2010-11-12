Ext.ns("F3.TYPO3.Management");

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
 * @class F3.TYPO3.Management.ManagementTree
 *
 * the default management tree view component
 *
 * @namespace F3.TYPO3.Management
 * @extends Ext.tree.TreePanel
 */
F3.TYPO3.Management.ManagementTree = Ext.extend(Ext.tree.TreePanel, {

	/**
	 * Initializer
	 */
	initComponent: function() {
		var config = {
			root: {
				nodeType: 'async',
				text: 'homepage',
				draggable: false,
				autoScroll: true,
				id: '/sites/phoenixdemotypo3org/homepage'
			},
			loader: new F3.TYPO3.Management.Tree.TreeLoader()
		};
		Ext.apply(this, config);

		F3.TYPO3.Management.ManagementTree.superclass.initComponent.call(this);
	}
});

Ext.reg('F3.TYPO3.Management.ManagementTree', F3.TYPO3.Management.ManagementTree);