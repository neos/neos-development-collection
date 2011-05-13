Ext.ns('F3.TYPO3.Module.Content.EditorFrontend.Aloha');

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
 * @class F3.TYPO3.Module.Content.EditorFrontend.Aloha.Initializer
 *
 * Initialize Aloha editor in the EditorFrontend
 *
 * @namespace F3.TYPO3.Module.Content.EditorFrontend.Aloha
 * @singleton
 */
F3.TYPO3.Module.Content.EditorFrontend.Aloha.Initializer = {

	/**
	 * Is aloha activated right now?
	 * @var {Boolean}
	 * @private
	 */
	_alohaEnabled: false,

	/**
	 * Task which checks for modifications.
	 * @private
	 */
	_checkModificationsTask: null,

	/**
	 * Initializer, called on page load. Is used to register event
	 * listeners on the core.
	 *
	 * @param {F3.TYPO3.Module.Content.EditorFrontend.Core} core
	 * @return {void}
	 */
	initialize: function(core) {
		core.on('enableEditingMode', this._enableAloha, this);
		core.on('disableEditingMode', this._disableAloha, this);
		core.on('loadNewlyCreatedContentElement', this._onNewlyCreatedContentElement, this);
	},

	/**
	 * After a new content element has been created, we need to enable
	 * Aloha.
	 *
	 * @param {DOMElement} newContentElement
	 * @private
	 * @return {void}
	 */
	_onNewlyCreatedContentElement: function(newContentElement) {
		if (this._alohaEnabled) {
			if(jQuery(newContentElement).is('.f3-typo3-contentelement-aloha')) {
				jQuery(newContentElement).vieSemanticAloha();
			}
		}
	},

	/**
	 * Enable aloha
	 *
	 * @param {DOMElement} target
	 * @return {void}
	 * @private
	 */
	_enableAloha: function(target) {
		if (!this._alohaEnabled) {
			this._alohaEnabled = true;

				// Select all contentelements and build models for that
			jQuery('.f3-typo3-contentelement-aloha').vieSemanticAloha();

				// Explicitly activate editable for the clicked element (double click selection hack)
			if (target) {
				var editableElement = Ext.fly(target).findParent('.f3-typo3-editable');
				if (editableElement && editableElement.id) {
					GENTICS.Aloha.getEditableById(editableElement.id).activate();
				}
			}

				// Force update of selection after activation
			window.setTimeout(function() {
				GENTICS.Aloha.Selection.updateSelection();
				var range = GENTICS.Aloha.Selection.getRangeObject();
				if (range.select) {
					range.endOffset = range.startOffset += Math.floor((range.endOffset - range.startOffset) / 2);
					range.select();
					GENTICS.Aloha.Selection.updateSelection();
				}
			}, 10);
		}
	},

	/**
	 * Disable aloha
	 *
	 * @return {void}
	 * @private
	 */
	_disableAloha: function() {
		if (this._alohaEnabled) {
			if (this._checkModificationsTask) {
				Ext.TaskMgr.stop(this._checkModificationsTask);
			}
			jQuery('.f3-typo3-editable').mahalo();
			GENTICS.Aloha.FloatingMenu.extTabPanel.hide();
			GENTICS.Aloha.FloatingMenu.shadow.hide();

			this._alohaEnabled = false;
		}
	}
};

F3.TYPO3.Module.Content.EditorFrontend.Core.registerModule(F3.TYPO3.Module.Content.EditorFrontend.Aloha.Initializer);