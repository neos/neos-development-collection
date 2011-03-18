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
F3.TYPO3.Content.ContentEditorFrontend.Html.Initializer = Ext.apply({}, {

	/**
	 * Loads the editor after the page load.
	 *
	 * @return {void}
	 * @private
	 */
	_loadOnStartup: function() {
		var scope = this;
		Ext.each(Ext.query('.f3-typo3-contentelement-html'), function(element) {
			scope._attachEventListeners(element);
		});
	},

	/**
	 * Called when the loadNewlyCreatedContentElement event is thrown. Adds the editor
	 * plugin frontend to the new element
	 *
	 * @param {DOMElement} newContentElement
	 * @return {void}
	 */
	afterLoadNewContentElementHandler: function(newContentElement) {
		if(Ext.get(newContentElement).hasClass('f3-typo3-contentelement-html')) {
			Ext.get(newContentElement).update(window.parent.F3.TYPO3.UserInterface.I18n.get('TYPO3', 'enterSomeContent'));
			this._attachEventListeners(Ext.get(newContentElement));
			// @todo: make a new instance now and on dblClick? Find a way to optimize this later on
			new F3.TYPO3.Content.ContentEditorFrontend.Html.Plugin(Ext.get(newContentElement));
		}
	},

	/**
	 * Atach the event listeners to an instance of the HTML plugin
	 *
	 * @param {Ext.Element} element
	 * @return {void}
	 */
	_attachEventListeners: function(element) {
		element = Ext.get(element).findParent('.f3-typo3-contentelement-html', 10, true);
		element.on('dblclick', function(event, element) {
			new F3.TYPO3.Content.ContentEditorFrontend.Html.Plugin(Ext.get(element));
		}, this);

	}

}, F3.TYPO3.Content.ContentEditorFrontend.AbstractInitializer);

F3.TYPO3.Content.ContentEditorFrontend.Core.registerModule(F3.TYPO3.Content.ContentEditorFrontend.Html.Initializer);