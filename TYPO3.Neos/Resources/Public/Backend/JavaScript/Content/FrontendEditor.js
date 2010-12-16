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
 * @class F3.TYPO3.Content.FrontendEditor
 *
 * The main frontend editor widget, which is shown in the lower part of the
 * Backend.
 *
 * @namespace F3.TYPO3.Content
 * @extends Ext.Container
 */
F3.TYPO3.Content.FrontendEditor = Ext.extend(Ext.Container, {

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
		F3.TYPO3.Content.FrontendEditor.superclass.initComponent.call(this);

		F3.TYPO3.Content.ContentModule.on('AlohaConnector.contentChanged', this._onContentChanged, this);
	},

	/**
	 * Load the uri in the iFrame
	 *
	 * @param {String} uri
	 * @return {void}
	 */
	loadPage: function(uri) {
		this._getIframeDocument().location.assign(uri);
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
	 * @param {Object} data See "AlohaConnector.contentChanged" event for detail description of the parameters.
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
	 * Get the frontend editor Aloha Initializer from the iframe
	 *
	 * @return {F3.TYPO3.Content.AlohaConnector.AlohaInitializer} the aloha initializer, or undefined, if the frame is not there-
	 * @private
	 */
	_getAlohaInitializer: function() {
		var iframeDom, iframeDocument;
		iframeDom = this.getComponent('contentIframe').el.dom;
		if (iframeDom.contentWindow.F3) {
			return iframeDom.contentWindow.F3.TYPO3.Content.AlohaConnector.AlohaInitializer;
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
	 * Overlay the uneditable areas in the frontend editor.
	 *
	 * @return {void}
	 */
	enableEditing: function() {
		if (this._getAlohaInitializer()) {
			this._getAlohaInitializer().enableEditing();
		}
	},

	/**
	 * Disable editing again
	 *
	 * @return {void}
	 */
	disableEditing: function() {
		if (this._getAlohaInitializer()) {
			this._getAlohaInitializer().disableEditing();
		}
	}
});
Ext.reg('F3.TYPO3.Content.FrontendEditor', F3.TYPO3.Content.FrontendEditor);