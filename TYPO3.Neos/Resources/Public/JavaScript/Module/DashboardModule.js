Ext.ns('F3.TYPO3.Module.Dashboard');

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
 * @class F3.TYPO3.Module.DashboardModule
 *
 * The dashboard module
 *
 * @namespace F3.TYPO3.Module.Dashboard
 * @extends Ext.util.Observable
 * @singleton
 */
F3.TYPO3.Core.Application.createModule('F3.TYPO3.Module.DashboardModule', {

	/**
	 * Register dashboard section and menu items
	 *
	 * @param {F3.TYPO3.Core.Registry} registry
	 * @return {void}
	 */
	configure: function(registry) {
		registry.append('menu/main', 'dashboard', {
			tabCls: 'F3-TYPO3-UserInterface-SectionMenu-DashboardTab',
			title: F3.TYPO3.Core.I18n.get('TYPO3', 'welcome'),
			itemId: 'dashboard',
			listeners: {
				added: function(tab, tabPanel) {
					tabPanel.on('render', function(tabPanel) {
						var el = tabPanel.getTabEl('dashboard');
						Ext.fly(el).insertHtml('afterBegin', '<div class="F3-TYPO3-Dashboard-PublishedContentCount"></div>');
					});
				}
			},
			specialMenu: {
				xtype: 'container',
				items: {
					xtype: 'F3.TYPO3.Components.Button',
					cls: 'F3-TYPO3-Dashboard-Logout',
					height: 47,
					width: 47,
					text: F3.TYPO3.Core.I18n.get('TYPO3', 'logout'),
					handler: function() {
						F3.TYPO3.Module.Login.Service.logout();
					}
				},
				margins: '0 10 0 0'
			}
		}, 100);
		registry.append('dashboard/column/left', 'unpublishedContentPortlet', {
			itemId: 'unpublishedContentPortlet',
			xtype: 'F3.TYPO3.Module.Dashboard.UnpublishedContentPortlet'
		});
	},

	/**
	 * Set up event handlers
	 *
	 * @param {F3.TYPO3.Core.Application} The Application object
	 * @return {void}
	 */
	initialize: function(application) {
		application.afterInitializationOf('F3.TYPO3.Module.UserInterfaceModule', function(userInterfaceModule) {
			userInterfaceModule.addContentArea('dashboard', 'dashboardView', {
				xtype: 'F3.TYPO3.Module.Dashboard.DashboardView',
				id: 'F3-TYPO3-Dashboard-DashboardView'
			});
			userInterfaceModule.contentAreaOn('menu/main/dashboard', 'dashboard', 'dashboardView');
		});
		application.afterInitializationOf('F3.TYPO3.Module.LoginModule', function(loginModule) {
				// Put name of user into dashboard tab
			loginModule.on('updated', function(party) {
				var fullName = party.name.fullName,
					el = F3.TYPO3.Module.UserInterfaceModule.viewport.sectionMenu.getTabEl('dashboard');
				Ext.fly(el).child('.x-tab-strip-text').update(fullName);
			});
		});
		application.afterInitializationOf('F3.TYPO3.Module.WorkspaceModule', function(workspaceModule) {
				// Listen to update workspace status
			workspaceModule.on('updatedWorkspaceStatus', function(status) {
				if (status.changed) {
					var el = F3.TYPO3.Module.UserInterfaceModule.viewport.sectionMenu.getTabEl('dashboard'),
						bubble = Ext.fly(el).child('.F3-TYPO3-Dashboard-PublishedContentCount');
						// TODO CSS animation if count changed

					if (status.unpublishedNodesCount > 0) {
						bubble.fadeIn();
						bubble.update(status.unpublishedNodesCount.toString());
					} else {
						bubble.hide();
					}
				}
			});
		});
	}
});
