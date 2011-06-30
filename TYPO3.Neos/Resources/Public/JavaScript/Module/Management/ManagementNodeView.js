Ext.ns('TYPO3.TYPO3.Module.Management');

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
 * @class TYPO3.TYPO3.Module.Management.ManagementNodeView
 *
 * container for grid component
 *
 * @namespace TYPO3.TYPO3.Module.Management
 * @extends Ext.Container
 */
TYPO3.TYPO3.Module.Management.ManagementNodeView = Ext.extend(Ext.Panel, {

	/**
	 * Initializer
	 */
	initComponent: function() {
		var config = {
			border: false,
			layout: 'fit',
			tbar: [{
				text: TYPO3.TYPO3.Core.I18n.get('TYPO3.TYPO3', 'content'),
				iconCls: 'TYPO3-TYPO3-Management-Content'
			}]
		};
		Ext.apply(this, config);
		TYPO3.TYPO3.Module.Management.ManagementNodeView.superclass.initComponent.call(this);

		TYPO3.TYPO3.Module.ManagementModule.on('TYPO3.TYPO3.Module.Management.nodeSelected', function(node) {
			this.removeAll(true);
			this.add({
				xtype: 'TYPO3.TYPO3.Module.Management.ManagementGrid',
				border: true,
				padding: '6px',
				cls: 'TYPO3-TYPO3-Management-Grid-Element',
				contextNodePath: node.attributes.id
			});
			this.doLayout();
		}, this);
	}
});
Ext.reg('TYPO3.TYPO3.Module.Management.ManagementNodeView', TYPO3.TYPO3.Module.Management.ManagementNodeView);
