Ext.ns('F3.TYPO3.Content');

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
 * @class F3.TYPO3.Content.WebsiteContainer
 *
 * The main frontend editor widget, which is shown in the lower part of the
 * Backend.
 * This class is the main interface to the frontend editing from the rest of the backend.
 * You should never directly call any classes of the Frontend Editor loaded on the page,
 * but always use this class as connector.
 *
 * @namespace F3.TYPO3.Content
 * @extends Ext.Container
 */
F3.TYPO3.Content.WebsiteContainer = Ext.extend(Ext.Container, {

	/**
	 * @event container.modifiedContent
	 *
	 * Thrown when content is changed
	 */

	/**
	 * @event modeChange
	 *
	 * Thrown when editing / navigation / selection mode changes.
	 * Use the is*ModeEnabled() methods to check for the currently
	 * active mode.
	 */

	/**
	 * @event container.beforeSave
	 *
	 * Thrown immediately before data is saved to the server
	 */

	/**
	 * @event container.afterSave
	 *
	 * Thrown immediately after data has been saved to the server.
	 */

	/**
	 * Navigation mode
	 *
	 * @var {integer}
	 */
	NAVIGATION_MODE: 0,

	/**
	 * Selection mode
	 *
	 * @var {integer}
	 */
	SELECTION_MODE: 1,

	/**
	 * Edit mode
	 *
	 * @var {integer}
	 */
	EDITING_MODE: 2,

	/**
	 * The current mode
	 *
	 * @var {integer}
	 * @private
	 */
	_mode: 0,


	/**
	 * Flag which indicates if a mode change is currently running. If yes,
	 * changes to the breadcrumb menu do NOT change the state; to prevent
	 * recursion.
	 *
	 * @var {boolean}
	 * @private
	 */
	_modeChangeRunning: false,

	/**
	 * Initialize the frontend editor component
	 */
	initComponent: function() {
		var uri, config, cookieLastVisited;
		cookieLastVisited = Ext.util.Cookies.get('TYPO3_lastVisitedNode');
		uri =
			F3.TYPO3.Configuration.Application.backendBaseUri +
			"service/rest/v1/node" +
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
		F3.TYPO3.Content.WebsiteContainer.superclass.initComponent.call(this);
	},

	/**
	 * Enable navigation mode
	 *
	 * @return {void}
	 * @api
	 */
	enableNavigationMode: function() {
		if (this._modeChangeRunning) return;
		if (this.isNavigationModeEnabled()) return;

		this._modeChangeRunning = true;

		if (this.isSelectionModeEnabled()) {
			this._getFrontendEditorCore()._disableSelectionMode();
		} else if (this.isEditingModeEnabled()) {
			this._getFrontendEditorCore()._disableEditingMode();
		}
		this._mode = this.NAVIGATION_MODE;
		this._getFrontendEditorCore()._enableNavigationMode();
		this.fireEvent('modeChange');

		F3.TYPO3.UserInterface.UserInterfaceModule.viewport.sectionMenu.setActiveTab('content');
		F3.TYPO3.UserInterface.UserInterfaceModule.viewport.sectionMenu.getComponent('content').moduleMenu.breadcrumbMenu.deactivateItem('menu/main/content[]/edit');

		this._modeChangeRunning = false;
	},

	/**
	 * Enable selection mode
	 *
	 * @return {void}
	 * @api
	 */
	enableSelectionMode: function() {
		if (this._modeChangeRunning) return;
		if (this.isSelectionModeEnabled()) return;

		this._modeChangeRunning = true;

		if (this.isNavigationModeEnabled()) {
			this._getFrontendEditorCore()._disableNavigationMode();
		} else if (this.isEditingModeEnabled()) {
			this._getFrontendEditorCore()._disableEditingMode();
		}

		this._mode = this.SELECTION_MODE;
		this._getFrontendEditorCore()._enableSelectionMode();
		this.fireEvent('modeChange');

		F3.TYPO3.UserInterface.UserInterfaceModule.viewport.sectionMenu.setActiveTab('content');
		F3.TYPO3.UserInterface.UserInterfaceModule.viewport.sectionMenu.getComponent('content').moduleMenu.breadcrumbMenu.activateItem('menu/main/content[]/edit');

		this._modeChangeRunning = false;
	},

	/**
	 * Enable the editing mode and selecting the appropriate element in the breadcrumb menu.
	 *
	 * @param {DOMEvent} event the DOM event which was used to trigger this mode. Optional parameter.
	 * @return {void}
	 * @api
	 */
	enableEditingMode: function(event) {
		if (this._modeChangeRunning) return;
		if (this.isEditingModeEnabled()) return;

		this._modeChangeRunning = true;

		if (this.isNavigationModeEnabled()) {
			this._getFrontendEditorCore()._disableNavigationMode();
		} else if (this.isSelectionModeEnabled()) {
			this._getFrontendEditorCore()._disableSelectionMode();
		}

		this._mode = this.EDITING_MODE;
		this._getFrontendEditorCore()._enableEditingMode(event);
		this.fireEvent('modeChange');

		F3.TYPO3.UserInterface.UserInterfaceModule.viewport.sectionMenu.setActiveTab('content');
		F3.TYPO3.UserInterface.UserInterfaceModule.viewport.sectionMenu.getComponent('content').moduleMenu.breadcrumbMenu.activateItem('menu/main/content[]/edit');

		this._modeChangeRunning = false;
	},

	/**
	 * Return true if navigation mode is enabled
	 * @return {boolean}
	 * @api
	 */
	isNavigationModeEnabled: function() {
		return this._mode === this.NAVIGATION_MODE;
	},

	/**
	 * Return true if selection mode is enabled
	 * @return {boolean}
	 * @api
	 */
	isSelectionModeEnabled: function() {
		return this._mode === this.SELECTION_MODE;
	},

	/**
	 * Return true if editing mode is enabled
	 * @return {boolean}
	 * @api
	 */
	isEditingModeEnabled: function() {
		return this._mode === this.EDITING_MODE;
	},

	/**
	 * Disable editing or selection mode depending on current mode
	 * @return {void}
	 * @api
	 */
	leaveCurrentMode: function() {
		if (this.isEditingModeEnabled()) {
			this.enableSelectionMode();
		} else if (this.isSelectionModeEnabled()) {
			this.enableNavigationMode();
		}
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
	 * Get the frontend editor core from the iFrame.
	 * If the frontend editor core could not be loaded, returns a stub which replaces
	 * all called methods of the core with empty functions, to prevent syntax errors.
	 *
	 * @return {F3.TYPO3.Content.ContentEditorFrontend.Core} the frontend editor core
	 * @private
	 */
	_getFrontendEditorCore: function() {
		var iframeDom;
		iframeDom = this.getComponent('contentIframe').el.dom;
		if (iframeDom.contentWindow.F3) {
			return iframeDom.contentWindow.F3.TYPO3.Content.ContentEditorFrontend.Core;
		} else {
			return {
				_enableNavigationMode: Ext.emptyFn,
				_disableNavigationMode: Ext.emptyFn,
				_enableSelectionMode: Ext.emptyFn,
				_disableSelectionMode: Ext.emptyFn,
				_enableEditingMode: Ext.emptyFn,
				_disableEditingMode: Ext.emptyFn,
				_fireEvent: Ext.emptyFn
			};
		}
	},

	/**
	 * Get the current page path
	 *
	 * @return {String} current page path
	 */
	getCurrentPagePath: function() {
		return this._getIframeDocument().body.getAttribute('about');
	}
});

Ext.reg('F3.TYPO3.Content.WebsiteContainer', F3.TYPO3.Content.WebsiteContainer);