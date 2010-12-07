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
 * @extends Ext.grid.GridPanel
 */
F3.TYPO3.Management.ManagementGrid = Ext.extend(Ext.grid.GridPanel, {

	/**
	 * Initializer
	 */
	initComponent: function() {
		var directFn = function(callback) {
				var context = {
					'__context': {
						workspaceName: F3.TYPO3.Configuration.Application.workspaceName,
						nodePath: nodePath
					}
				};
				F3.TYPO3_Service_ExtDirect_V1_Controller_NodeController.getChildNodes(context, '!TYPO3:Page', callback);
			};
		directFn.directCfg = {
			method: {
				len: 0
			}
		};
		var iconColumn = {
				width: 24,
				renderer: function(contentType) {
					return '<span class="F3-TYPO3-Management-ManagementGrid-Icon F3-TYPO3-Icon-ContentType-' + contentType.replace(':', '_') + '"></span>';
				},
				dataIndex: '__contentType'
			},
			nodePath = this.nodePath,
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
		F3.TYPO3.Management.ManagementGrid.superclass.initComponent.call(this);

		this.on('render', function() {
			this.body.mask('Loading...');
		});

		this.store.on('load', function() {
			this.body.unmask();
		}, this);

			// Reconfigure column model with server side fields
		this.store.on('metachange', function(store, meta) {
			var columns = [iconColumn];
			Ext.each(meta.fields, function(fieldName) {
				if (!fieldName.match(/^__/)) {
					columns.push({header: fieldName, dataIndex: fieldName});
				}
			});
			this.getColumnModel().setConfig(columns, false);
		}, this);
	}
});
Ext.reg('F3.TYPO3.Management.ManagementGrid', F3.TYPO3.Management.ManagementGrid);
