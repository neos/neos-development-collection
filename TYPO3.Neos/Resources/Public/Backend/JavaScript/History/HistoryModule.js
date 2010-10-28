Ext.ns("F3.TYPO3.History");
/**
 * @class F3.TYPO3.History.HistoryModule
 *
 * Module for history management
 * @namespace F3.TYPO3.History
 */
F3.TYPO3.Core.Application.createModule('F3.TYPO3.History.HistoryModule', {
	/**
	 * Listen to afterBootstrap event and fire an event for the initial
	 * token (e.g. after reload or bookmarked load).
	 *
	 * @param {F3.TYPO3.Core.Application} The Application object
	 * @return {void}
	 */
	initialize: function(application) {
		F3.TYPO3.History.HistoryManager.initialize();

		application.on('afterBootstrap', function() {
			var token = F3.TYPO3.History.HistoryManager.getToken();
			if (token) {
				F3.TYPO3.History.HistoryManager._updateState(token);
			} else {
				this.fireEvent('emptyToken');
			}
		}, this);
	}
});