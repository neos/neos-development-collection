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
 * @class F3.TYPO3.Content.ContentEditorFrontend.Html.Editor
 *
 * Initialize Html editor in the ContentEditorFrontend
 *
 * @namespace F3.TYPO3.Content.ContentEditorFrontend.Html
 */
F3.TYPO3.Content.ContentEditorFrontend.Html.Editor = function(element) {
	this._element = element;
	this.initialize();
}

F3.TYPO3.Content.ContentEditorFrontend.Html.Editor = Ext.extend(F3.TYPO3.Content.ContentEditorFrontend.Html.Editor, {

	/**
	 * @var {Ext.Element}
	 */
	_element: null,

	/**
	 * @var {Ext.Window}
	 */
	_htmlEditorWindow: null,

	/**
	 * @var {Ext.form.TextArea}
	 */
	_htmlEditorTextarea: null,

	initialize: function() {
		var scope = this;

		var confirmButton = new Ext.Button({
			text: 'Confirm',
			disabled: true,
			handler: function() {
				scope._saveHandler.call(scope);
			}
		});

		var html = scope._element.dom.innerHTML;
		html = html.replace(/(^\s+|\s+$)/g,'');

		this._htmlEditorTextarea = new Ext.form.TextArea({
			value: html,
			width: '100%',
			height: '100%',
			enableKeyEvents: true,
			listeners: {
				keyup: function(event) {
					if (confirmButton.disabled === true) {
						confirmButton.enable();
					}
				}
			}
		});

		this._htmlEditorWindow = new Ext.Window({
			title: 'HTML Editor',
			modal: true,
			closeAction:'hide',
			plain: true,
			height: top.Ext.getBody().getHeight() - 250,// @todo: decide something about the size later on to find
			width: top.Ext.getBody().getWidth() - 300,// a clean way we can manage the size
			items: [
				this._htmlEditorTextarea
			],
			buttons: [confirmButton, {
				text: 'Cancel',
				handler: function() {
					scope._htmlEditorWindow.hide();
				}
			}],
			renderTo: Ext.getBody()
		}).show();
	},

	/**
	 * Save handler after click on the save button
	 *
	 * @return {void}
	 */
	_saveHandler: function() {
		this._element.dom.innerHTML = this._htmlEditorTextarea.getValue();
		this._element.dom.innerHTML = this._element.dom.innerHTML.replace(/id="ext-gen[0-9]*"/, '');

		var node = F3.TYPO3.Content.ContentEditorFrontend.Html.Initializer._createNodeFromContentElement(this._element);
		node.properties = {
			source: this._element.dom.innerHTML
		};

		var scope = this;
		top.F3.TYPO3_Service_ExtDirect_V1_Controller_NodeController.update.call(this, node, function(result) {
			scope._htmlEditorWindow.hide();
		});
	}

});

Ext.reg('F3.TYPO3.Content.ContentEditorFrontend.Html.Editor', F3.TYPO3.Content.ContentEditorFrontend.Html.Editor);