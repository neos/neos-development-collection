Ext.ns('F3.TYPO3.Content.ContentEditorFrontend.Html');

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
 * @class F3.TYPO3.Content.ContentEditorFrontend.Html.Plugin
 *
 * Initialize Html editor in the ContentEditorFrontend
 *
 * @namespace F3.TYPO3.Content.ContentEditorFrontend.Html
 */
F3.TYPO3.Content.ContentEditorFrontend.Html.Plugin = {

	/**
	 * @var {Ext.Element}
	 */
	_element: null,

	/**
	 * @var {Ext.Window}
	 */
	_htmlEditorWindow: null,

	/**
	 * @var {CodeMirror}
	 */
	_htmlEditorTextarea: null,

	/**
	 * Initialize the plugin
	 *
	 * @return {void}
	 */
	init: function() {
		var confirmButton = new Ext.Button({
			text: F3.TYPO3.Content.ContentEditorFrontend.Core.I18n.get('TYPO3', 'confirm'),
			disabled: true,
			handler: function() {
				this._saveHandler();
			}.createDelegate(this)
		});

		var htmlSource = null;
		var containerInstance = VIE.ContainerManager.getInstanceForContainer(jQuery(this._element.dom));
		htmlSource = containerInstance.get('typo3:source').replace(/(^\s+|\s+$)/g,'');

		if (F3.TYPO3.Content.ContentEditorFrontend.Html.Initializer.isEmptyHtmlContentElement(this._element.dom)) {
			htmlSource = '';
		}

		this._htmlEditorWindow = new Ext.Window({
			title: F3.TYPO3.Content.ContentEditorFrontend.Core.I18n.get('TYPO3', 'htmlEditor'),
			modal: true,
			closeAction:'hide',
			plain: true,
			height: top.Ext.getBody().getHeight() - 250,// @todo: decide something about the size later on to find
			width: top.Ext.getBody().getWidth() - 300,// a clean way we can manage the size

			buttons: [confirmButton, {
				text: F3.TYPO3.Content.ContentEditorFrontend.Core.I18n.get('TYPO3', 'cancel'),
				handler: function() {
					this._htmlEditorWindow.hide();
				}.createDelegate(this)
			}],
			listeners: {
				afterRender: function(htmlEditor) {
					this._htmlEditorTextarea = new CodeMirror(htmlEditor.body.dom, {
						lineNumbers: true,
						mode: 'xml',
						value: htmlSource,
						onChange: function() {
							if (confirmButton.disabled === true) {
								confirmButton.enable();
							}
						},
						height: '100%'
					});
				}.createDelegate(this)
			},
			renderTo: Ext.getBody()
		});
		this._htmlEditorWindow.show();
	},

	/**
	 * Save handler after click on the save button
	 *
	 * @return {void}
	 */
	_saveHandler: function() {
		var containerInstance = VIE.ContainerManager.getInstanceForContainer(jQuery(this._element.dom));
		containerInstance.set({'typo3:source': this._htmlEditorTextarea.getValue()});
		containerInstance.save();
		this._htmlEditorWindow.hide();

		F3.TYPO3.Content.ContentEditorFrontend.Html.Initializer.insertPlaceholderIfElementIsEmpty(this._element.dom);
	}

};

/**
 * Constructor method
 */
F3.TYPO3.Content.ContentEditorFrontend.Html.Plugin = Ext.extend(
	/**
	 *
	 * @param {Ext.Element} element
	 * @return {void}
	 */
	function(element) {
		this._element = element;
		this.init();
	},
	F3.TYPO3.Content.ContentEditorFrontend.Html.Plugin
);

Ext.reg('F3.TYPO3.Content.ContentEditorFrontend.Html.Plugin', F3.TYPO3.Content.ContentEditorFrontend.Html.Plugin);