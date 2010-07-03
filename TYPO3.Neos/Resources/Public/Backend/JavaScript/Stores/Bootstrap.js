Ext.ns("F3.TYPO3.Stores");

F3.TYPO3.Stores.Bootstrap = Ext.apply(new F3.TYPO3.Application.AbstractBootstrap, {
	initialize: function() {
		F3.TYPO3.Application.on('F3.TYPO3.Application.afterBootstrap', this._initStores, this);
	},

	/**
	 * Initialize stores
	 */
	_initStores: function() {
		F3.TYPO3_Controller_Service_LoginController.show(function(result) {
			F3.TYPO3.Application.fireEvent('F3.TYPO3.Login.updated', result.data);
		});
	}
});
F3.TYPO3.Application.registerBootstrap(F3.TYPO3.Stores.Bootstrap);
