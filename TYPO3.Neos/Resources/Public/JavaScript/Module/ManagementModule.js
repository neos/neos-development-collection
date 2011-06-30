Ext.ns('TYPO3.TYPO3.Module.Management');

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
 * @class TYPO3.TYPO3.Module.ManagementModule
 *
 * Management Module Component
 *
 * @namespace TYPO3.TYPO3.Module.Management
 * @singleton
 */
TYPO3.TYPO3.Core.Application.createModule('TYPO3.TYPO3.Module.ManagementModule', {

	/**
	 *
	 * @param {TYPO3.TYPO3.Core.Registry} registry
	 * @return {void}
	 */
	configure: function(registry) {
		registry.append('menu[main]', 'management', {
			tabCls: 'TYPO3-TYPO3-UserInterface-SectionMenu-ManagementTab',
			title: TYPO3.TYPO3.Core.I18n.get('TYPO3.TYPO3', 'management'),
			itemId: 'management'
		});

		registry.append('management/components/west', 'defaultTree', {
			xtype: 'TYPO3.TYPO3.Module.Management.ManagementTree',
			itemId: 'managementWestDefaultTree'
		});

		registry.append('management/components/center', 'defaultGrid', {
			xtype: 'TYPO3.TYPO3.Module.Management.ManagementNodeView',
			itemId: 'managementCenterNodeView'
		});

	},

	/**
	 *
	 * @param {TYPO3.TYPO3.Core.Application} application
	 * @return {void}
	 */
	initialize: function(application) {
		application.afterInitializationOf('TYPO3.TYPO3.Module.UserInterfaceModule', function(userInterfaceModule) {
			userInterfaceModule.addContentArea('management', 'managementView', {
				xtype: 'TYPO3.TYPO3.Module.Management.ManagementView',
				id: 'TYPO3-TYPO3-Management-ManagementView'
			});
			userInterfaceModule.contentAreaOn('menu[main]/management', 'management', 'managementView');

		});
	}
});