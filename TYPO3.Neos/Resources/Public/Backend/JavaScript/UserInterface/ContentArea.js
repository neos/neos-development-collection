Ext.ns("F3.TYPO3.UserInterface");

F3.TYPO3.UserInterface.ContentArea = Ext.extend(Ext.Panel, {
	/**
	 * @event F3.TYPO3.UserInterface.ContentArea.afterInit
	 * @param {F3.TYPO3.UserInterface.ContentArea} a reference to the main area,
	 *
	 * Event triggered after initialization of the main area. Should be used
	 * to add elements to the main area.
	 */

	layout: 'card',
	initComponent: function() {
		var config = {
			autoScroll: true,
			border: false,
			items: []
		};
		Ext.apply(this, config);
		F3.TYPO3.UserInterface.ContentArea.superclass.initComponent.call(this);

		F3.TYPO3.UserInterface.UserInterfaceModule.fireEvent('ContentArea.initialized', this);
	}
});

Ext.reg('F3.TYPO3.UserInterface.ContentArea', F3.TYPO3.UserInterface.ContentArea);