Ext.ns("F3.TYPO3.Login");

/**
 * @class F3.TYPO3.Login.LoginService
 *
 * Login service for login related functions
 *
 * @namespace F3.TYPO3.Login
 * @extends Ext.util.Observable
 * @singleton
 */
F3.TYPO3.Login.Service = Ext.apply(new Ext.util.Observable, {
	/**
	 * Publishes the current user workspace
	 *
	 * @return {void}
	 */
	publishWorkspace: function() {
		F3.TYPO3_Service_ExtDirect_V1_Controller_WorkspaceController.publish(F3.TYPO3.Configuration.Application.workspaceName, 'live');
	},
	/**
	 * Logout the current user
	 *
	 * @return {void}
	 */
	logout: function() {
		F3.TYPO3_Controller_LoginController.logout(function(result) {
			F3.TYPO3.Core.Application.fireEvent('logout');
		});
	}
});