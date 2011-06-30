Ext.ns('TYPO3.TYPO3.Module.Content.EditorFrontend.Html');

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
 * @class TYPO3.TYPO3.Module.Content.EditorFrontend.Html.Initializer
 *
 * Initialize Html editor in the EditorFrontend
 *
 * @namespace TYPO3.TYPO3.Module.Content.EditorFrontend.Html
 * @singleton
 */
TYPO3.TYPO3.Module.Content.EditorFrontend.Html.Initializer = Ext.apply({}, {

	/**
	 * HTML content of the placeholder
	 *
	 * @var {string}
	 * @private
	 */
	_placeholderContent: null,

	/**
	 * Initializer, called on page load. Is used to register event
	 * listeners on the core.
	 *
	 * @param {TYPO3.TYPO3.Module.Content.EditorFrontend.Core} core
	 * @return {void}
	 */
	initialize: function(core) {
		this._placeholderContent = '<span class="typo3-typo3-html-placeholder">[' + core.I18n.get('TYPO3.TYPO3', 'enterSomeContent') + ']</span>';

		core.on('enableEditingMode', this._registerEventHandlers, this);
		core.on('disableEditingMode', this._unregisterEventHandlers, this);
		core.on('loadNewlyCreatedContentElement', this._onNewlyCreatedContentElement, this);
	},

	/**
	 * Attach the event listeners to an instance of the HTML plugin
	 *
	 * @return {void}
	 */
	_registerEventHandlers: function() {
		var scope = this;

		jQuery('.typo3-typo3-contentelement-html').each(function(index, element) {
			scope.insertPlaceholderIfElementIsEmpty(element);
		});
		jQuery('.typo3-typo3-contentelement-html').live('dblclick', function() {
			new TYPO3.TYPO3.Module.Content.EditorFrontend.Html.Plugin(Ext.get(this));
		});
	},

	/**
	 * After a new content element has been created, we need to add the placeholder
	 *
	 * @param {DOMElement} newContentElement
	 * @private
	 * @return {void}
	 */
	_onNewlyCreatedContentElement: function(newContentElement) {
		if (jQuery(newContentElement).is('.typo3-typo3-contentelement-html')) {
			newContentElement.innerHTML = this._placeholderContent;
		}
	},

	/**
	 * Unregister the event listeners on all HTML content elements / placeholders
	 *
	 * @return {void}
	 * @private
	 */
	_unregisterEventHandlers: function() {
		jQuery('.typo3-typo3-contentelement-html').die('dblclick');
		jQuery('.typo3-typo3-html-placeholder').remove();
	},

	/**
	 * Fill the content with a placeholder if the element becomes empty.
	 *
	 * @param {DOMElement} element
	 * @return {void}
	 */
	insertPlaceholderIfElementIsEmpty: function(element) {
		if (this.isEmptyHtmlContentElement(element)) {
			jQuery(element).html(this._placeholderContent);
		}
	},

	/**
	 * Check if a HTML content element is empty
	 *
	 * @param {DOMElement} element
	 * @return {boolean} True if empty
	 */
	isEmptyHtmlContentElement: function(element) {
		var contents = Ext.util.Format.trim(jQuery(element).html());
		if (!contents || contents == '' || contents == '&nbsp;' ||
				contents == '<br />' || contents == '<br>' || contents == this._placeholderContent) {
			return true;
		}
		return false;
	}

});

TYPO3.TYPO3.Module.Content.EditorFrontend.Core.registerModule(TYPO3.TYPO3.Module.Content.EditorFrontend.Html.Initializer);