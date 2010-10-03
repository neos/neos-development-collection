Ext.ns("F3.TYPO3.Dummy");

F3.TYPO3.Dummy.DummyContentArea = Ext.extend(Ext.Panel, {
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
			html: '<h1 style="font-family:Share-Bold, sans-serif; margin-top:30px; text-align:center;font-size:45px;">' + this.name + '</h1>'
		};
		Ext.apply(this, config);
		F3.TYPO3.Dummy.DummyContentArea.superclass.initComponent.call(this);
	}
});

Ext.reg('F3.TYPO3.Dummy.DummyContentArea', F3.TYPO3.Dummy.DummyContentArea);