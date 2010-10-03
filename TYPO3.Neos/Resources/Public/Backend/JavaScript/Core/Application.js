Ext.ns("F3.TYPO3.Core");


/**
 * @class F3.TYPO3.Core.Application
 * @namespace F3.TYPO3.Core
 * @extends Ext.util.Observable
 *
 * The main entry point which controls the lifecycle of the application.
 *
 * This is the main event handler of the application.
 *
 * <ul>
 * <li>QuickTips</li>
 * <li>History Manager</li>
 * </ul>
 *
 * @singleton
 */
F3.TYPO3.Core.Application = Ext.apply(new Ext.util.Observable, {
	/**
	 * @event afterBootstrap After bootstrap event. Should
	 * be used for main initialization
	 */

	/**
	 * @event logout called when the logout button is pressed.
	 */

	/**
	 * List of all modules which have been registered
	 * @private
	 */
	modules: {},

	/**
	 * List of callbacks to be called after initialization
	 * @private
	 */
	afterInitializationCallbacks: [],

	/**
	 * Main bootstrap. This is called by Ext.onReady and calls all registered
	 * bootstraps.
	 *
	 * This method is called automatically.
	 */
	bootstrap: function() {
		this._initializeConfiguration();

		F3.TYPO3.Core.Registry.initialize();
		this._configureModules();
		F3.TYPO3.Core.Registry.compile();

		this._initializeModules();
		Ext.QuickTips.init();

		this.fireEvent('afterBootstrap');
	},

	/**
	 * Initialize the configuration object in F3.TYPO3.Configuration
	 * @private
	 */
	_initializeConfiguration: function() {
		var baseTag = Ext.query('base')[0];
		if (typeof baseTag.href == 'string') {
			F3.TYPO3.Configuration.Application.frontendBaseUri = baseTag.href;
		} else {
			F3.TYPO3.Configuration.Application.frontendBaseUri = '/';
			if (window.console) {
				console.warn("Base URI could not be extracted");
			}
		}
		F3.TYPO3.Configuration.Application.backendBaseUri = F3.TYPO3.Configuration.Application.frontendBaseUri + 'typo3/';
	},

	/**
	 * Configure modules with the help of the registry
	 * @private
	 */
	_configureModules: function() {
		for (var moduleName in this.modules) {
			if (this.modules[moduleName].configure !== undefined) {
				this.modules[moduleName].configure(F3.TYPO3.Core.Registry);
			}
		}
	},

	/**
	 * Invoke the registered modules.
	 * @private
	 */
	_initializeModules: function() {
		for (var moduleName in this.modules) {
			if (this.modules[moduleName].initialize !== undefined) {
				this.modules[moduleName].initialize(F3.TYPO3.Core.Application);
			}
		}

		Ext.each(this.afterInitializationCallbacks, function(c) {
			if (this.modules[c.moduleName]) {
				c.callback.call(c.scope, this.modules[c.moduleName]);
			}
		}, this);
		this.afterInitializationCallbacks = [];
	},

	/**
	 * Register after module initialization handler
	 *
	 * @api
	 */
	afterInitializationOf: function(moduleName, callback, scope) {
		this.afterInitializationCallbacks.push({
			moduleName: moduleName,
			callback: callback,
			scope: scope
		});
	},

	/**
	 * Create a new module.
	 *
	 * @api
	 */
	createModule: function(moduleName, moduleDefinition) {
		var module = Ext.apply(new Ext.util.Observable(), moduleDefinition);
		this.modules[moduleName] = module;
		return module;
	}
});

Ext.onReady(F3.TYPO3.Core.Application.bootstrap, F3.TYPO3.Core.Application);
