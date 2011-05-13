Ext.ns('F3.TYPO3.Module.Login');

/**
 * @class F3.TYPO3.Module.Login.LoginService
 *
 * Login service for login related functions
 *
 * @namespace F3.TYPO3.Module.Login
 * @extends Ext.util.Observable
 * @singleton
 */
F3.TYPO3.Module.Login.Service = Ext.apply(new Ext.util.Observable, {

	/**
	 * Logout the current user
	 *
	 * @return {void}
	 */
	logout: function(callback, scope) {
		F3.TYPO3_Controller_LoginController.logout(function() {
			F3.TYPO3.Core.Application.fireEvent('logout');
			if (Ext.isFunction(callback)) callback.call(scope);
		});
	}
});
