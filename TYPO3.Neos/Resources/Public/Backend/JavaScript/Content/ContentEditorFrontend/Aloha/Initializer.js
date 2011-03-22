Ext.ns('F3.TYPO3.Content.ContentEditorFrontend.Aloha');

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
 * @class F3.TYPO3.Content.ContentEditorFrontend.Aloha.Initializer
 *
 * Initialize Aloha editor in the ContentEditorFrontend
 *
 * @namespace F3.TYPO3.Content.ContentEditorFrontend.Aloha
 * @singleton
 */
F3.TYPO3.Content.ContentEditorFrontend.Aloha.Initializer = Ext.apply({}, {

	/**
	 * Is aloha activated right now?
	 * @var {Boolean}
	 * @private
	 */
	_alohaEnabled: false,

	/**
	 * Initializer, called on page load. Is used to register event
	 * listeners on the core.
	 *
	 * @param {F3.TYPO3.Content.ContentEditorFrontend.Core} core
	 * @return {void}
	 */
	initialize: function(core) {
		core.on('enableEditing', this._enableAloha, this);
		core.on('disableEditing', this._disableAloha, this);
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
			jQuery('.f3-typo3-contentelement').vieSemanticAloha();

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

				// TODO Use smart content change events!
			this.checkModificationsTask = {
				run: function() {
					if (GENTICS.Aloha.getActiveEditable() && GENTICS.Aloha.getActiveEditable().isModified()) {
						if (window.parent.F3.TYPO3.Content.ContentModule !== undefined) {
							window.parent.F3.TYPO3.Content.ContentModule.fireEvent('AlohaConnector.modifiedContent');
						}
					}
				},
				interval: 1000
			};
			Ext.TaskMgr.start(this.checkModificationsTask);
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
			if (this.checkModificationsTask) {
				Ext.TaskMgr.stop(this.checkModificationsTask);
			}

			jQuery('.f3-typo3-editable').mahalo();
			VIE.ContainerManager.cleanup();
			this._alohaEnabled = false;
		}
	}
}, F3.TYPO3.Content.ContentEditorFrontend.AbstractInitializer);
F3.TYPO3.Content.ContentEditorFrontend.Core.registerModule(F3.TYPO3.Content.ContentEditorFrontend.Aloha.Initializer);

	// Override backbone sync for node model (actual saving of nodes)
Backbone.sync = function(method, model, success, error) {
	var properties = {},
		contentContext = {
			workspaceName: model.workspaceName,
			nodePath: model.id
		};
	jQuery.each(model.attributes, function(propertyName, value) {
		if (propertyName == 'id') {
			return;
		}
			// TODO If TYPO3 supports mapping of fully qualified properties, send with namespace
		properties[propertyName.split(':', 2)[1]] = value;
	});
	window.parent.F3.TYPO3.Content.ContentModule.saveNode(contentContext, properties, function() {

	});
};

	// Add additional model properties from elements
VIE.ContainerManager.findAdditionalInstanceProperties = function(element, modelInstance) {
	modelInstance.workspaceName = jQuery(element).attr('data-workspacename');
};
