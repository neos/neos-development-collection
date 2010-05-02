Ext.ns('F3.TYPO3.Configuration');

/**
 * @class F3.TYPO3.Configuration.Application
 * @namespace F3.TYPO3.Configuration
 * @extends Object
 * 
 * This object contains the configuration for the main TYPO3 application.
 *
 * @singleton
 */

F3.TYPO3.Configuration.Application = {
	/**
	 * This setting is extracted from the base-Tag of the
	 * current page, and a "typo3/" appended to it. This happens in
	 * {@link F3.TYPO3.Application}.
	 *
	 * Instead of using this setting directly, instead use
	 * {@link F3.TYPO3.Utils#buildBackendUri}
	 *
	 * @type {String}
	 */
	backendBaseUri: null
}