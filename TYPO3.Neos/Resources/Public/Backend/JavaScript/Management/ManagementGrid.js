Ext.ns("F3.TYPO3.Management");

/**
 * @class F3.TYPO3.Management.ManagementGrid
 * @namespace F3.TYPO3.Management
 * @extends Ext.grid.EditorGridPanel
 * @author Christian Müller <christian@kitsunet.de>
 *
 * The Management Grid component
 */
F3.TYPO3.Management.ManagementGrid = Ext.extend(Ext.grid.EditorGridPanel, {

	initComponent: function(){
		var config = {
			store: new Ext.data.ArrayStore({
			    // store configs
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