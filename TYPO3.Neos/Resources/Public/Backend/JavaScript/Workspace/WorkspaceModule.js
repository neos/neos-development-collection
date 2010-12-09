Ext.ns("F3.TYPO3.Workspace");

/**
 * @class F3.TYPO3.Workspace.WorkspaceModule
 *
 * Module for workspace related components and events
 *
 * @namespace F3.TYPO3.Workspace
 * @singleton
 */
F3.TYPO3.Core.Application.createModule('F3.TYPO3.Workspace.WorkspaceModule', {

	/**
	 * Update workspace status periodically
	 *
	 * @param {F3.TYPO3.Core.Application} The Application object
	 * @return {void}
	 */
	initialize: function(application) {
		application.on('afterBootstrap', function() {
			F3.TYPO3.Workspace.Service.updateWorkspaceStatus();
		});
		F3.TYPO3.Workspace.WorkspaceModule.on('updatedWorkspaceStatus', function() {
			window.setTimeout(F3.TYPO3.Workspace.Service.updateWorkspaceStatus, 60000);
		});
	}
});
