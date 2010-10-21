Ext.ns("F3.TYPO3.Management");

/**
 * @class F3.TYPO3.Management.ManagementView
 * @namespace F3.TYPO3.Management
 * @extends Ext.Container
 * @author Christian Müller <christian@kitsunet.de>
 *
 * The main Management View
 */
F3.TYPO3.Management.ManagementView = Ext.extend(Ext.Container, {

	layout: 'border',

	initComponent: function(){

		var config = {
			items: [{
				title: 'TYPOtree',
				region:'west',
				margins: '0 0 0 0',
				width: 200,
				split: true,
				collapsible: false,
				layout: 'fit',
				xtype: 'F3.TYPO3.Management.ManagementTree'
			},
			{
				region: 'center',
				layout: 'fit',
				margins: '0 5 0 5',
				xtype: 'F3.TYPO3.Management.ManagementGrid'
			}]
		};
		Ext.apply(this, config);

		F3.TYPO3.Management.ManagementView.superclass.initComponent.call(this);
	}

});

Ext.reg('F3.TYPO3.Management.ManagementView', F3.TYPO3.Management.ManagementView);