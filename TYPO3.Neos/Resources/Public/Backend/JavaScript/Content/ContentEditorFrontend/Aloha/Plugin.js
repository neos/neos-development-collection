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
 * @class F3.TYPO3.Content.ContentEditorFrontend.Aloha.Plugin
 *
 * TYPO3 to Aloha connector plugin<br />
 * <br />
 * This plugin tracks content changes by the aloha editor and communicates this
 * information to the TYPO3 backend.
 * This class does NOT run inside the backend, but is included in the Frontend
 * iframe.
 *
 * @namespace F3.TYPO3.Content.ContentEditorFrontend.Aloha
 * @extends GENTICS.Aloha.Plugin
 * @singleton
 */
F3.TYPO3.Content.ContentEditorFrontend.Aloha.Plugin = Ext.apply(
	new GENTICS.Aloha.Plugin('F3.TYPO3.Content.ContentEditorFrontend.Aloha.Plugin'),
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
				// Adds the "create content element" buttons to the 'insert' tab.
			this._addButtons(
				this.settings.contentTypes,
				'floatingmenu.tab.insert',
				this._onNewContentElementClick
			);

			jQuery('.f3-typo3-placeholder').live('click', this._onPlaceholderClick);
		},

		/**
		 * subscribe to the aloha events, like when an editable is created or deactivated
		 *
		 * @return {void}
		 * @private
		 */
		_subscribeToAlohaEvents: function() {
			GENTICS.Aloha.EventRegistry.subscribe(
				GENTICS.Aloha,
				'editableCreated',
				this._onEditableCreated.createDelegate(this)
			);

			GENTICS.Aloha.EventRegistry.subscribe(
				GENTICS.Aloha,
				'editableDeactivated',
				this._onEditableDeactivated.createDelegate(this)
			);
		},

		/**
		 * Event handler for aloha event "editable created".
		 * Adds a placeholder if the element is empty.
		 *
		 * @param {Object} event Event object
		 * @param {GENTICS.Aloha.Editable} editable the created editable
		 * @return {void}
		 * @private
		 */
		_onEditableCreated: function(event, editable) {
			this._insertPlaceholderIfEditableIsEmpty(editable);
		},

		/**
		 * On Aloha Event: "onEditableDeactivated", saves changes
		 * if the element has changed.
		 *
		 * @param {Object} event Event object
		 * @param {GENTICS.Aloha.Editable} editable the aloha Editable object which has been deactivated
		 * @return {void}
		 * @private
		 */
		_onEditableDeactivated: function(event, editable) {
			if (editable.editable.isModified()) {
				this._saveChanges(editable.editable);
				this._insertPlaceholderIfEditableIsEmpty(editable.editable);
			}
		},

		/**
		 * Fires Event which in the scope of the TYPO3 Backend
		 * with the information which should be persisted by the Backend
		 *
		 * @param {GENTICS.Aloha.Editable} editable the editable to save
		 * @return {void}
		 * @private
		 */
		_saveChanges: function(editable) {
			var currentContentElement = this._findParentContentElement(editable.obj);

			var data = this._createNodeFromContentElement(currentContentElement);
			data.properties = {};

			currentContentElement.find('*[data-property]').each(function(index, element) {
				// We are stripping trailing and leading whitespaces, as they have been added for Firefox.
				data.properties[element.getAttribute('data-property')] = Ext.util.Format.trim(element.innerHTML).replace(/^&nbsp;|&nbsp;$/g, ''); // TODO: use getContents() on the editable!
			});

			if (window.parent.F3.TYPO3.Content.ContentModule !== undefined) {
				window.parent.F3.TYPO3.Content.ContentModule.fireEvent(
					'AlohaConnector.persistChangedContent',
					data
				);
			}
		},

		/**
		 * Insert Placeholder for editable in case it is empty.
		 *
		 * @param {GENTICS.Aloha.Editable} editable if this editable is empty, a placeholder is automatically inserted.
		 * @return {void}
		 * @private
		 */
		_insertPlaceholderIfEditableIsEmpty: function(editable) {
			var contents = Ext.util.Format.trim(editable.getContents());
			if (contents == '' || contents == '&nbsp;' || contents == '<br />' || contents == '<br>') {
				editable.obj.html('<span class="f3-typo3-placeholder">[enter some content here]</span>');
			}
		},

		/**
		 * Event handler, on click of a placeholder. Removes the placeholder, and
		 * sets the cursor, so the user can directly start typing.
		 *
		 * @param {Event} event click event object
		 * @return {void}
		 * @private
		 * @todo should maybe use the Aloha functionality instead of native DOM, so that it also works in Internet Explorer.
		 */
		_onPlaceholderClick: function(event) {
			var parentObject = jQuery(event.target).parent();
			var range = document.createRange();
			parentObject.html('&nbsp;');

			range.selectNodeContents(parentObject.get(0));

			if (Ext.isGecko) {
					// Firefox hack -> Collapse the selection such that the user can start typing right away.
				range.collapse(true);
			}

			window.getSelection().addRange(range);
		},

		/**
		 * Adds buttons to the aloha editor
		 *
		 * @param {array} buttons array with objects ({label:'',name:''})
		 * @param {string} tab name of the Aloha tab to add the buttons to
		 * @param {Function} onClick callback function to be executed once a button click occurs. This callback function is executed in the scope of this Plugin, and gets the "name" of the button passed as parameter.
		 * @return {void}
		 * @private
		 */
		_addButtons: function(buttons, tab, onClick) {
			Ext.each(buttons, function(button) {
				var newButton = new GENTICS.Aloha.ui.Button({
					'label' : button.label,
					'size' : 'small',
					'onclick' : onClick.createDelegate(this, [button.name])
				});
				GENTICS.Aloha.FloatingMenu.addButton(
					'GENTICS.Aloha.continuoustext',
					newButton,
					GENTICS.Aloha.i18n(GENTICS.Aloha, tab),
					2
				);
			}, this);
		},

		/**
		 * Event handler triggered if a "new content element" button is clicked.
		 *
		 * @param {String} nameOfContentType the name of the content type to be created after the current one.
		 * @return {void}
		 * @private
		 */
		_onNewContentElementClick: function(nameOfContentType) {
			var currentContentElement = this._findParentContentElement(GENTICS.Aloha.activeEditable.obj);
			var data = this._createNodeFromContentElement(currentContentElement);

			if (window.parent.F3.TYPO3.Content.ContentModule !== undefined) {
				var loadingIndicatorDom = Ext.DomHelper.insertAfter(currentContentElement.get(0), '<div>LOADING</div>');

				window.parent.F3.TYPO3_Service_ExtDirect_V1_Controller_NodeController.create(data, {contentType: nameOfContentType}, 1, function(result) {
					this._loadNewlyCreatedContentElement(result.data.nextUri, loadingIndicatorDom);
				}.createDelegate(this));
			}
		},

		/**
		 * Load the newly created content element specified by uri, and replace
		 * the loading indicator by it. After that, activate Aloha for the newly
		 * inserted element.
		 *
		 * @param {String} uri URI of the content element
		 * @param {DOMElement} loadingIndicatorDom DOM element which is replaced by the content element
		 * @return {void}
		 * @private
		 */
		_loadNewlyCreatedContentElement: function(uri, loadingIndicatorDom) {
			Ext.Ajax.request({
				url: uri,
				method: 'GET',
				success: function(response) {
					var newContentElement = Ext.DomHelper.insertBefore(loadingIndicatorDom, Ext.util.Format.trim(response.responseText));
					Ext.fly(loadingIndicatorDom).remove();
					jQuery('.f3-typo3-editable', newContentElement).aloha();
				}
			});
		},

		/**
		 * Helper function which finds the parent content element from the given DOM node
		 *
		 * @param {jQuery} jQueryDomNode a DOM node wrapped by jQuery, from which the search should start
		 * @return {jQuery} the DOM node of the content element, having data-nodepath and data-workspacename set.
		 * @private
		 */
		_findParentContentElement: function(jQueryDomNode) {
			return jQueryDomNode.parents('*[data-nodepath]').first();
		},

		/**
		 * Helper function which creates a JSON structure which can be mapped
		 * to a TYPO3CR Node if used as argument for an Ext.Direct call.
		 *
		 * @param {jQuery} contentElement the Content Element container
		 * @return {Object} a JSON object with the __context set correctly.
		 * @private
		 */
		_createNodeFromContentElement: function(contentElement) {
			return F3.TYPO3.Content.ContentEditorFrontend.Core.createNode(contentElement.attr('data-nodepath'), contentElement.attr('data-workspacename'));
		}
	}
);