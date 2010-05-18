Ext.ns("F3.TYPO3.Content");

F3.TYPO3.Content.Bootstrap = Ext.apply(new F3.TYPO3.Application.AbstractBootstrap, {
	initialize: function() {
		this.handleNavigationToken(/content/, function() {
			F3.TYPO3.UserInterface.viewport.sectionMenu.setActiveTab('content');
		});

		this.addContentArea(
			'content',
			'F3-TYPO3-Content-Preview', {
				xtype: 'F3.TYPO3.Content.FrontendEditor',
				id: 'F3.TYPO3.Content.FrontendEditor',
				layout: 'fit'
			}
		);

		F3.TYPO3.Application.on('F3.TYPO3.UserInterface.afterInitialize', function() {
			F3.TYPO3.UserInterface.viewport.sectionMenu.setActiveTab('content');
		}, this);

		// TODO refactor to helper method
		F3.TYPO3.Application.on('F3.TYPO3.UserInterface.SectionMenu.activated', function(itemId) {
			if (itemId === 'content') {
				Ext.History.add('content');
				// TODO long path and coupling, refactor to method in SectionMenu
				F3.TYPO3.UserInterface.viewport.sectionMenu.getComponent('content').contentArea.getLayout().setActiveItem('F3-TYPO3-Content-Preview');
			}
		});
	}
});
F3.TYPO3.Application.registerBootstrap(F3.TYPO3.Content.Bootstrap);