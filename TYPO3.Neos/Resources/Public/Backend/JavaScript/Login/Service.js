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
	publishWorkspace: function(callback, scope) {
		F3.TYPO3_Service_ExtDirect_V1_Controller_WorkspaceController.publish(F3.TYPO3.Configuration.Application.workspaceName, 'live', function(result) {
			F3.TYPO3.Login.Service.updateWorkspaceStatus();
			F3.TYPO3.Login.LoginModule.fireEvent('publishedWorkspace');
			if (Ext.isFunction(callback)) callback.call(scope, result);
		});
	},

	/**
	 * Returns the status of the current workspace
	 *
	 * @return {}
	 */
	updateWorkspaceStatus: function(callback, scope) {
		F3.TYPO3_Service_ExtDirect_V1_Controller_WorkspaceController.getStatus(F3.TYPO3.Configuration.Application.workspaceName, function(result) {
			var status = result.data;
				// TODO Move the saved nodeCount to some context
			if (!F3.TYPO3.Login.LoginModule.nodeCount || F3.TYPO3.Login.LoginModule.nodeCount != status.nodeCount) {
				F3.TYPO3.Login.LoginModule.nodeCount = status.nodeCount;
				status.changed = true;
			} else {
				status.changed = false;
			}
			F3.TYPO3.Login.LoginModule.fireEvent('updatedWorkspaceStatus', status);
			if (Ext.isFunction(callback)) callback.call(scope, result);
		});
	},

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
