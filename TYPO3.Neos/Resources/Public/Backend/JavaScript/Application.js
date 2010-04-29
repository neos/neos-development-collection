Ext.ns("F3.TYPO3");

/**
 * @class F3.TYPO3.Application
 * @namespace F3.TYPO3
 * @extends Ext.util.Observable
 *
 * The main entry point which controls the lifecycle of the application.
 *
 * This is the main event handler of the application.
 *
 * First, it calls all registered bootstrappers, thus other modules can register
 * event listeners. Afterwards, the bootstrap procedure is started. During
 * bootstrap, it will initialize:
 * <ul>
 * <li>QuickTips</li>
 * <li>History Manager</li>
 * </ul>
 *
 * @singleton
 */
F3.TYPO3.Application = Ext.apply(new Ext.util.Observable, {
	/**
	 * @event F3.TYPO3.Application.afterBootstrap After bootstrap event. Should
	 * be used for main initialization.
	 */

	bootstrappers: [],

	/**
	 * Main bootstrap. This is called by Ext.onReady and calls all registered
	 * bootstraps.
	 *
	 * This method is called automatically.
	 */
	bootstrap: function() {
		this._invokeBootstrappers();

		Ext.QuickTips.init();

		this.fireEvent('F3.TYPO3.Application.afterBootstrap');
	},

	/**
	 * Registers a new bootstrap class.
	 *
	 * Every bootstrap class needs to extend
	 * F3.TYPO3.Application.AbstractBootstrap.
	 *
	 * @param {F3.TYPO3.Application.AbstractBootstrap} bootstrap The bootstrap
	 * class to be registered.
	 * @api
	 */
	registerBootstrap: function(bootstrap) {
		this.bootstrappers.push(bootstrap);
	},

	/**
	 * Invoke the registered bootstrappers.
	 */
	_invokeBootstrappers: function() {
		Ext.each(
			this.bootstrappers,
			function(bootstrapper) {
				bootstrapper.initialize();
			}
		);
	}
});

Ext.onReady(F3.TYPO3.Application.bootstrap, F3.TYPO3.Application);