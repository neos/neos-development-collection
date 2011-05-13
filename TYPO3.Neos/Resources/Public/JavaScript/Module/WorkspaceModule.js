Ext.ns('F3.TYPO3.Module.Workspace');

/**
 * @class F3.TYPO3.Module.WorkspaceModule
 *
 * Module for workspace related components and events
 *
 * @namespace F3.TYPO3.Module.Workspace
 * @singleton
 */
F3.TYPO3.Core.Application.createModule('F3.TYPO3.Module.WorkspaceModule', {

	/**
	 * Update workspace status periodically
	 *
	 * @param {F3.TYPO3.Core.Application} The Application object
	 * @return {void}
	 */
	initialize: function(application) {
		application.on('afterBootstrap', function() {
			F3.TYPO3.Module.Workspace.Service.updateWorkspaceStatus();
		});
		F3.TYPO3.Module.WorkspaceModule.on('updatedWorkspaceStatus', function() {
			window.setTimeout(F3.TYPO3.Module.Workspace.Service.updateWorkspaceStatus, 6000);
		});
	}
});
