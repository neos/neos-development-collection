Ext.ns('F3.TYPO3.Content.ContentEditorFrontend.Html');

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
 * @class F3.TYPO3.Content.ContentEditorFrontend.Html.Initializer
 *
 * Initialize Html editor in the ContentEditorFrontend
 *
 * @namespace F3.TYPO3.Content.ContentEditorFrontend.Html
 * @singleton
 */
F3.TYPO3.Content.ContentEditorFrontend.Html.Initializer = Ext.apply(F3.TYPO3.Content.ContentEditorFrontend.AbstractInitializer, {

	/**
	 * Loads the editor after the page load.
	 *
	 * @return {void}
	 * @private
	 */
	_loadOnStartup: function() {
		var scope = this;

		Ext.select('.f3-typo3-contentelement-html', true).on('dblclick', function(event, element) {
			element = Ext.get(element).findParent('.f3-typo3-contentelement-html', 10, true);
			new F3.TYPO3.Content.ContentEditorFrontend.Html.Editor(element);
		}, this);
	}

});

F3.TYPO3.Content.ContentEditorFrontend.Core.registerModule(F3.TYPO3.Content.ContentEditorFrontend.Html.Initializer);