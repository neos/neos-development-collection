Ext.ns("F3.TYPO3.History");
/**
 * @class F3.TYPO3.History.HistoryModule
 *
 * Module for history management
 * @namespace F3.TYPO3.History
 */
F3.TYPO3.Core.Application.createModule('F3.TYPO3.History.HistoryModule', {
	/**
	 * @return {void}
	 */
	initialize: function(application) {
		F3.TYPO3.History.HistoryManager.initialize();

		application.on('afterBootstrap', function() {
			var token = F3.TYPO3.History.HistoryManager.getToken();
			if (token) {
				F3.TYPO3.History.HistoryManager.updateState(token);
			} else {
				this.fireEvent('emptyToken');
			}
		}, this);
	}
});