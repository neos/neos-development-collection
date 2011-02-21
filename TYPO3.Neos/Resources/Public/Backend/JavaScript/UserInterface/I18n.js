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
				cancel: 'Cancel',
				content: 'Content',
				couldNotExtraBaseURI: 'Base URI could not be extracted',
				currentlyLoading: 'Loading...',
				currentlySaving: 'Saving...',
				createPage: 'Create page',
				deletePage: 'Delete page',
				dropContentHere: 'Drop content here',
				edit: 'Edit',
				errorOccurred: 'An Error occurred',
				enterSomeContent: 'enter some content here',
				floatingMenuTabAction: 'Actions',
				layout: 'Layout',
				loading: 'Loading',
				logout: 'logout',
				management: 'Management',
				nodeName: 'Node name',
				page: 'Page',
				pageDeleteConfirm: 'Are you sure you want to delete this page? Any content on this page will be lost.',
				pageProperties: 'Page properties',
				pageTitle: 'Page title',
				pageTree: 'Page Tree',
				'PhoenixDemoTypo3Org:Registration': 'Registration form',
				publishAll: 'Publish all',
				publishSelected: 'Publish selected',
				report: 'Report',
				system: 'System',
				'Twitter:LatestTweets': 'Latest tweets',
				'TYPO3:Section': 'Section',
				'TYPO3:Text': 'Text',
				unknownErrorOccurred: 'An unknown error occurred',
				unpublishedContentDescription: 'The listed content is currently only visible in your personal workspace and can be published to the live website.',
				updatePage: 'Update page',
				valueDoesNotMatchPattern: 'The given subject did not match the pattern.',
				welcome: 'Welcome',
				workspaceHasNoUnpublishedContent: 'The current workspace does not contain any unpublished content.',
				workspaceOverview: 'Workspace overview'
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
