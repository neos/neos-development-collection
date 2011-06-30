Ext.ns('TYPO3.TYPO3.Module.Workspace');

/**
 * @class TYPO3.TYPO3.Module.WorkspaceModule
 *
 * Module for workspace related components and events
 *
 * @namespace TYPO3.TYPO3.Module.Workspace
 * @singleton
 */
TYPO3.TYPO3.Core.Application.createModule('TYPO3.TYPO3.Module.WorkspaceModule', {

	/**
	 * Update workspace status periodically
	 *
	 * @param {TYPO3.TYPO3.Core.Application} The Application object
	 * @return {void}
	 */
	initialize: function(application) {
		application.on('afterBootstrap', function() {
			TYPO3.TYPO3.Module.Workspace.Service.updateWorkspaceStatus();
		});
		TYPO3.TYPO3.Module.WorkspaceModule.on('updatedWorkspaceStatus', function() {
			window.setTimeout(TYPO3.TYPO3.Module.Workspace.Service.updateWorkspaceStatus, 6000);
		});
	}
});
