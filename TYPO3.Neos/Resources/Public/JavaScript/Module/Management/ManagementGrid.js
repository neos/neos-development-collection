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
 * @class TYPO3.TYPO3.Module.Management.ManagementGrid
 *
 * default grid component for the management view
 *
 * @namespace TYPO3.TYPO3.Module.Management
 * @extends Ext.grid.GridPanel
 */
TYPO3.TYPO3.Module.Management.ManagementGrid = Ext.extend(Ext.grid.GridPanel, {

	/**
	 * Initializer
	 */
	initComponent: function() {
		var directFn = function(callback) {
				TYPO3.TYPO3_Service_ExtDirect_V1_Controller_NodeController.getChildNodes(contextNodePath, '!TYPO3.TYPO3:Page', 0, callback);
			};
		directFn.directCfg = {
			method: {
				len: 0
			}
		};
		var iconColumn = {
				width: 24,
				renderer: function(contentType) {
					return '<span class="TYPO3-TYPO3-Management-ManagementGrid-Icon TYPO3-TYPO3-Icon-ContentType-' + contentType.replace(':', '_') + '"></span>';
				},
				dataIndex: '__contentType'
			},
			labelColumn = {
				header: 'label',
				dataIndex: '__label'
			},
			contextNodePath = this.contextNodePath,
			config = {
				store: new Ext.data.DirectStore({
					directFn: directFn,
					autoLoad: true,
					autoDestroy: true,
					root: 'data',
					fields: []
				}),
				colModel: new Ext.grid.ColumnModel({
					defaults: {
						width: 200,
						sortable: false
					},
					columns: []
				})
			};
		Ext.apply(this, config);
		TYPO3.TYPO3.Module.Management.ManagementGrid.superclass.initComponent.call(this);

		this.on('render', function() {
			this.body.mask(TYPO3.TYPO3.Core.I18n.get('TYPO3.TYPO3', 'currentlyLoading'));
		});

		this.store.on('load', function() {
			this.body.unmask();
		}, this);

			// Reconfigure column model with server side fields
		this.store.on('metachange', function(store, meta) {
			var columns = [iconColumn, labelColumn];
			Ext.each(meta.fields, function(fieldName) {
				if (!fieldName.match(/^__/)) {
					columns.push({header: fieldName, dataIndex: fieldName});
				}
			});
			this.getColumnModel().setConfig(columns, false);
		}, this);
	}
});
Ext.reg('TYPO3.TYPO3.Module.Management.ManagementGrid', TYPO3.TYPO3.Module.Management.ManagementGrid);
