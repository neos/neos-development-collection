Ext.ns('F3.TYPO3.Content.ContentEditorFrontend');

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
 * @class F3.TYPO3.Content.ContentEditorFrontend.AbstractInitializer
 *
 * Abstract initializer class. A ContentEditorFrontend initializer
 * loads a new ContentEditor into the frontend and registers it
 * in the ContentEditorFrontend.
 *
 * @namespace F3.TYPO3.Content.ContentEditorFrontend
  */
F3.TYPO3.Content.ContentEditorFrontend.AbstractInitializer = {

	/**
	 * Activated right now?
	 * @var {Boolean}
	 * @private
	 */
	_enabled: false,

	/**
	 * Initializer, called on page load. Is used to register event
	 * listeners on the core.
	 *
	 * @param {F3.TYPO3.Content.ContentEditorFrontend.Core} core
	 * @return {void}
	 */
	initialize: function(core) {
		core.on('afterPageLoad', function() {
			this._loadOnStartup();
		}, this);

		core.on('enableEditingMode', this._enable, this);
		core.on('disableEditingMode', this._disable, this);

		core.on('loadNewlyCreatedContentElement', this.afterLoadNewContentElementHandler, this);
	},

	/**
	 * Called when the loadNewlyCreatedContentElement event is thrown. Adds the editor
	 * plugin frontend to the new element
	 *
	 * @param {DOMElement} newContentElement
	 * @return {void}
	 */
	afterLoadNewContentElementHandler: function(newContentElement) {
	},

	/**
	 * Loads after the page load.
	 *
	 * @return {void}
	 * @private
	 */
	_loadOnStartup: function() {
	},

	/**
	 * Enable editor
	 *
	 * @return {void}
	 * @private
	 */
	_enable: function() {
		this._enabled = true;
	},

	/**
	 * Disable editor
	 *
	 * @return {void}
	 * @private
	 */
	_disable: function() {
		this._enabled = false;
	}

};

Ext.reg('F3.TYPO3.Content.ContentEditorFrontend.AbstractInitializer', F3.TYPO3.Content.ContentEditorFrontend.AbstractInitializer);