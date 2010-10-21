Ext.ns("F3.TYPO3.Management");

/**
 * @class F3.TYPO3.Management.ManagementTree
 * @namespace F3.TYPO3.Management
 * @extends Ext.tree.TreePanel
 * @author Christian Müller <christian@kitsunet.de>
 *
 * The Management Tree component
 */
F3.TYPO3.Management.ManagementTree = Ext.extend(Ext.tree.TreePanel, {

	initComponent: function(){
		var config = {
			root: {
				nodeType: 'async',
				text: 'default Tree',
				draggable: false,
				autoScroll: true,
				id: '/sites/phoenix.demo.typo3.org/homepage'
			},
			loader: new F3.TYPO3.Management.Tree.TreeLoader()
		};
		Ext.apply(this, config);

		F3.TYPO3.Management.ManagementTree.superclass.initComponent.call(this);
	}

});

Ext.reg('F3.TYPO3.Management.ManagementTree', F3.TYPO3.Management.ManagementTree);