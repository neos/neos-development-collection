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
 * First, it calls all registered bootstrappers, thus other modules can register event listeners.
 * Afterwards, the bootstrap procedure is started. During bootstrap, it will initialize:
 * <ul><li>QuickTips</li>
 * <li>History Manager</li></ul>
 *
 * @singleton
 */
F3.TYPO3.Application = Ext.apply(new Ext.util.Observable, {
	/**
	 * @event F3.TYPO3.Application.afterBootstrap
	 * After bootstrap event. Should be used for main initialization.
	 */

	bootstrappers: [],

	/**
	 * Main bootstrap. This is called by Ext.onReady and calls all registered bootstraps.
	 *
	 * This method is called automatically.
	 */
	bootstrap: function() {
		this._configureExtJs();
		this._initializeExtDirect();
		this._registerEventDebugging();
		this._invokeBootstrappers();

		Ext.QuickTips.init();

		this.fireEvent('F3.TYPO3.Application.afterBootstrap');

		this._initializeHistoryManager();
	},

	/**
	 * Registers a new bootstrap class.
	 *
	 * Every bootstrap class needs to extend F3.TYPO3.Application.AbstractBootstrap.
	 * @param {F3.TYPO3.Application.AbstractBootstrap} bootstrap The bootstrap class to be registered.
	 * @api
	 */
	registerBootstrap: function(bootstrap) {
		this.bootstrappers.push(bootstrap);
	},


	// pirvate
	/**
	 * Initialize Ext.Direct Provider
	 */
	_initializeExtDirect: function() {
		if (F3.TYPO3 && F3.TYPO3.Direct) {
			F3.TYPO3.Direct.REMOTING_API.enableBuffer = 100;
			Ext.Direct.addProvider(F3.TYPO3.Direct.REMOTING_API);
		} else {
			console.log("ExtDirect NOT initialized");
		}
	},

	// private
	/**
	 */
	_configureExtJs: function() {

	},

	/**
	 * Invoke the registered bootstrappers.
	 */
	_invokeBootstrappers: function() {
		Ext.each(this.bootstrappers, function(bootstrapper) {
			bootstrapper.initialize();
		});
	},
	_initializeHistoryManager: function() {
		if (Ext.fly('history-form') != null) {
			Ext.History.on('change', function(token) {
				this.fireEvent('F3.TYPO3.Application.navigate', token);
			}, this);
			// Handle initial token (on page load)
			Ext.History.init(function(history) {
				history.fireEvent('change', history.getToken());
			}, this);
		} else {
			console.log("History manager could not be initialized, because the form with the ID 'history-form' was not there.");
		}
	},
	_registerEventDebugging: function() {
		Ext.util.Observable.capture(
			this,
			function(e) {
				if (window.console && window.console.log) {
					console.log(e, arguments);
				}
			}
		);
	}

});

Ext.onReady(F3.TYPO3.Application.bootstrap, F3.TYPO3.Application);