Ext.ns('F3.TYPO3.Module.Content.EditorFrontend');

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
 * @class F3.TYPO3.Module.Content.EditorFrontend.UneditableAreaOverlay
 *
 * This class shows and hides the uneditable area overlays.
 *
 * @namespace F3.TYPO3.Module.Content.EditorFrontend
 * @singleton
 */
F3.TYPO3.Module.Content.EditorFrontend.UneditableAreaOverlay = {

	/**
	 * @var {boolean}
	 * @private
	 */
	_enabled: false,

	/**
	 * Initializer, called on page load. Is used to register event
	 * listeners on the core.
	 *
	 * @param {F3.TYPO3.Module.Content.EditorFrontend.Core} core
	 * @return {void}
	 */
	initialize: function(core) {
		core.on('enableEditingMode', this._overlayUneditableAreas, this);
		core.on('disableEditingMode', this._removeUneditableAreas, this);
		core.on('windowResize', function() {
			if (this._enabled) {
				this._removeUneditableAreas();
				this._overlayUneditableAreas();
			}
		}, this);
	},

	/**
	 * Add the overlay for the not editable areas.
	 *
	 * @return {void}
	 * @private
	 */
	_overlayUneditableAreas: function() {
		this._enabled = true;
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
		this._enabled = false;
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

F3.TYPO3.Module.Content.EditorFrontend.Core.registerModule(F3.TYPO3.Module.Content.EditorFrontend.UneditableAreaOverlay);