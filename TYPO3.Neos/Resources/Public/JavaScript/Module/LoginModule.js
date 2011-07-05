Ext.ns('TYPO3.TYPO3.Module.Login');

/**
 * @class TYPO3.TYPO3.Module.LoginModule
 *
 * Bootstrap for login related stores, data and components
 *
 * @namespace TYPO3.TYPO3.Module.Login
 * @singleton
 */
TYPO3.TYPO3.Core.Application.createModule('TYPO3.TYPO3.Module.LoginModule', {

	/**
	 * Get login status after boostrap and handle logout event
	 *
	 * @param {TYPO3.TYPO3.Core.Application} The Application object
	 * @return {void}
	 */
	initialize: function(application) {
		application.on('afterBootstrap', function() {
			TYPO3_TYPO3_Controller_LoginController.show(function(result) {
				if (result) {
					TYPO3.TYPO3.Module.LoginModule.fireEvent('updated', result.data);
				}
			});
		}, this);

		application.on('logout', function() {
			window.location.href = TYPO3.TYPO3.Configuration.Application.frontendBaseUri;
		}, this);
	}
});