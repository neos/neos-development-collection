Ext.ns("F3.TYPO3.Content");

/**
 * @class F3.TYPO3.Content.FrontendEditor
 * @namespace F3.TYPO3.Content
 * @extends Ext.Panel
 *
 * The main frontend editor.
 */
F3.TYPO3.Content.FrontendEditor = Ext.extend(Ext.Container, {
	/**
	 * Reference to the IFrame box component
	 */
	contentIframe: null,

	/**
	 * Initialize the frontend editor component
	 */
	initComponent: function() {
		var uri =
			Ext.util.Cookies.get('TYPO3_lastVisitedFrontendUri') ?
			Ext.util.Cookies.get('TYPO3_lastVisitedFrontendUri') :
			F3.TYPO3.Configuration.Application.frontendBaseUri;

		var config = {
			border: false,
			style: {
				overflow: 'hidden'
			},
			items: {
				itemId: 'contentIframe',
				xtype: 'box',
				ref: '../contentIframe',
				autoEl: {
					tag: 'iframe',
					src: uri,
					style: {
						width: '100%',
						height: '100%',
						border: '0'
					}
				}
			}
		};
		Ext.apply(this, config);
		F3.TYPO3.Content.FrontendEditor.superclass.initComponent.call(this);

		F3.TYPO3.Content.ContentModule.on('AlohaConnector.contentChanged', this._contentChanged, this);
	},

	/**
	 * Reload the IFrame content
	 *
	 * @return {void}
	 */
	reload: function() {
		this.getIframeDocument().location.reload();
	},

	/**
	 * Callback fired if content is changed
	 */
	_contentChanged: function(data) {
		F3.TYPO3_Controller_NodeController.update(data);
	},

	/**
	 * get the frontent editor iframe document object
	 *
	 * @return {object}
	 */
	getIframeDocument: function() {
		var iframeDom = this.getComponent('contentIframe').el.dom,
			iframeDocument = iframeDom.contentDocument ? iframeDom.contentDocument : iframeDom.Document;
		return iframeDocument;
	},


	/**
	 * Get the current context path
	 *
	 * @return {object} current context path
	 */
	getCurrentContext: function() {
		var context = {
			'__context': {
				workspaceName: this.getIframeDocument().body.getAttribute('data-workspacename'),
				nodePath: this.getIframeDocument().body.getAttribute('data-nodepath')
			}
		}
		return context;
	}

});
Ext.reg('F3.TYPO3.Content.FrontendEditor', F3.TYPO3.Content.FrontendEditor);