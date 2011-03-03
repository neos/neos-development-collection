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
 * This class handles the drag and drop functionality of content elements
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

		core.on('enableEditing', this._enable, this);
		core.on('disableEditing', this._disable, this);
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
	},

	/**
	 * Helper function which creates a JSON structure which can be mapped
	 * to a TYPO3CR Node if used as argument for an Ext.Direct call.
	 *
	 * @param {Ext.Element} contentElement the Content Element container
	 * @return {Object} a JSON object with the __context set correctly.
	 * @private
	 */
	_createNodeFromContentElement: function(contentElement) {
		return F3.TYPO3.Content.ContentEditorFrontend.Core.createNode(contentElement.getAttribute('data-nodepath'), contentElement.getAttribute('data-workspacename'));
	}

};

Ext.reg('F3.TYPO3.Content.ContentEditorFrontend.AbstractInitializer', F3.TYPO3.Content.ContentEditorFrontend.AbstractInitializer);