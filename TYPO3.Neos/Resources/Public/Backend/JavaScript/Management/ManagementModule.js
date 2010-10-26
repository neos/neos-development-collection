Ext.ns("F3.TYPO3.Management");

/**
 * @class F3.TYPO3.Management.ManagementModule
 * @namespace F3.TYPO3.Management
 * @extends Ext.util.Observable
 * @author Christian Müller <christian@kitsunet.de>
 *
 * The Management Module Main component
 */
F3.TYPO3.Core.Application.createModule('F3.TYPO3.Management.ManagementModule', {

	configure: function(registry) {
		registry.append('menu[main]', 'management', {
			tabCls: 'F3-TYPO3-UserInterface-SectionMenu-ManagementTab',
			title: 'Management',
			itemId: 'management'
		});
	},

	initialize: function(application) {
		application.afterInitializationOf('F3.TYPO3.UserInterface.UserInterfaceModule', function(userInterfaceModule) {
			userInterfaceModule.addContentArea('management', 'managementView', {
				xtype: 'F3.TYPO3.Management.ManagementView',
				id: 'F3.TYPO3.Management.ManagementView'
			});
			userInterfaceModule.contentAreaOn('menu[main]/management', 'management', 'managementView');

		});
	}
});