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
 * @class F3.TYPO3.Dashboard.DashboardModule
 *
 * The dashboard module
 *
 * @namespace F3.TYPO3.Dashboard
 * @extends Ext.util.Observable
 * @singleton
 */
F3.TYPO3.Core.Application.createModule('F3.TYPO3.Dashboard.DashboardModule', {

	/**
	 * Register dashboard section and menu items
	 *
	 * @param {F3.TYPO3.Core.Registry} registry
	 * @return {void}
	 */
	configure: function(registry) {
		registry.append('menu/main', 'dashboard', {
			tabCls: 'F3-TYPO3-UserInterface-SectionMenu-DashboardTab',
			title: 'Welcome',
			itemId: 'dashboard',
			listeners: {
				added: function(tab, tabPanel) {
					tabPanel.on('render', function(tabPanel) {
						var el = tabPanel.getTabEl('dashboard');
						Ext.fly(el).insertHtml('afterBegin', '<div class="F3-TYPO3-Dashboard-PublishedContentCount"></div>');
						var logoutEl = Ext.DomHelper.append(Ext.fly(el), {
							tag: 'a',
							href: '#',
							cls: 'F3-TYPO3-Dashboard-Logout',
							html: 'Logout'
						});
						Ext.fly(logoutEl).on('click', function(event) {
							event.preventDefault();
							F3.TYPO3.Login.Service.logout();
							return false;
						})
					});
				}
			}
		}, 100);
		registry.append('dashboard/column/left', 'unpublishedContentPortlet', {
			itemId: 'unpublishedContentPortlet',
			xtype: 'F3.TYPO3.Dashboard.UnpublishedContentPortlet'
		});
	},

	/**
	 * Set up event handlers
	 *
	 * @param {F3.TYPO3.Core.Application} The Application object
	 * @return {void}
	 */
	initialize: function(application) {
		application.afterInitializationOf('F3.TYPO3.UserInterface.UserInterfaceModule', function(userInterfaceModule) {
			userInterfaceModule.addContentArea('dashboard', 'dashboardView', {
				xtype: 'F3.TYPO3.Dashboard.DashboardView',
				id: 'F3-TYPO3-Dashboard-DashboardView'
			});
			userInterfaceModule.contentAreaOn('menu/main/dashboard', 'dashboard', 'dashboardView');
		});
		application.afterInitializationOf('F3.TYPO3.Login.LoginModule', function(loginModule) {
				// Put name of user into dashboard tab
			loginModule.on('updated', function(party) {
				var fullName = party.name.fullName,
					el = F3.TYPO3.UserInterface.UserInterfaceModule.viewport.sectionMenu.getTabEl('dashboard');
				Ext.fly(el).child('.x-tab-strip-text').update(fullName);
			});
		});
		application.afterInitializationOf('F3.TYPO3.Workspace.WorkspaceModule', function(workspaceModule) {
				// Listen to update workspace status
			workspaceModule.on('updatedWorkspaceStatus', function(status) {
				if (status.changed) {
					var el = F3.TYPO3.UserInterface.UserInterfaceModule.viewport.sectionMenu.getTabEl('dashboard'),
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
