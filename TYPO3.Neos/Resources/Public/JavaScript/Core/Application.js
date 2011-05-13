Ext.ns('F3.TYPO3.Core');

/*                                                                        *
 * This script belongs to the FLOW3 package "TYPO3".                      *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License as published by the Free   *
 * Software Foundation, either version 3 of the License, or (at your      *
 * option) any later version.                                             *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General      *
 * Public License for more details.                                       *
 *                                                                        *
 * You should have received a copy of the GNU General Public License      *
 * along with the script.                                                 *
 * If not, see http://www.gnu.org/licenses/gpl.html                       *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * @class F3.TYPO3.Core.Application
 *
 * The main entry point which controls the lifecycle of the application.
 *
 * This is the main event handler of the application.
 *
 * @namespace F3.TYPO3.Core
 * @extends Ext.util.Observable
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
	_modules: {},

	/**
	 * Main bootstrap. This is called by Ext.onReady and calls all registered
	 * bootstraps.
	 *
	 * This method is called automatically.
	 */
	bootstrap: function() {
		this._initializeConfiguration();

		F3.TYPO3.Core.HistoryManager.initialize(this);
		F3.TYPO3.Core.Registry.initialize();

		this._configureModules();
		F3.TYPO3.Core.Registry.compile();

		this._initializeModules();
		Ext.QuickTips.init();

		this.fireEvent('afterBootstrap');
		F3.TYPO3.Core.HistoryManager.start();
	},

	/**
	 * Initialize the configuration object in F3.TYPO3.Configuration
	 *
	 * @private
	 */
	_initializeConfiguration: function() {
		var baseTag = Ext.query('base')[0];
		if (typeof baseTag.href == 'string') {
			F3.TYPO3.Configuration.Application.frontendBaseUri = baseTag.href;
		} else {
			F3.TYPO3.Configuration.Application.frontendBaseUri = '/';
			if (window.console) {
				console.warn(F3.TYPO3.Core.I18n.get('TYPO3', 'couldNotExtraBaseURI'));
			}
		}
		F3.TYPO3.Configuration.Application.backendBaseUri = F3.TYPO3.Configuration.Application.frontendBaseUri + 'typo3/';
	},

	/**
	 * Configure modules with the help of the registry
	 *
	 * @private
	 */
	_configureModules: function() {
		for (var moduleName in this._modules) {
			if (this._modules[moduleName].configure !== undefined) {
				this._modules[moduleName].configure(F3.TYPO3.Core.Registry);
			}
		}
	},

	/**
	 * Invoke the initialize() method on the registered modules,
	 * and afterwards, invoke all "afterInitializationOf" callbacks for the
	 * registered modules.
	 *
	 * @private
	 */
	_initializeModules: function() {
		var moduleName;
		for (moduleName in this._modules) {
			if (this._modules[moduleName].initialize !== undefined) {
				this._modules[moduleName].initialize(F3.TYPO3.Core.Application);
			}
		}

		for (moduleName in this._modules) {
			this.fireEvent('_afterInitializationOf.' + moduleName, this._modules[moduleName]);
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
	 */
	afterInitializationOf: function(moduleName, callback, scope) {
		this.addListener('_afterInitializationOf.' + moduleName, callback, scope);
	},

	/**
	 * Create a new module.
	 *
	 * @param {String} moduleName the name of the module to create
	 * @param {Object} moduleDefinition the module definition
	 */
	createModule: function(moduleName, moduleDefinition) {
		var module = Ext.apply(new Ext.util.Observable(), moduleDefinition),
		    splittedModuleName, o;

		this._modules[moduleName] = module;

		splittedModuleName = moduleName.split('.');
		o = window[splittedModuleName[0]] = window[splittedModuleName[0]] || {};
		Ext.each(splittedModuleName.slice(1, -1), function(moduleNamePart) {
			o = o[moduleNamePart] = o[moduleNamePart] || {};
		});
		o[splittedModuleName[splittedModuleName.length-1]] = module;
	}
});

Ext.onReady(F3.TYPO3.Core.Application.bootstrap, F3.TYPO3.Core.Application);