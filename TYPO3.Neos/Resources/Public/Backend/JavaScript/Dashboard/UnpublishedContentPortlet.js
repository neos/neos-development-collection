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
			tools: [{
				id: 'gear',
				handler: function(event) {
					var menu = new Ext.menu.Menu({
						// TODO re-use edit action
						items: [{
							text: 'Configure widget'
						}, {
							text: 'Rename title'
						}, '-', {
							text: 'Remove from dashboard'
						}],
						listeners: {
							hide: function(menu) {
								menu.destroy();
							}
						}
					}).showAt(event.getXY());
				}
			}],
			title: 'Modified content',
			height: 400,
			items: {
				itemId: 'contentView',
				xtype: 'F3.TYPO3.Dashboard.UnpublishedContentView',
				ref: 'contentView'
			},bbar: [{
				itemId: 'publishAll',
				text: 'Publish all',
				handler: this._publishAll,
				scope: this
			}, {
				hidden: true,
				itemId: 'publishSelected',
				text: 'Publish selected',
				handler: this._publishSelected,
				scope: this
			}, '->', {
				text: 'Update list',
				handler: function() {
					this.getComponent('contentView').store.load();
				},
				scope: this
			}]
		};
		Ext.apply(this, config);
		F3.TYPO3.Dashboard.UnpublishedContentPortlet.superclass.initComponent.call(this);

			// Reload store if workspace status changed
		F3.TYPO3.Workspace.WorkspaceModule.on('updatedWorkspaceStatus', function(status) {
			if (status.changed) {
				this.getComponent('contentView').store.load();
			}
		}, this);

			// Update button status when  selection changes
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
