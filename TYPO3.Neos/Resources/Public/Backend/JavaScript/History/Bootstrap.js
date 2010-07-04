Ext.ns("F3.TYPO3.History");

/**
 * @class F3.TYPO3.History.Bootstrap
 * @namespace F3.TYPO3.History
 * @extends F3.TYPO3.Application.AbstractBootstrap
 *
 * Bootstrap for history management
 */
F3.TYPO3.History.Bootstrap = Ext.apply(new F3.TYPO3.Application.AbstractBootstrap, {
	/**
	 * @return {void}
	 */
	initialize: function() {
		F3.TYPO3.Application.on('F3.TYPO3.UserInterface.afterInitialize', function() {
			F3.TYPO3.History.HistoryManager.initialize();
			var token = F3.TYPO3.History.HistoryManager.getToken();
			if (token) {
				F3.TYPO3.History.HistoryManager.updateState(token);
			} else {
				F3.TYPO3.Application.fireEvent('F3.TYPO3.History.emptyToken');
			}
		}, this);
	}
});
F3.TYPO3.Application.registerBootstrap(F3.TYPO3.History.Bootstrap);