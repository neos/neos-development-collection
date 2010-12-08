Ext.ns("F3.TYPO3.Content.AlohaConnector");

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
 * @class F3.TYPO3.Content.AlohaConnector.AlohaInitializer
 *
 * The aloha initializer which is loaded INSIDE the iframe.
 *
 * @namespace F3.TYPO3.Content.AlohaConnector
 * @singleton
 */
F3.TYPO3.Content.AlohaConnector.AlohaInitializer = {

	/**
	 * shortcut reference to the Content Module
	 *
	 * @var {F3.TYPO3.Content.ContentModule}
	 * @private
	 */
	_contentModule: null,

	/**
	 * Main entry point for the initializer, called on load of the page.
	 *
	 * @return {void}
	 */
	initialize: function() {
		if (window.parent.F3.TYPO3.Content.ContentModule !== undefined) {
			this._contentModule = window.parent.F3.TYPO3.Content.ContentModule;

			window.addEventListener('dblclick', this._onDblClick, true);
			if (this._contentModule.isEditing()) {
				this.overlayUneditableAreas();
			} else {
				window.addEventListener('dblclick', this._onDblClick, true);
			}
		}
	},

	/**
	 * Double click handler.
	 *
	 * @return {void}
	 * @private
	 */
	_onDblClick: function(event) {
		if (F3.TYPO3.Content.AlohaConnector.AlohaInitializer._contentModule.isEditing()) {
			F3.TYPO3.Content.AlohaConnector.AlohaInitializer._shouldActivateAloha.call(F3.TYPO3.Content.AlohaConnector.AlohaInitializer, event);
		} else {
			F3.TYPO3.Content.AlohaConnector.AlohaInitializer._shouldActivateEditingMode.call(F3.TYPO3.Content.AlohaConnector.AlohaInitializer, event);
		}
	},

	/**
	 * Callback which activates the editing mode
	 *
	 * @param {DOMEvent} the DOM event
	 * @return {void}
	 * @private
	 */
	_shouldActivateEditingMode: function(event) {
		event.preventDefault();
		event.stopPropagation();

		this._contentModule.enableEditing();
	},

	/**
	 * Should activate aloha
	 *
	 * @param {DOMEvent} the DOM event
	 * @return {void}
	 * @private
	 */
	_shouldActivateAloha: function(event) {
		jQuery('.f3-typo3-editable').aloha();
	},

	/**
	 * Add the overlay for the not editable areas.
	 *
	 * @return {void}
	 */
	overlayUneditableAreas: function() {
		jQuery('.f3-typo3-notEditable').each(function(index, element) {
			element = jQuery(element);
			var offset = element.offset();
			Ext.DomHelper.append(window.document.body, {
				cls: 'f3-typo3-notEditable-visible',
				style: 'position: absolute; left: ' + offset.left + 'px; top: '+offset.top+'px;width:'+element.width()+'px;height:'+element.height()+'px'
			});
		});
	},

	/**
	 * Disable editing of the current page.
	 *
	 * @return {void}
	 */
	disableEditing: function() {
		jQuery('.f3-typo3-notEditable-visible').remove();
		jQuery('.f3-typo3-editable').mahalo();
		window.addEventListener('dblclick', this._onDblClick, true);
	}
};

Ext.onReady(function() {
	F3.TYPO3.Content.AlohaConnector.AlohaInitializer.initialize();
});