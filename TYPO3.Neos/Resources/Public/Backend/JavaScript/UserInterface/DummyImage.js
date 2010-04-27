Ext.ns('F3.TYPO3.UserInterface');

F3.TYPO3.UserInterface.DummyImage = Ext.extend(Ext.BoxComponent, {
	backgroundImage: null,

	initComponent: function() {
		var config = {
			autoEl: {
				tag: 'img',
				src: this.backgroundImage,
				width: 100
			}
		};
		Ext.apply(this, config);
		F3.TYPO3.UserInterface.DummyImage.superclass.initComponent.call(this);
	}
});
Ext.reg('F3.TYPO3.UserInterface.DummyImage', F3.TYPO3.UserInterface.DummyImage);