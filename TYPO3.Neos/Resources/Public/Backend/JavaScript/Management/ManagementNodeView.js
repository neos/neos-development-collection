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
 * @class F3.TYPO3.Management.ManagementNodeView
 *
 * container for grid component
 *
 * @namespace F3.TYPO3.Management
 * @extends Ext.Container
 */
F3.TYPO3.Management.ManagementNodeView = Ext.extend(Ext.Panel, {

	/**
	 * Initializer
	 */
	initComponent: function() {
		var config = {
			border: false,
			layout: 'fit',
			tbar: [{
				text: 'Content',
				iconCls: 'F3-TYPO3-Management-Content'
			}]
		};
		Ext.apply(this, config);
		F3.TYPO3.Management.ManagementNodeView.superclass.initComponent.call(this);

		F3.TYPO3.Management.ManagementModule.on('F3.TYPO3.Management.nodeSelected', function(node) {
			this.removeAll(true);
			this.add({
				xtype: 'F3.TYPO3.Management.ManagementGrid',
				border: true,
				padding: '6px',
				cls: 'F3-TYPO3-Management-Grid-Element',
				nodePath: node.attributes.id
			});
			this.doLayout();
		}, this);
	}
});
Ext.reg('F3.TYPO3.Management.ManagementNodeView', F3.TYPO3.Management.ManagementNodeView);
