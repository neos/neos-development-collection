Ext.ns('TYPO3.TYPO3.Module.Workspace');

/**
 * @class TYPO3.TYPO3.Module.Workspace.WorkspaceService
 *
 * Workspace service for workspace related functions
 *
 * @namespace TYPO3.TYPO3.Module.Workspace
 * @extends Ext.util.Observable
 * @singleton
 */
TYPO3.TYPO3.Module.Workspace.Service = Ext.apply(new Ext.util.Observable, {

	/**
	 * Publishes the current user workspace
	 *
	 * @return {void}
	 */
	publishUserWorkspace: function(callback, scope) {
		TYPO3_TYPO3_Service_ExtDirect_V1_Controller_WorkspaceController.publishWorkspace(TYPO3.TYPO3.Configuration.Application.workspaceName, 'live', function(result) {
			TYPO3.TYPO3.Module.Workspace.Service.updateWorkspaceStatus();
			TYPO3.TYPO3.Module.WorkspaceModule.fireEvent('publishedWorkspace');
			if (Ext.isFunction(callback)) callback.call(scope, result);
		});
	},

	/**
	 * Publishes the given node records
	 *
	 * @return {void}
	 */
	publishNode: function(node, callback, scope) {
		TYPO3_TYPO3_Service_ExtDirect_V1_Controller_WorkspaceController.publishNode(node, 'live', function(result) {
			TYPO3.TYPO3.Module.Workspace.Service.updateWorkspaceStatus();
			TYPO3.TYPO3.Module.WorkspaceModule.fireEvent('publishedNodes', node);
			if (Ext.isFunction(callback)) callback.call(scope, result);
		});
	},

	/**
	 * Returns the status of the current workspace
	 *
	 * @return {}
	 */
	updateWorkspaceStatus: function(callback, scope) {
		TYPO3_TYPO3_Service_ExtDirect_V1_Controller_WorkspaceController.getStatus(TYPO3.TYPO3.Configuration.Application.workspaceName, function(result) {
			var status = result.data;
				// TODO Move the saved unpublishedNodesCount to some context
			if (TYPO3.TYPO3.Module.WorkspaceModule.unpublishedNodesCount !== status.unpublishedNodesCount) {
				TYPO3.TYPO3.Module.WorkspaceModule.unpublishedNodesCount = status.unpublishedNodesCount;
				status.changed = true;
			} else {
				status.changed = false;
			}
			TYPO3.TYPO3.Module.WorkspaceModule.fireEvent('updatedWorkspaceStatus', status);
			if (Ext.isFunction(callback)) callback.call(scope, result);
		});
	}
});
