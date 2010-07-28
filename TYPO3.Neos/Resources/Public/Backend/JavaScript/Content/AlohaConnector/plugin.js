/*                                                                        *
 * This script belongs to the TYPO3 project.                              *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License as published by the *
 * Free Software Foundation, either version 3 of the License, or (at your *
 * option) any later version.                                             *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Lesser       *
 * General Public License for more details.                               *
 *                                                                        *
 * You should have received a copy of the GNU Lesser General Public       *
 * License along with the script.                                         *
 * If not, see http://www.gnu.org/licenses/lgpl.html                      *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */
Ext.namespace('F3.TYPO3.Content.AlohaConnector');
/**
 * @class F3.TYPO3.Content.AlohaConnector
 * TYPO3 to Aloha connector plugin<br>
 * <br>
 * This plugin tracks content changes by the aloha editor and communicat this<br>
 * informations to the TYPO3 backend<br>
 *
 * @namespace F3.TYPO3.Content
 * @extends GENTICS.Aloha.Plugin
 * @author Nils Dehl <nils.dehl@dkd.de>
 * @version $Id$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
F3.TYPO3.Content.AlohaConnector = Ext.apply(
	new GENTICS.Aloha.Plugin(
		'F3.TYPO3.Content.AlohaConnector'
	),
	{

		/**
		 * Configure the available languages
		 *
		 * @type array
		 */
		languages: ['en', 'de'],

		/**
		 * init the Aloha connector for TYPO3
		 *
		 * @author Nils Dehl <nils.dehl@dkd.de>
		 * @return {void}
		 * @private
		 */
		init: function() {

			// subscribe event listener
			this.subscribeToAlohaEvents();

			// add a "insert a new section" button which adds a new section.
			this.addInsertNewSectionButton();
		},

		/**
		 * subscribe to the aloha content edit events
		 *
		 * @author Nils Dehl <nils.dehl@dkd.de>
		 * @return {void}
		 * @private
		 */
		subscribeToAlohaEvents: function() {


			GENTICS.Aloha.EventRegistry.subscribe(
				GENTICS.Aloha,
				'editableCreated',
				this.onEditableCreated
			);

			GENTICS.Aloha.EventRegistry.subscribe(
				GENTICS.Aloha,
				'editableActivated',
				this.onEditableActivated
			);

			GENTICS.Aloha.EventRegistry.subscribe(
				GENTICS.Aloha,
				'editableDeactivated',
				this.onEditableDeactivated
			);

			Ext.EventManager.on(
				window,
				'beforeunload',
				this.onBeforeunload,
				this
			);

			Ext.EventManager.on(
				window,
				'unload',
				this.onUnload,
				this
			);

		},

		/**
		 * On Aloha Event: "onEditableCreated"
		 *
		 * @author Nils Dehl <nils.dehl@dkd.de>
		 * @return {void}
		 * @private
		 */
		onEditableCreated: function(event, editable) {
			console.log('onEditableCreated', arguments);

		},

		/**
		 * On Aloha Event: "onEditableActivated"
		 *
		 * @author Nils Dehl <nils.dehl@dkd.de>
		 * @return {void}
		 * @private
		 */
		onEditableActivated: function(event, editable) {
			console.log('onEditableActivated', arguments);
		},

		/**
		 * On Aloha Event: "onEditableDeactivated"
		 *
		 * @author Nils Dehl <nils.dehl@dkd.de>
		 * @return {void}
		 * @private
		 */
		onEditableDeactivated: function(event, editable) {
			console.log('onEditableDeactivated', arguments);

			// function call must be with F3.TYPO3.Content.AlohaConnector
			// because the scope of this is GENTICS.Aloha
			F3.TYPO3.Content.AlohaConnector.saveChanges(editable.editable);
		},

		/**
		 * On browser window event: "beforeunload"
		 *
		 * @author Nils Dehl <nils.dehl@dkd.de>
		 * @return {void}
		 * @private
		 */
		onBeforeunload: function() {
			//alert('cross-exit tab click AND cross-exit browser click');
			// TODO check if there is something to save before closeing the tab /window
			this.checkForUnsavedChanges();
		},

		/**
		 * On browser window event: "unload"
		 *
		 * @author Nils Dehl <nils.dehl@dkd.de>
		 * @return {void}
		 * @private
		 */
		onUnload: function() {
			//alert('cross-exit tab click');
			// TODO check if there is something to save before closeing the tab /window
		},

		/**
		 * Checks for unsaved content,
		 * the user can confirm if he wants to save this content
		 *
		 * @author Nils Dehl <nils.dehl@dkd.de>
		 * @return {Boolean}
		 * @private
		 */
		checkForUnsavedChanges: function() {
			// check if something needs top be saved
			for (var i in GENTICS.Aloha.editables) {
				if (GENTICS.Aloha.editables[i].isModified) {
					if (GENTICS.Aloha.editables[i].isModified()) {
						// if an editable has been modified, the user can confirm if he wants the page to be saved
						if (true || confirm('Saved unsaved content?')) {
console.log(i,GENTICS.Aloha.editables[i]);
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
		 * @author Nils Dehl <nils.dehl@dkd.de>
		 * @author Sebastian Kurfürst <sebastian@typo3.org>
		 * @param {Object} editable
		 * @return {void}
		 * @private
		 */
		saveChanges: function(editable) {
			var data = Ext.decode(editable.obj[0].getAttribute('data-identity'));

			editable.obj.find('*[data-property]').each(function(index, element) {
				data[element.getAttribute('data-property')] = element.innerHTML
			});

			if (typeof window.parent.F3.TYPO3.Application !== 'undefined') {
				/**
				 * @event F3.TYPO3.Application.AlohaConnector.contentChanged
				 * fires when there is changed content which should persist by the TYPO3 backend.<br>
				 * this event is fired in the following <b>scope:</b> <i>window.parent.F3.TYPO3.Application</i>
				 * @param {object} data <ul>
				 * <li><b>identity:</b> Identity of the element</li>
				 * <li><b>html:</b> the html content</li>
				 * </ul>
				 */
				window.parent.F3.TYPO3.Application.fireEvent(
					'F3.TYPO3.Application.AlohaConnector.contentChanged',
					data
				);
			}
		},

		/**
		 * Adds a "insert new Section" button.
		 * when this button is pressed, a new aloha element is created.
		 *
		 * @author Sebastian Kurfürst <sebastian@typo3.org>
		 * @return {void}
		 * @private
		 */
		addInsertNewSectionButton: function() {
			var button = new GENTICS.Aloha.ui.Button({
				'label' : 'Add section',
				'size' : 'small',
				'onclick' : function() {
					console.log(GENTICS.Aloha.activeEditable,  GENTICS.Aloha.activeEditable.obj[0]);
					var newHtmlElement = Ext.DomHelper.insertAfter(GENTICS.Aloha.activeEditable.obj[0], '<div class="f3-typo3-editable"><div contenteditable="false"><h2 contenteditable="true">[headline]</h2><div contenteditable="true">[content]</div></div></div>');
					console.log(newHtmlElement);
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