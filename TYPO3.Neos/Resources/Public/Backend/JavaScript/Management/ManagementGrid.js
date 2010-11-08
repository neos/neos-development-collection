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
 * @class F3.TYPO3.Management.ManagementGrid
 *
 * default grid component for the management view
 *
 * @namespace F3.TYPO3.Management
 * @extends Ext.grid.EditorGridPanel
 */
F3.TYPO3.Management.ManagementGrid = Ext.extend(Ext.grid.EditorGridPanel, {

	/**
	 * Initializer
	 */
	initComponent: function() {
		var config = {
			store: new Ext.data.ArrayStore({
				autoDestroy: true,
				storeId: 'F3.TYPO3.Management.ManagementStore',
				idIndex: 0,
				fields: [
					'field',
					'value'
				]
			}),
			colModel: new Ext.grid.ColumnModel({
				defaults: {
					width: 120,
					sortable: false
				},
				columns: [
					{header: 'Field', dataIndex: 'field'},
					{header: 'Value', dataIndex: 'value'}
				]
			})
		};
		Ext.apply(this, config);

		F3.TYPO3.Management.ManagementGrid.superclass.initComponent.call(this);
	}
});

Ext.reg('F3.TYPO3.Management.ManagementGrid', F3.TYPO3.Management.ManagementGrid);