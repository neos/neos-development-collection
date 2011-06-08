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
	 * Initializer, called on page load. Is used to register event
	 * listeners on the core.
	 *
	 * @param {F3.TYPO3.Module.Content.EditorFrontend.Core} core
	 * @return {void}
	 */
	initialize: function(core) {
		var scope = this;

		core.on('enableEditingMode', this._enableAloha, this);
		core.on('disableEditingMode', this._disableAloha, this);
		core.on('loadNewlyCreatedContentElement', this._onNewlyCreatedContentElement, this);

		if(jQuery('body').hasClass('alohacoreloaded')) {
			scope._bindAlohaEventsAndOverrideLocalization();
		} else {
			jQuery('body').bind('alohacoreloaded', function() {
				scope._bindAlohaEventsAndOverrideLocalization();
			});
		}
	},

	/**
	 * Bind Aloha events and override localization
	 *
	 * @return {void}
	 * @private
	 */
	_bindAlohaEventsAndOverrideLocalization: function() {
		Aloha.bind("aloha-editable-deactivated", this._saveChanges.createDelegate(this));
		Aloha.bind("aloha-smart-content-changed", this._saveChanges.createDelegate(this));

		this._overrideI18nLocalization();
	},

	/**
	 * Override the i18n method of Aloha so localization is primarily based
	 * on the TYPO3 I18n class, and Aloha dictionary files are just used
	 * as fallback
	 *
	 * @return {string} the localized string
	 * @private
	 */
	_overrideI18nLocalization: function() {
		var alohaI18n = Aloha.i18n;
		Aloha.i18n = function (component, key, replacements) {
			var localizedString = F3.TYPO3.Module.Content.EditorFrontend.Core.I18n.get('TYPO3', key);
			if (localizedString === key) {
				return alohaI18n.call(this, component, key, replacements);
			}
			return localizedString;
		}
	},

	/**
	 * Fires Event which in the scope of the TYPO3 Backend
	 * with the information which should be persisted by the Backend
	 *
	 * @return {void}
	 * @private
	 */
	_saveChanges: function() {
		if (VIE && VIE.ContainerManager && VIE.ContainerManager.instances) {
			jQuery.each(VIE.ContainerManager.instances, function(index, objectInstance) {
				if (typeof objectInstance.editables !== 'undefined') {
					if (VIE.AlohaEditable.refreshFromEditables(objectInstance)) {
						F3.TYPO3.Module.Content.EditorFrontend.Core.fireEvent('modifiedContent');
						objectInstance.save();
						jQuery.each(objectInstance.editables, function() {
							this.setUnmodified();
						});
					}
				}
			});
		}
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
	 * @param {DOMEvent} event
	 * @return {void}
	 * @private
	 */
	_enableAloha: function(event) {
		var scope = this;
		if(!jQuery('body').hasClass('alohacoreloaded')) {
			jQuery('body').bind('alohacoreloaded', function() {
				scope._enableAloha(event);
			});
			return;
		}
		if (!this._alohaEnabled) {
			this._alohaEnabled = true;

				// Select all contentelements and build models for that
			jQuery('.f3-typo3-contentelement-aloha').vieSemanticAloha();

				// Explicitly activate editable for the clicked element (double click selection hack)
			if (event && event.target) {
				window.setTimeout(function() {
					var editableElement = Ext.fly(event.target).findParent('.f3-typo3-editable');
					if (editableElement && editableElement.id) {
						Aloha.getEditableById(editableElement.id).activate();
						Aloha.Selection.updateSelection(event);

						// Now, collapse the range.
						window.setTimeout(function() {
							var range = Aloha.Selection.getRangeObject();
							if (range.select) {
								range.endOffset = range.startOffset += Math.floor((range.endOffset - range.startOffset) / 2);
								range.select();
							}
						}, 10);
					}
				}, 10);
			}
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

			jQuery('.f3-typo3-editable').mahalo();
			Aloha.FloatingMenu.extTabPanel.hide();
			Aloha.FloatingMenu.shadow.hide();

			this._alohaEnabled = false;
		}
	}
};

F3.TYPO3.Module.Content.EditorFrontend.Core.registerModule(F3.TYPO3.Module.Content.EditorFrontend.Aloha.Initializer);