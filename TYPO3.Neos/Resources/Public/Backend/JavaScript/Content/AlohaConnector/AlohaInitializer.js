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
	 * Is aloha activated right now?
	 * @var {Boolean}
	 * @private
	 */
	_alohaEnabled: false,

	/**
	 * Main entry point for the initializer, called on load of the page.
	 *
	 * @return {void}
	 */
	initialize: function() {
		if (window.parent.F3.TYPO3.Content.ContentModule !== undefined) {
			this._contentModule = window.parent.F3.TYPO3.Content.ContentModule;
			GENTICS.Aloha.EventRegistry.subscribe(
					GENTICS.Aloha,
					"editableCreated",
					function (event, editable) {
						editable.obj.unbind('keyup');
						editable.disable();
						editable.blur(); // TODO: does not fully work yet.
						GENTICS.Aloha.FloatingMenu.obj.hide();
						GENTICS.Aloha.FloatingMenu.shadow.hide();
						F3.TYPO3.Content.AlohaConnector.AlohaInitializer._alohaEnabled = false;
					}
			);
			jQuery('.f3-typo3-editable').aloha();

			window.addEventListener('dblclick', this._onDblClick.createDelegate(this), true);
			if (this._contentModule.isEditing()) {
				this.enableEditing();
			}
			Ext.getBody().on('keydown', this._onKeyDown, this);
		}
	},

	/**
	 * Double click handler. On first double click, we activate the editing mode.
	 * On second double click, we activate aloha.
	 *
	 * @param {DOMEvent} event the DOM event
	 * @return {void}
	 * @private
	 */
	_onDblClick: function(event) {
		if (!this._contentModule.isEditing()) {
			event.preventDefault();
			event.stopPropagation();
			this._contentModule.enableEditing();
		}
	},

	/**
	 * On key down
	 *
	 * @param {DOMEvent} event the DOM event
	 * @return {void}
	 * @private
	 */
	_onKeyDown: function(event) {
		if (event.keyCode == 27) {
			this._contentModule.disableEditing();
		}
	},

	/**
	 * Enable the editing functionality.
	 *
	 * @return {void}
	 */
	enableEditing: function() {
		this._overlayUneditableAreas();
		this._enableAloha();
	},

	/**
	 * Disable editing of the current page.
	 *
	 * @return {void}
	 */
	disableEditing: function() {
		this._removeUneditableAreas();
		this._disableAloha();
	},

	/**
	 * Enable aloha
	 *
	 * @return {void}
	 * @private
	 */
	_enableAloha: function() {
		if (!this._alohaEnabled) {
			for (var i=0; i < GENTICS.Aloha.editables.length; i++) {
				GENTICS.Aloha.editables[i].enable();
			}
			GENTICS.Aloha.FloatingMenu.obj.show();
			GENTICS.Aloha.FloatingMenu.shadow.show();
			this._alohaEnabled = true;
		}
	},

	/**
	 * Disable aloha
	 *
	 * @return {void}
	 * @private
	 */
	_disableAloha: function() {
		for (var i=0; i < GENTICS.Aloha.editables.length; i++) {
			GENTICS.Aloha.editables[i].disable();
			GENTICS.Aloha.editables[i].blur();
		}
		GENTICS.Aloha.FloatingMenu.obj.hide();
		GENTICS.Aloha.FloatingMenu.shadow.hide();
		this._alohaEnabled = false;
	},

	/**
	 * Add the overlay for the not editable areas.
	 *
	 * @return {void}
	 * @private
	 */
	_overlayUneditableAreas: function() {
		Ext.each(Ext.query('.f3-typo3-notEditable'), function(element) {
			this._createOverlayForElement(element, 'f3-typo3-notEditable-visible');
		}, this);
	},

	/**
	 * Remove the overlays for uneditable areas.
	 *
	 * @return {void}
	 * @private
	 */
	_removeUneditableAreas: function() {
		jQuery('.f3-typo3-notEditable-visible').remove();
	},

	/**
	 * Helper method: Create an overlay div appended to the body area, which
	 * will exactly overlay the given element, and return the overlay div.
	 *
	 * @param {DOMElement} element the element which should be overlaid
	 * @param {String} cssClass the css class for the newly created overlay
	 * @return {DOMElement} the newly created element
	 * @private
	 */
	_createOverlayForElement: function(element, cssClass) {
		var offset;
		element = Ext.get(element);
		offset = element.getXY();
		return Ext.DomHelper.append(window.document.body, {
			cls: cssClass,
			style: 'position: absolute; left: ' + offset[0] + 'px; top: '+offset[1]+'px;width:'+element.getComputedWidth()+'px;height:'+element.getComputedHeight()+'px'
		});
	}
};

Ext.onReady(function() {
	F3.TYPO3.Content.AlohaConnector.AlohaInitializer.initialize();
});