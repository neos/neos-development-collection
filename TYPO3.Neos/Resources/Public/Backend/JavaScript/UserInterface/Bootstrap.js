Ext.ns("F3.TYPO3.UserInterface");

// TODO: DOKU FÜR F3.TYPO3.UserInterface.viewport;

F3.TYPO3.UserInterface.Bootstrap = Ext.apply(new F3.TYPO3.Application.AbstractBootstrap, {
	initialize: function() { // TODO: Call like object lifecycle method in FLOW3!
		F3.TYPO3.Application.on('F3.TYPO3.Application.afterBootstrap', this.initViewport, this);
	},
	initViewport: function() {
		F3.TYPO3.UserInterface.viewport = new F3.TYPO3.UserInterface.Layout();
	}

});

F3.TYPO3.Application.registerBootstrap(F3.TYPO3.UserInterface.Bootstrap);