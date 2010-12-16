Ext.namespace('F3.TYPO3.Content.AlohaConnector');

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
 * @class F3.TYPO3.Content.AlohaConnector
 *
 * TYPO3 to Aloha connector plugin<br />
 * <br />
 * This plugin tracks content changes by the aloha editor and communicates this
 * information to the TYPO3 backend.
 * This class does NOT run inside the backend, but is included in the Frontend
 * iframe.
 *
 * @namespace F3.TYPO3.Content
 * @extends GENTICS.Aloha.Plugin
 * @singleton
 */
F3.TYPO3.Content.AlohaConnector = Ext.apply(
	new GENTICS.Aloha.Plugin('F3.TYPO3.Content.AlohaConnector'),
	{
		/**
		 * Configure the available languages
		 *
		 * @type {Array}
		 */
		languages: [],

		/**
		 * init the Aloha connector for TYPO3
		 *
		 * @return {void}
		 * @private
		 */
		init: function() {
			this._subscribeToAlohaEvents();
			this._addInsertNewSectionButton();
		},

		/**
		 * subscribe to the aloha content edit events
		 *
		 * @return {void}
		 * @private
		 */
		_subscribeToAlohaEvents: function() {
			GENTICS.Aloha.EventRegistry.subscribe(
				GENTICS.Aloha,
				'editableCreated',
				this._onEditableCreated
			);

			GENTICS.Aloha.EventRegistry.subscribe(
				GENTICS.Aloha,
				'editableActivated',
				this._onEditableActivated
			);

			GENTICS.Aloha.EventRegistry.subscribe(
				GENTICS.Aloha,
				'editableDeactivated',
				this._onEditableDeactivated
			);

			Ext.EventManager.on(
				window,
				'beforeunload',
				this._onBeforeunload,
				this
			);

			Ext.EventManager.on(
				window,
				'unload',
				this._onUnload,
				this
			);
		},

		/**
		 * On Aloha Event: "onEditableCreated"<br />
		 *
		 * The scope of "this" is GENTICS.Aloha
		 *
		 * @param {Object} event Event object
		 * @param {GENTICS.Aloha.editable} editable current aloha Editable object
		 * @return {void}
		 * @private
		 */
		_onEditableCreated: function(event, editable) {
		},

		/**
		 * On Aloha Event: "onEditableActivated"<br />
		 *
		 * The scope of "this" is GENTICS.Aloha
		 *
		 * @param {Object} event Event object
		 * @param {GENTICS.Aloha.editable} editable current aloha Editable object
		 * @return {void}
		 * @private
		 */
		_onEditableActivated: function(event, editable) {
		},

		/**
		 * On Aloha Event: "onEditableDeactivated"<br />
		 *
		 * The scope of "this" is GENTICS.Aloha
		 *
		 * @param {Object} event Event object
		 * @param {GENTICS.Aloha.editable} editable current aloha Editable object
		 * @return {void}
		 * @private
		 */
		_onEditableDeactivated: function(event, editable) {
			F3.TYPO3.Content.AlohaConnector._saveChanges(editable.editable);
		},

		/**
		 * On browser window event: "beforeunload"
		 *
		 * @return {void}
		 * @private
		 */
		_onBeforeunload: function() {
			// TODO check if there is something to save before closing the tab /window
			this._checkForUnsavedChanges();
		},

		/**
		 * On browser window event: "unload"
		 *
		 * @return {void}
		 * @private
		 */
		_onUnload: function() {
			// TODO check if there is something to save before closing the tab /window
		},

		/**
		 * Checks for unsaved content,
		 * the user can confirm if he wants to save this content
		 *
		 * @return {Boolean} TRUE if there are unsaved changes, FALSE otherwise.
		 * @private
		 */
		_checkForUnsavedChanges: function() {
			// check if something needs top be saved
			for (var i in GENTICS.Aloha.editables) {
				if (GENTICS.Aloha.editables[i].isModified) {
					if (GENTICS.Aloha.editables[i].isModified()) {
						// if an editable has been modified, the user can confirm if he wants the page to be saved
						if (true || confirm('Saved unsaved content?')) {
							// TODO Save unsaved content here
							return false;
						}
						return true;
					}
				}
			}
		},

		/**
		 * Fires Event which in the scope of the TYPO3 Backend
		 * with the information which should be persist by the Backend
		 *
		 * @param {GENTICS.Aloha.Editable} editable the editable to save
		 * @return {void}
		 * @private
		 */
		_saveChanges: function(editable) {
			var currentContentElement = editable.obj.parents('*[data-nodepath]').first();

			var nodePath = currentContentElement.attr('data-nodepath');
			var workspaceName = currentContentElement.attr('data-workspacename');
			var data = {};
			data.__context = {
				workspaceName: workspaceName,
				nodePath: nodePath
			};
			data.properties = {};

			currentContentElement.find('*[data-property]').each(function(index, element) {
				data.properties[element.getAttribute('data-property')] = element.innerHTML;
			});

			if (window.parent.F3.TYPO3.Content.ContentModule !== undefined) {
				window.parent.F3.TYPO3.Content.ContentModule.fireEvent(
					'AlohaConnector.contentChanged',
					data
				);
			}
		},

		/**
		 * Adds a "insert new Section" button.
		 * when this button is pressed, a new aloha element is created.
		 *
		 * @return {void}
		 * @private
		 * @todo implement so that it really works.
		 */
		_addInsertNewSectionButton: function() {
			var button = new GENTICS.Aloha.ui.Button({
				'label' : 'Add section',
				'size' : 'small',
				'onclick' : function() {
					var newHtmlElement = Ext.DomHelper.insertAfter(GENTICS.Aloha.activeEditable.obj[0], '<div class="f3-typo3-editable"><div contenteditable="false"><h2 contenteditable="true">[headline]</h2><div contenteditable="true">[content]</div></div></div>');
					jQuery(newHtmlElement).aloha();
				},
				'tooltip' : 'tooltip'
			});

			GENTICS.Aloha.FloatingMenu.addButton(
				'GENTICS.Aloha.continuoustext',
				button,
				GENTICS.Aloha.i18n(GENTICS.Aloha, 'floatingmenu.tab.insert'),
				4
			);
		}
	}
);