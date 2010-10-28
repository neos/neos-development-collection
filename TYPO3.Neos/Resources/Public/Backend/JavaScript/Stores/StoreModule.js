Ext.ns("F3.TYPO3.Stores");

F3.TYPO3.Core.Application.createModule('F3.TYPO3.Stores.StoreModule', {
	/**
	 * @param {F3.TYPO3.Core.Application} The application object
	 * @return {void}
	 */
	initialize: function(application) {
		application.on('afterBootstrap', this._initStores, this);
	},

	/**
	 * Initialize stores
	 *
	 * @private
	 */
	_initStores: function() {

	}
});