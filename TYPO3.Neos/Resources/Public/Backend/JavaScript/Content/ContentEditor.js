Ext.ns("F3.TYPO3.Content");

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
 * @class F3.TYPO3.Content.ContentEditor
 *
 * The main frontend editor widget, which is shown in the lower part of the
 * Backend.
 *
 * @namespace F3.TYPO3.Content
 * @extends Ext.Container
 */
F3.TYPO3.Content.ContentEditor = Ext.extend(Ext.Container, {

	/**
	 * Initialize the frontend editor component
	 */
	initComponent: function() {
		var uri, config, cookieLastVisited;
		cookieLastVisited = Ext.util.Cookies.get('TYPO3_lastVisitedNode');
		uri =
			F3.TYPO3.Configuration.Application.backendBaseUri +
			"service/rest/v1/node/" +
			F3.TYPO3.Configuration.Application.workspaceName +
			(cookieLastVisited ? cookieLastVisited + ".html" :
				F3.TYPO3.Configuration.Application.siteNodePath + '/.html');

		config = {
			border: false,
			style: {
				overflow: 'hidden'
			},
			items: {
				itemId: 'contentIframe',
				xtype: 'box',
				autoEl: {
					tag: 'iframe',
					src: uri,
					style: {
						width: '100%',
						height: '100%',
						border: '0',
						background: '#333'
					}
				}
			}
		};
		Ext.apply(this, config);
		F3.TYPO3.Content.ContentEditor.superclass.initComponent.call(this);

		F3.TYPO3.Content.ContentModule.on('AlohaConnector.persistChangedContent', this._onContentChanged, this);
	},

	/**
	 * Load the uri in the iFrame
	 *
	 * @param {String} uri
	 * @return {void}
	 */
	loadPage: function(uri) {
		this._getIframeDocument().location.assign(Ext.select('base').first().getAttribute('href') + uri);
	},

	/**
	 * Reload the iFrame content
	 *
	 * @return {void}
	 */
	reload: function() {
		this._getIframeDocument().location.reload();
	},

	/**
	 * Callback fired if content is changed
	 *
	 * @param {Object} data See "AlohaConnector.persistChangedContent" event for detail description of the parameters.
	 * @private
	 */
	_onContentChanged: function(data) {
		F3.TYPO3_Service_ExtDirect_V1_Controller_NodeController.update(data);
	},

	/**
	 * Get the frontend editor IFrame document object
	 *
	 * @return {Object}
	 * @private
	 */
	_getIframeDocument: function() {
		var iframeDom, iframeDocument;

		iframeDom = this.getComponent('contentIframe').el.dom,
		iframeDocument = iframeDom.contentDocument ? iframeDom.contentDocument : iframeDom.Document;
		return iframeDocument;
	},

	/**
	 * Get the frontend editor core from the iFrame
	 *
	 * @return {F3.TYPO3.Content.ContentEditorFrontend.Core} the frontend editor core, or undefined, if the frame is not there-
	 * @private
	 */
	_getFrontendEditorCore: function() {
		var iframeDom;
		iframeDom = this.getComponent('contentIframe').el.dom;
		if (iframeDom.contentWindow.F3) {
			return iframeDom.contentWindow.F3.TYPO3.Content.ContentEditorFrontend.Core;
		} else {
			return undefined;
		}
	},

	/**
	 * Get the current context path
	 *
	 * @return {Object} current context
	 */
	getCurrentContext: function() {
		return {
			'__context': {
				workspaceName: this._getIframeDocument().body.getAttribute('data-workspacename'),
				nodePath: this._getIframeDocument().body.getAttribute('data-nodepath')
			}
		};
	},

	/**
	 * Enable editing. Only used internally; To enable editing mode, use F3.TYPO3.Content.ContentModule.enableEditing();
	 *
	 * @return {void}
	 * @private
	 */
	_enableEditing: function() {
		if (this._getFrontendEditorCore()) {
			this._getFrontendEditorCore()._enableEditing();
		}
	},

	/**
	 * Disable editing. Only used internally; To disable editing mode, use F3.TYPO3.Content.ContentModule.disableEditing();
	 *
	 * @return {void}
	 * @private
	 */
	_disableEditing: function() {
		if (this._getFrontendEditorCore()) {
			this._getFrontendEditorCore()._disableEditing();
		}
	}
});
Ext.reg('F3.TYPO3.Content.ContentEditor', F3.TYPO3.Content.ContentEditor);