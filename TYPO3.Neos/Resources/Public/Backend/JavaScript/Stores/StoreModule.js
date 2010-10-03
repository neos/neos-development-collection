Ext.ns("F3.TYPO3.Stores");

F3.TYPO3.Stores.StoreModule = F3.TYPO3.Core.Application.createModule('F3.TYPO3.Stores.StoreModule', {
	/**
	 * @return {void}
	 */
	initialize: function(application) {
		application.on('afterBootstrap', this._initStores, this);
	},

	/**
	 * Initialize stores
	 */
	_initStores: function() {

	}
});