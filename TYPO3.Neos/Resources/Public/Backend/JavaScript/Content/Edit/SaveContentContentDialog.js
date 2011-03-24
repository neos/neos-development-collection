Ext.ns('F3.TYPO3.Content.Edit');

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
 * @class F3.TYPO3.Content.Edit.SaveContentDialog
 *
 * An empty dialog for deleting pages
 *
 * @namespace F3.TYPO3.Content.Edit
 * @extends F3.TYPO3.UserInterface.ModuleDialog
 */
F3.TYPO3.Content.Edit.SaveContentContentDialog = Ext.extend(F3.TYPO3.UserInterface.ContentDialog, {

	/**
	 * @var {F3.TYPO3.Content.WebsiteContainer}
	 */
	websiteContainer: null,

	/**
	 * Initializer
	 */
	initComponent: function() {
		var config = {
			height: 1,
			items: [],
			listeners: {
				afterrender: function() {
					this.websiteContainer.on('modeChange', this._onModeChange, this);
					this._onModeChange();
				}.createDelegate(this),
				beforeDestroy: function() {
					this.websiteContainer.removeListener('modeChange', this._onModeChange, this);
				}.createDelegate(this)
			}
		};
		Ext.apply(this, config);
		F3.TYPO3.Content.Edit.SaveContentContentDialog.superclass.initComponent.call(this);
	},
	_onModeChange: function() {
		if (this.websiteContainer.isSelectionModeEnabled()) {
			this._setModeName(F3.TYPO3.UserInterface.I18n.get('TYPO3', 'selectionMode'));
		} else if (this.websiteContainer.isEditingModeEnabled()) {
			this._setModeName(F3.TYPO3.UserInterface.I18n.get('TYPO3', 'editingMode'));
		}
	},

	/**
	 * @return {object}
	 */
	_prepareToolbarConfig: function(toolbarConfig) {
		toolbarConfig.items.push({
			xtype: 'F3.TYPO3.Components.Button',
			itemId: 'modeButton',
			text: F3.TYPO3.UserInterface.I18n.get('TYPO3', 'selectionMode'),
			scale: 'large',
			disabled: true,
			cls: 'F3-TYPO3-Components-Button-type-neutral',
			ref: 'modeButton'
		});
		toolbarConfig.items.push({
			xtype: 'F3.TYPO3.Components.Button',
			itemId: 'saveButton',
			text: F3.TYPO3.UserInterface.I18n.get('TYPO3', 'save'),
			scale: 'large',
			handler: this._onSave,
			disabled: true,
			scope: this,
			cls: 'F3-TYPO3-Components-Button-type-neutral',
			ref: 'saveButton'
		});
		return toolbarConfig;
	},

	/**
	 * Callback which is called when the save button is clicked
	 *
	 * @return {void}
	 */
	_onSave: function() {
		Ext.getCmp('F3.TYPO3.Content.WebsiteContainer').saveContent();
	},

	/**
	 * @param {String} mode name to set inside button.
	 * @private
	 */
	_setModeName: function(modeName) {
		this.panel.getTopToolbar().modeButton.setText(modeName);
	},

	activateSave: function() {
		this.panel.getTopToolbar().saveButton.enable();
	},

	startSave: function() {
		this.panel.getTopToolbar().saveButton.setText(F3.TYPO3.UserInterface.I18n.get('TYPO3', 'saving'));
	},

	finishSaving: function() {
		this.panel.getTopToolbar().saveButton.setText(F3.TYPO3.UserInterface.I18n.get('TYPO3', 'save'));
		this.panel.getTopToolbar().saveButton.disable();
	}

});
Ext.reg('F3.TYPO3.Content.Edit.SaveContentContentDialog', F3.TYPO3.Content.Edit.SaveContentContentDialog);