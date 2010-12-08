Ext.ns('F3.TYPO3.Configuration');

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
 * @class F3.TYPO3.Configuration.Application
 *
 * This object contains the configuration for the main TYPO3 application.
 *
 * @namespace F3.TYPO3.Configuration
 * @extends Object
 * @singleton
 */
F3.TYPO3.Configuration.Application = {

	/**
	 * This setting is extracted from the base-Tag of the
	 * current page, and a "typo3/" appended to it. This happens in
	 * {@link F3.TYPO3.Core.Application}.<br />
	 *
	 * Instead of using this setting directly, use
	 * {@link F3.TYPO3.Utils#buildBackendUri}
	 *
	 * @type {String}
	 */
	backendBaseUri: null,

	/**
	 * The frontend base URI.
	 *
	 * This setting is extracted from the base-Tag of the
	 * current page. This happens in
	 * {@link F3.TYPO3.Core.Application}.
	 *
	 * @type {String}
	 */
	frontendBaseUri: null,

	/**
	 * Name of the workspace of the currently logged in user.
	 *
	 * This setting is set by a small script in the Backend
	 * Fluid template and is used by the FrontendEditor.
	 *
	 * Don't use or rely on it yet, as this solution still
	 * is to be discussed.
	 *
	 * @todo
	 * @type {String}
	 */
	workspaceName: null,

	/**
	 * Name of the currently selected site
	 *
	 * @type {string}
	 */
	siteName: null,

	/**
	 * Node path of the currently selected site
	 *
	 * @type {string}
	 */
	siteNodePath: null,

	/**
	 * The current TYPO3 version number
	 *
	 * This setting is set by a small script in the Backend
	 * Fluid template and is used by the FrontendEditor.
	 *
	 * Don't use or rely on it yet, as this solution still
	 * is to be discussed.
	 *
	 * @type {String}
	 */
	version: null
}