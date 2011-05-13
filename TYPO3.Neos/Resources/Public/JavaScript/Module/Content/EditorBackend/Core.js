Ext.ns('F3.TYPO3.Module.Content.EditorBackend');

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
 * @class F3.TYPO3.Module.Content.EditorBackend.Core
 *
 * This class is the main API between the EditorFrontend and the TYPO3
 * PHP backend
 *
 * @todo: actually create this API
 *
 * @namespace F3.TYPO3.Module.Content.EditorBackend
 * @extends Ext.util.Observable
 * @singleton
 */
F3.TYPO3.Module.Content.EditorBackend.Core = Ext.apply(new Ext.util.Observable(), {

	/**
	 * Initializer. Called on Ext.onReady().
	 *
	 * @return {void}
	 */
	initialize: function() {
	}

});

Ext.onReady(function() {
	F3.TYPO3.Module.Content.EditorBackend.Core.initialize();
});