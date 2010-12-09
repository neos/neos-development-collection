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
			autoHeight: true,
			items: {
				itemId: 'contentView',
				xtype: 'F3.TYPO3.Dashboard.UnpublishedContentView',
				ref: 'contentView',
				height: 400
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

			// Create toolbar for hover over content
		var fadeOutTimeout = null;
		this.contentToolbar = new Ext.Panel({
			items: [{
				xtype: 'button',
				text: 'Publish',
				handler: function() {
					var record;
					if (this.contentToolbar.contentIndex !== null) {
						record = this.contentView.store.getAt(this.contentToolbar.contentIndex);
						// TODO Publish individual record
					}
				},
				scope: this
			}, {
				xtype: 'button',
				text: 'Discard',
				handler: function() {
					var record;
					if (this.contentToolbar.contentIndex !== null) {
						record = this.contentView.store.getAt(this.contentToolbar.contentIndex);
						// TODO Discard individual record
					}
				},
				scope: this
			}],
			layout: 'hbox',
			layoutConfig: {
				align: 'middle',
				pack: 'end',
				padding: '6'
			},
			width: 120,
			height: 20,
			floating: true,
			border: false,
			shadow: false,
			renderTo: document.body,
			hidden: true
		});
		this.contentToolbar.el.on('mouseenter', function() {
			Ext.fly(this.contentToolbar.currentNode).addClass('x-view-over');
			if (fadeOutTimeout) {
				clearTimeout(fadeOutTimeout);
			}
		}, this);
		this.contentToolbar.el.on('mouseleave', function() {
			Ext.fly(this.contentToolbar.currentNode).removeClass('x-view-over');
			if (fadeOutTimeout) {
				fadeOutTimeout = setTimeout(this.contentToolbar.hide.createDelegate(this.contentToolbar), 50);
			}
		}, this);
		this.contentView.on('mouseenter', function(view, index, node, event) {
			if (fadeOutTimeout) {
				clearTimeout(fadeOutTimeout);
			}
			var nodeEl = Ext.fly(node);
			if (nodeEl) {
				this.contentToolbar.contentIndex = index;
					// FIXME Check if this is safe
				this.contentToolbar.currentNode = node;
				this.contentToolbar.show();
				this.contentToolbar.setPosition(nodeEl.getRight() - (120 + 1), nodeEl.getTop() + 1);
				this.contentToolbar.setHeight(nodeEl.getHeight() - 2);
			}
		}, this);
		this.contentView.on('mouseleave', function() {
			fadeOutTimeout = setTimeout(this.contentToolbar.hide.createDelegate(this.contentToolbar), 50);
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

	_publishAll: function() {
		F3.TYPO3.Workspace.Service.publishUserWorkspace(function() {
			this.getComponent('contentView').store.load();
		}, this);
	},

	_publishSelected: function() {
		var records = this.contentView.getSelectedRecords();
		// console.log('Not implemented...');
	}

});
Ext.reg('F3.TYPO3.Dashboard.UnpublishedContentPortlet', F3.TYPO3.Dashboard.UnpublishedContentPortlet);
