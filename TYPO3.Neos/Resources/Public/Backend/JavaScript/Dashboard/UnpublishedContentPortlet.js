Ext.ns("F3.TYPO3.Dashboard");

/*                                                                        *
 * This script belongs to the FLOW3 package "TYPO3".                      *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License as published by the Free   *
 * Software Foundation, either version 3 of the License, or (at your      *
 * option) any later version.                                             *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General      *
 * Public License for more details.                                       *
 *                                                                        *
 * You should have received a copy of the GNU General Public License      *
 * along with the script.                                                 *
 * If not, see http://www.gnu.org/licenses/gpl.html                       *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * @class F3.TYPO3.Dashboard.UnpublishedContentPortlet
 *
 * A dashboard portlet for managing unpublished content
 *
 * @namespace F3.TYPO3.Dashboard
 * @extends Ext.ux.Portlet
 */
F3.TYPO3.Dashboard.UnpublishedContentPortlet = Ext.extend(Ext.ux.Portlet, {

	/**
	 * Initializer
	 */
	initComponent: function() {
		var config = {
			collapsible: false,
			title: 'Workspace Overview',
			items: {
				itemId: 'contentView',
				xtype: 'F3.TYPO3.Dashboard.UnpublishedContentView',
				ref: 'contentView',
				cls: 'F3-TYPO3-Dashboard-DashboardView-ContentView'
			},
			bbar: [{
				itemId: 'description',
				xtype: 'panel',
				html: 'The listed content is currently only visible in your personal workspace and can be published to the live website.',
				border: false,
				cls: 'F3-TYPO3-Dashboard-DashboardView-Description'
			}, {
				itemId: 'noContent',
				hidden: true,
				xtype: 'panel',
				html: 'The current workspace does not contain any unpublished content.',
				border: false,
				cls: 'F3-TYPO3-Dashboard-DashboardView-Description'
			}, '->', {
				xtype: 'F3.TYPO3.Components.Button',
				itemId: 'publishAll',
				text: 'Publish all',
				handler: this._publishAll,
				scope: this,
				cls: 'F3-TYPO3-Components-Button-type-positive F3-TYPO3-Dashboard-DashboardView-PublishButton'
			}, {
				xtype: 'F3.TYPO3.Components.Button',
				hidden: true,
				itemId: 'publishSelected',
				text: 'Publish selected',
				handler: this._publishSelected,
				scope: this,
				cls: 'F3-TYPO3-Components-Button-type-positive F3-TYPO3-Dashboard-DashboardView-PublishButton'
			}]
		};

		Ext.apply(this, config);
		F3.TYPO3.Dashboard.UnpublishedContentPortlet.superclass.initComponent.call(this);

			// Reload store if workspace status changed
		F3.TYPO3.Workspace.WorkspaceModule.on('updatedWorkspaceStatus', function(status) {
			if (status.changed) {
				this.getComponent('contentView').store.load();
			}
			if (status.unpublishedNodesCount > 0) {
				this.getBottomToolbar().getComponent('description').show();
				this.getBottomToolbar().getComponent('noContent').hide();
				this.getBottomToolbar().getComponent('publishAll').enable();
			} else {
				this.getBottomToolbar().getComponent('description').hide();
				this.getBottomToolbar().getComponent('noContent').show();
				this.getBottomToolbar().getComponent('publishAll').disable();
			}
		}, this);

			// Update button status when selection changes
		this.contentView.on('selectionchange', function(dataView, selections) {
			if (selections.length > 0) {
				this.getBottomToolbar().getComponent('publishAll').hide();
				this.getBottomToolbar().getComponent('publishSelected').show();
			} else {
				this.getBottomToolbar().getComponent('publishAll').show();
				this.getBottomToolbar().getComponent('publishSelected').hide();
			}
		}, this);
	},

	_publishAll: function(button) {
		button.disable();
		F3.TYPO3.Workspace.Service.publishUserWorkspace(function() {
			this.getComponent('contentView').store.load({callback: function() {
				button.enable();
			}, scope: this});
		}, this);
	},

	_publishSelected: function(button) {
		button.disable();
		var records = this.contentView.getSelectedRecords(), count = records.length, publishCount = 0;
		Ext.each(records, function(record) {
			F3.TYPO3.Workspace.Service.publishNode({
				'__context': {
					workspaceName: record.data.__workspaceName,
					nodePath: record.data.__nodePath
				}
			}, function() {
				publishCount++;
				if (publishCount == count) {
					this.getComponent('contentView').store.load({callback: function() {
						button.enable();
					}, scope: this});
				}
			}, this);
		}, this);
	}

});
Ext.reg('F3.TYPO3.Dashboard.UnpublishedContentPortlet', F3.TYPO3.Dashboard.UnpublishedContentPortlet);
