Ext.ns("F3.TYPO3.UserInterface");

F3.TYPO3.UserInterface.Bootstrap = Ext.apply(new F3.TYPO3.Application.AbstractBootstrap, {
	initialize: function() {
		F3.TYPO3.Application.on('F3.TYPO3.Application.afterBootstrap', this._initViewport, this);
	},
	/**
	 * Create the main viewport for layouting all components in a full
	 * width and height browser window.
	 */
	_initViewport: function() {
		F3.TYPO3.UserInterface.viewport = new F3.TYPO3.UserInterface.Layout();
		F3.TYPO3.Application.fireEvent('F3.TYPO3.UserInterface.afterInitialize');
	}

});
F3.TYPO3.Application.registerBootstrap(F3.TYPO3.UserInterface.Bootstrap);
