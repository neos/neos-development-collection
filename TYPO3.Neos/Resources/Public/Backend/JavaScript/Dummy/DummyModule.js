Ext.ns("F3.TYPO3.Dummy");

F3.TYPO3.Dummy.DummyModule = F3.TYPO3.Core.Application.createModule('F3.TYPO3.Dummy.DummyModule', {

	configure: function(registry) {

		registry.append('menu[main]', 'report', {
			title: 'Report',
			itemId: 'report'
		});

		registry.append('menu[main]', 'layout', {
			title: 'Layout',
			itemId: 'layout'
		});

		registry.append('menu[main]', 'system', {
			title: 'System',
			itemId: 'system'
		});

	},
	initialize: function(application) {
		application.afterInitializationOf('F3.TYPO3.UserInterface.UserInterfaceModule', function(userInterfaceModule) {

			userInterfaceModule.addContentArea('report', 'dummy', {
				xtype: 'F3.TYPO3.Dummy.DummyContentArea',
				name: 'Report'
			});
			userInterfaceModule.contentAreaOn('menu[main]/report', 'report', 'dummy');

			userInterfaceModule.addContentArea('layout', 'dummy', {
				xtype: 'F3.TYPO3.Dummy.DummyContentArea',
				name: 'Layout'
			});
			userInterfaceModule.contentAreaOn('menu[main]/layout', 'layout', 'dummy');

			userInterfaceModule.addContentArea('system', 'dummy', {
				xtype: 'F3.TYPO3.Dummy.DummyContentArea',
				name: 'System'
			});
			userInterfaceModule.contentAreaOn('menu[main]/system', 'system', 'dummy');
		});
	}
});