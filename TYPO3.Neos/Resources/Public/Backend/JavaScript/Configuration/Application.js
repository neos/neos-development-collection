Ext.ns('F3.TYPO3.Configuration');

/**
 * @class F3.TYPO3.Configuration.Application
 * @namespace F3.TYPO3.Configuration
 * @singleton
 *
 * This object contains the configuration for the main TYPO3 application.
 */

F3.TYPO3.Configuration.Application = {
	/**
	 * @var String contains the base URI of the TYPO3 backend.
	 *
	 * This setting is extracted from the <base href="...">-Tag of the
	 * current page, and a "typo3/" appended to it. This happens in
	 * F3.TYPO3.Application.
	 *
	 * Instead of using this setting directly, instead use
	 * F3.TYPO3.Utils.buildBackendUri
	 */
	backendBaseUri: null
}