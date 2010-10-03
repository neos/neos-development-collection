Ext.ns("F3.TYPO3.Login");

/**
 * @class F3.TYPO3.Login.LoginModule
 * @namespace F3.TYPO3.Login
 *
 * Bootstrap for login related stores, data and components
 */
F3.TYPO3.Login.LoginModule = F3.TYPO3.Core.Application.createModule('F3.TYPO3.Login.LoginModule', {
	/**
	 * @return {void}
	 */
	initialize: function(application) {
		application.on('afterBootstrap', function() {
			F3.TYPO3_Controller_LoginController.show(function(result) {
				F3.TYPO3.Login.LoginModule.fireEvent('updated', result.data);
			});
		}, this);

		application.on('logout', function() {
			window.location.href = F3.TYPO3.Configuration.Application.frontendBaseUri;
		}, this);
	}
});