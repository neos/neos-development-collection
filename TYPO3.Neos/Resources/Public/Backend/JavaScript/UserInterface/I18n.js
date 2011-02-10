Ext.ns('F3.TYPO3.UserInterface');

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
 * @class
 *
 * Object used to store and fetch localized strings
 * based on package / key combination
 *
 * @namespace F3.TYPO3.UserInterface
 * @singleton
 */
F3.TYPO3.UserInterface.I18n = {

	/**
	 * @var {object}
	 * @private
	 */
	_data: {},

	/**
	 * @var {boolean}
	 * @private
	 */
	_initialized: false,

	/**
	 * @return {void}
	 */
	initialize: function() {
		this._data = {
			TYPO3: {
				pageProperties: 'Page properties',
				createPage: 'Create page',
				deletePage: 'Delete page',
				page: 'Page',
				pageTitle: 'Page title',
				nodeName: 'Node name',
				cancel: 'Cancel',
				updatePage: 'Update page',
				pageDeleteConfirm: 'Are you sure you want to delete this page? Any content on this page will be lost.',
				edit: 'Edit',
				content: 'Content',
				errorOccurred: 'An Error occurred',
				unknownErrorOccurred: 'An unknown error occurred',
				dropContentHere: 'Drop content here'
			}
		}
	},

	/**
	 * Get the localized version of the key if found
	 *
	 * @param {string} package the package to which the key belongs
	 * @param {string} key the key which should be translated
	 * @return {string} the localized string, or the key if no translation found
	 */
	get: function(package, key) {
		if (!this._initialized) {
			this.initialize();
		}

		if (this._data[package] && this._data[package][key]) {
			return this._data[package][key];
		}

		return key;
	}

};