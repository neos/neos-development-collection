Ext.ns('F3.TYPO3.UserInterface');

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
 * @class F3.TYPO3.UserInterface.LoginStatus
 *
 * A container which shows the login status.
 *
 * @namespace F3.TYPO3.UserInterface
 * @extends Ext.Container
 */
F3.TYPO3.UserInterface.LoginStatus = Ext.extend(Ext.Container, {
	initComponent: function() {
		var config = {
			layout: 'hbox',
			layoutConfig: {
				pack: 'end',
				align: 'middle'
			},
			items: [
				{
					xtype: 'box',
					width: 150,
					itemId: 'statusText',
					tpl: '<tpl for="name">{fullName}</tpl>',
					id: 'F3-TYPO3-TopBar-StatusText'
				}, {
					xtype: 'F3.TYPO3.Components.Button',
					itemId: 'publishWorkspaceButton',
					text: 'Publish',
					handler: function() {
						F3.TYPO3.Login.Service.publishWorkspace();
					},
					scope: this
				}, {
					xtype: 'F3.TYPO3.Components.Button',
					itemId: 'logoutButton',
					text: 'Logout',
					handler: function() {
						F3.TYPO3.Login.Service.logout();
					},
					scope: this
				}]
		};
		Ext.apply(this, config);
		F3.TYPO3.UserInterface.LoginStatus.superclass.initComponent.call(this);

		F3.TYPO3.Login.LoginModule.on('updated', function(party) {
			this.getComponent('statusText').update(party);
			this.getComponent('statusText').el.fadeIn();
			this.doLayout();
		}, this);
	}

});
Ext.reg('F3.TYPO3.UserInterface.LoginStatus', F3.TYPO3.UserInterface.LoginStatus);