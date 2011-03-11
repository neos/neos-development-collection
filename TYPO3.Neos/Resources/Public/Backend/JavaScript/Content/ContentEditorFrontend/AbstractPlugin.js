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
 * @class F3.TYPO3.Content.ContentEditorFrontend.AbstractPlugin
 *
 * Abstract plugin class, implements basic functionality and required
 * methods for a ContentEditor plugin.
 *
 * @namespace F3.TYPO3.Content.ContentEditorFrontend
  */
F3.TYPO3.Content.ContentEditorFrontend.AbstractPlugin = {

	/**
	 * Helper function which creates a JSON structure which can be mapped
	 * to a TYPO3CR Node if used as argument for an Ext.Direct call.
	 *
	 * @param {jQuery} contentElement the Content Element container
	 * @return {Object} a JSON object with the __context set correctly.
	 * @private
	 */
	_createNodeFromContentElement: function(contentElement) {
		return F3.TYPO3.Content.ContentEditorFrontend.Core.createNode(contentElement.getAttribute('data-nodepath'), contentElement.getAttribute('data-workspacename'));
	}

};

Ext.reg('F3.TYPO3.Content.ContentEditorFrontend.AbstractPlugin', F3.TYPO3.Content.ContentEditorFrontend.AbstractPlugin);