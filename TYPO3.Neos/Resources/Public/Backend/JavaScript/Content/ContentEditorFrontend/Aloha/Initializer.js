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
F3.TYPO3.Content.ContentEditorFrontend.Aloha.Initializer = {

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
		core.on('afterPageLoad', function() {
			this._loadAlohaOnStartup();
		}, this);

		core.on('enableEditing', this._enableAloha, this);
		core.on('disableEditing', this._disableAloha, this);
	},

	/**
	 * Loads Aloha after the page load.
	 *
	 * @return {void}
	 * @private
	 */
	_loadAlohaOnStartup: function() {
		// Helper function
		var makeAlohaElementEditableAndActivateItImmediately = function(editable, event) {
			// Aloha is enabled right now, but the
			// contenteditable attribute is not set yet...
			// we set it, and by the setTimeout make sure the
			// browser refreshes its DOM.
			editable.obj.attr('contenteditable', 'true');
			window.setTimeout(function() {
				// Now, the contenteditable setting has reached the DOM
				// and we can activate the editable.
				editable.activate(event);
				// Now, we still need to throw an onChange
				// event on the selection, so that the floating
				// menu recalculates its position.
				GENTICS.Aloha.Selection.onChange(editable.obj, event);
			}, 5);
		};

		// Here, we modify the aloha editable behavior
		GENTICS.Aloha.EventRegistry.subscribe(
			GENTICS.Aloha,
			"editableCreated",
			function (event, editable) {
				// We need to re-wire the Aloha editable events a bit,
				// that's why we disable all internal event handlers
				editable.obj.unbind('mousedown');
				editable.obj.unbind('focus'); // TODO: Handle focus event as well!!!
				editable.obj.unbind('keydown');

				editable.obj.mousedown(function(event) {
					if (F3.TYPO3.Content.ContentEditorFrontend.Aloha.Initializer._alohaEnabled) {
						if (editable.isDisabled()) {
							makeAlohaElementEditableAndActivateItImmediately(editable, event);
						} else {
							// Editable is already enabled, so we want to activate it straight away
							editable.activate(event);
						}
					}
					return true;
				});

				// we only want to forward the keystrokes in case aloha
				// is enabled.
				editable.obj.keydown(function(event) {
					if (F3.TYPO3.Content.ContentEditorFrontend.Aloha.Initializer._alohaEnabled) {
						return GENTICS.Aloha.Markup.preProcessKeyStrokes(event);
					} else {
						return false;
					}
				});

				// Add new double click event listener.
				editable.obj.dblclick(function(event) {
					if (F3.TYPO3.Content.ContentEditorFrontend.Aloha.Initializer._alohaEnabled) {
						// Aloha is already enabled, so we do not need
						// to react on double click.
						return true;
					}

					// Enable the editing mode
					F3.TYPO3.Content.ContentEditorFrontend.Core.shouldEnableEditing();

					makeAlohaElementEditableAndActivateItImmediately(editable, event);
					return true;
				});
				editable.obj.attr("contentEditable", "false");
			}
		);

		jQuery('.f3-typo3-editable').aloha();
	},

	/**
	 * Enable aloha
	 *
	 * @return {void}
	 * @private
	 */
	_enableAloha: function() {
		this._alohaEnabled = true;
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
	}
};

F3.TYPO3.Content.ContentEditorFrontend.Core.registerModule(F3.TYPO3.Content.ContentEditorFrontend.Aloha.Initializer);
