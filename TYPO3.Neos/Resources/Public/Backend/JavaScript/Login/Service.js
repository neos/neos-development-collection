Ext.ns("F3.TYPO3.Login");

/**
 * @class F3.TYPO3.Login.LoginService
 * @namespace F3.TYPO3.Login
 * @extends F3.TYPO3.Application.AbstractBootstrap
 *
 * Login service for login related functions
 */
F3.TYPO3.Login.Service = Ext.apply(new Ext.util.Observable, {
	/**
	 * Logout the current user
	 *
	 * @return {void}
	 */
	logout: function() {
		F3.TYPO3_Controller_Backend_LoginController.logout(function(result) {
			F3.TYPO3.Application.fireEvent('F3.TYPO3.Login.logout');
		});
	}
});
