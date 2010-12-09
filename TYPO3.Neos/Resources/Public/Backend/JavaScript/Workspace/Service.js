Ext.ns("F3.TYPO3.Workspace");

/**
 * @class F3.TYPO3.Workspace.WorkspaceService
 *
 * Workspace service for workspace related functions
 *
 * @namespace F3.TYPO3.Workspace
 * @extends Ext.util.Observable
 * @singleton
 */
F3.TYPO3.Workspace.Service = Ext.apply(new Ext.util.Observable, {

	/**
	 * Publishes the current user workspace
	 *
	 * @return {void}
	 */
	publishUserWorkspace: function(callback, scope) {
		F3.TYPO3_Service_ExtDirect_V1_Controller_WorkspaceController.publishWorkspace(F3.TYPO3.Configuration.Application.workspaceName, 'live', function(result) {
			F3.TYPO3.Workspace.Service.updateWorkspaceStatus();
			F3.TYPO3.Workspace.WorkspaceModule.fireEvent('publishedWorkspace');
			if (Ext.isFunction(callback)) callback.call(scope, result);
		});
	},

	/**
	 * Publishes the given node records
	 *
	 * @return {void}
	 */
	publishNode: function(node, callback, scope) {
		F3.TYPO3_Service_ExtDirect_V1_Controller_WorkspaceController.publishNode(node, 'live', function(result) {
			F3.TYPO3.Workspace.Service.updateWorkspaceStatus();
			F3.TYPO3.Workspace.WorkspaceModule.fireEvent('publishedNodes', node);
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
				// TODO Move the saved unpublishedNodesCount to some context
			if (F3.TYPO3.Workspace.WorkspaceModule.unpublishedNodesCount !== status.unpublishedNodesCount) {
				F3.TYPO3.Workspace.WorkspaceModule.unpublishedNodesCount = status.unpublishedNodesCount;
				status.changed = true;
			} else {
				status.changed = false;
			}
			F3.TYPO3.Workspace.WorkspaceModule.fireEvent('updatedWorkspaceStatus', status);
			if (Ext.isFunction(callback)) callback.call(scope, result);
		});
	}
});
