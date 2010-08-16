Ext.ns("F3.TYPO3.Login");

/**
 * @class F3.TYPO3.Login.Bootstrap
 * @namespace F3.TYPO3.Login
 * @extends F3.TYPO3.Application.AbstractBootstrap
 *
 * Bootstrap for login related stores, data and components
 */
F3.TYPO3.Login.Bootstrap = Ext.apply(new F3.TYPO3.Application.AbstractBootstrap, {
	/**
	 * @return {void}
	 */
	initialize: function() {
		F3.TYPO3.Application.on('F3.TYPO3.Application.afterBootstrap', function() {
			F3.TYPO3_Controller_LoginController.show(function(result) {
				F3.TYPO3.Application.fireEvent('F3.TYPO3.Login.updated', result.data);
			});
		}, this);

		F3.TYPO3.Application.on('F3.TYPO3.Login.logout', function() {
			window.location.href = F3.TYPO3.Configuration.Application.frontendBaseUri;
		}, this);
	}
});
F3.TYPO3.Application.registerBootstrap(F3.TYPO3.Login.Bootstrap);