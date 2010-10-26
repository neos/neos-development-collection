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
	 * @event _afterInitializationOf.[ModuleName]
	 * For each initialized module, one of these events is executed.
	 * All callbacks which are registered with the afterInitializationOf
	 * are used here.
	 * @param {[ModuleObject]} module the target module is passed as first and only parameter.
	 * @private
	 */

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
	 * Invoke the initialize() method on the registered modules,
	 * and afterwards, invoke all "afterInitializationOf" callbacks for the
	 * registered modules.
	 * @private
	 */
	_initializeModules: function() {
		var moduleName;
		for (moduleName in this.modules) {
			if (this.modules[moduleName].initialize !== undefined) {
				this.modules[moduleName].initialize(F3.TYPO3.Core.Application);
			}
		}

		for (moduleName in this.modules) {
			this.fireEvent('_afterInitializationOf.' + moduleName, this.modules[moduleName]);
		}
	},

	/**
	 * Register a new Module Initialization Handler.
	 *
	 * After the module specified with {moduleName} is initialized, the specified
	 * {callback} is invoked, and receives the specified module as parameter.
	 *
	 * This method should only be called inside "initialize" of each module.
	 *
	 * @param {String} moduleName the module name to listen to
	 * @param {Function} callback the callback function
	 * @param {Object} scope scope for the callback
	 * @api
	 */
	afterInitializationOf: function(moduleName, callback, scope) {
		this.addListener('_afterInitializationOf.' + moduleName, callback, scope);
	},

	/**
	 * Create a new module.
	 *
	 * @api
	 */
	createModule: function(moduleName, moduleDefinition) {
		var module = Ext.apply(new Ext.util.Observable(), moduleDefinition),
		    splittedModuleName, o;

		this.modules[moduleName] = module;

		splittedModuleName = moduleName.split('.');
		o = window[splittedModuleName[0]] = window[splittedModuleName[0]] || {};
		Ext.each(splittedModuleName.slice(1, -1), function(moduleNamePart) {
			o = o[moduleNamePart] = o[moduleNamePart] || {};
		});
		o[splittedModuleName[splittedModuleName.length-1]] = module;
	}
});

Ext.onReady(F3.TYPO3.Core.Application.bootstrap, F3.TYPO3.Core.Application);
