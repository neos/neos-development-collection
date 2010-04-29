Ext.ns("F3.TYPO3.Login");

/**
 * @class F3.TYPO3.Login.Layout
 * @namespace F3.TYPO3.Login
 * @extends Ext.Viewport
 *
 * Layout configuration for the viewport
 */

F3.TYPO3.Login.Layout = Ext.extend(Ext.Viewport, {
	layout: 'border',

	initComponent: function() {
		var config = {
			items: [{
				region: 'north',
				xtype: 'F3.TYPO3.Login.LoginForm'
			}, {
				region: 'center',
				html: '<iframe src="http://flow3.typo3.org" width="100%" height="100%" name="Website"></iframe>'
			}]
		};
		Ext.apply(this, config);
		F3.TYPO3.Login.Layout.superclass.initComponent.call(this);
	}
});