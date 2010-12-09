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

	_alohaActivated: false,

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
		if (!this._alohaActivated) {
			jQuery('.f3-typo3-editable').aloha();
			Ext.getBody().removeClass('f3-typo3-editmode-active');
			this._alohaActivated = true;
		}
	},

	/**
	 * Add the overlay for the not editable areas.
	 *
	 * @return {void}
	 */
	overlayUneditableAreas: function() {
		Ext.each(Ext.query('.f3-typo3-notEditable'), function(element) {
			var offset, createdOverlay;
			element = Ext.get(element);
			offset = element.getXY();
			createdOverlay = Ext.DomHelper.append(window.document.body, {
				cls: 'f3-typo3-notEditable-visible',
				style: 'position: absolute; left: ' + offset[0] + 'px; top: '+offset[1]+'px;width:'+element.getComputedWidth()+'px;height:'+element.getComputedHeight()+'px'
			});

			Ext.get(createdOverlay).on('click', this._disableAloha, this);
		}, this);
		Ext.getBody().addClass('f3-typo3-editmode-active');
	},
	_disableAloha: function() {
		while (GENTICS.Aloha.editables.length > 0) {
			GENTICS.Aloha.editables[0].destroy();
		}
		GENTICS.Aloha.FloatingMenu.obj.hide();
		GENTICS.Aloha.FloatingMenu.shadow.hide();
		Ext.getBody().addClass('f3-typo3-editmode-active');

	},
	/**
	 * Disable editing of the current page.
	 *
	 * @return {void}
	 */
	disableEditing: function() {
		jQuery('.f3-typo3-notEditable-visible').remove();
		this._disableAloha();
		Ext.getBody().removeClass('f3-typo3-editmode-active');
	}
};

Ext.onReady(function() {
	F3.TYPO3.Content.AlohaConnector.AlohaInitializer.initialize();
});