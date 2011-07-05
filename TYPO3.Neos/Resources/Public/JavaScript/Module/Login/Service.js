Ext.ns('TYPO3.TYPO3.Module.Login');

/**
 * @class TYPO3.TYPO3.Module.Login.LoginService
 *
 * Login service for login related functions
 *
 * @namespace TYPO3.TYPO3.Module.Login
 * @extends Ext.util.Observable
 * @singleton
 */
TYPO3.TYPO3.Module.Login.Service = Ext.apply(new Ext.util.Observable, {

	/**
	 * Logout the current user
	 *
	 * @return {void}
	 */
	logout: function(callback, scope) {
		TYPO3_TYPO3_Controller_LoginController.logout(function() {
			TYPO3.TYPO3.Core.Application.fireEvent('logout');
			if (Ext.isFunction(callback)) callback.call(scope);
		});
	}
});
