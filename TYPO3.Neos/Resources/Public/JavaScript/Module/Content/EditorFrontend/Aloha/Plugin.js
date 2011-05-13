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
 * @class F3.TYPO3.Module.Content.EditorFrontend.Aloha.Plugin
 *
 * TYPO3 to Aloha connector plugin<br />
 * <br />
 * This plugin tracks content changes by the aloha editor and communicates this
 * information to the TYPO3 backend.
 * This class does NOT run inside the backend, but is included in the Frontend
 * iframe.
 *
 * @namespace F3.TYPO3.Module.Content.EditorFrontend.Aloha
 * @extends GENTICS.Aloha.Plugin
 * @singleton
 */
F3.TYPO3.Module.Content.EditorFrontend.Aloha.Plugin = Ext.apply(
	new GENTICS.Aloha.Plugin('F3.TYPO3.Module.Content.EditorFrontend.Aloha.Plugin'),
	{
		/**
		 * Configure the available languages
		 *
		 * @type {Array}
		 */
		languages: [],

		/**
		 * Initialize the Aloha connector for TYPO3
		 *
		 * @return {void}
		 * @private
		 */
		init: function() {
			this._overrideI18nLocalization();
			this._subscribeToAlohaEvents();

				// Adds the "create content element" buttons to the 'insert' tab.
			this._addButtons(
				this.settings.contentTypes,
				'floatingmenu.tab.insert',
				this._onNewContentElementClick
			);

				// Add the "delete" buttons to the 'actions' tab.
			this._addButtons([{
					labelKey: F3.TYPO3.Module.Content.EditorFrontend.Core.I18n.get('TYPO3', 'delete')
				}],
				'floatingMenuTabAction',
				this._onDeleteButtonClick
			);
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
				'editableDeactivated',
				this._saveChanges.createDelegate(this)
			);

			GENTICS.Aloha.EventRegistry.subscribe(
				GENTICS.Aloha,
				'smartContentChanged',
				this._saveChanges.createDelegate(this)
			);
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
					'label': F3.TYPO3.Module.Content.EditorFrontend.Core.I18n.get('ContentTypes', button.labelKey),
					'size': 'small',
					'onclick': onClick.createDelegate(this, [button.name])
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
			if (GENTICS.Aloha.activeEditable === null) return;
			var currentContentElement = this._findParentContentElement(GENTICS.Aloha.activeEditable.obj);

			var data = this._createNodeFromContentElement(currentContentElement);

			F3.TYPO3.Module.Content.EditorFrontend.Core.createNewContentElement(
				nameOfContentType,
				data,
				currentContentElement.get(0)
			);
		},

		/**
		 * Event handler triggered if a "delete" button is clicked.
		 *
		 * @return {void}
		 * @private
		 */
		_onDeleteButtonClick: function() {
			var currentContentElement = this._findParentContentElement(GENTICS.Aloha.activeEditable.obj);
			var data = this._createNodeFromContentElement(currentContentElement);

				// We have to use call() since delete is a reserved word and will invalidate code validation
			window.parent.F3.TYPO3_Service_ExtDirect_V1_Controller_NodeController['delete'].call(this, data, function(result) {
				currentContentElement.remove();
			}.createDelegate(this));
		},

		/**
		 * Helper function which finds the parent content element from the given DOM node
		 *
		 * @param {jQuery} jQueryDomNode a DOM node wrapped by jQuery, from which the search should start
		 * @return {jQuery} the DOM node of the content element, having "about" property set.
		 * @private
		 */
		_findParentContentElement: function(jQueryDomNode) {
			return jQueryDomNode.parents('*[about]').first();
		},

		_createNodeFromContentElement: function(element) {
			return {'__contextNodePath': element.attr('about')};
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
			var alohaI18n = GENTICS.Aloha.i18n;
			GENTICS.Aloha.i18n = function (component, key, replacements) {
				var localizedString = F3.TYPO3.Module.Content.EditorFrontend.Core.I18n.get('TYPO3', key);
				if (localizedString === key) {
					return alohaI18n.call(this, component, key, replacements);
				}
				return localizedString;
			}
		}
	}
);