Ext.ns('F3.TYPO3.Module.Content.EditorFrontend');

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
 * @class F3.TYPO3.Module.Content.EditorFrontend.Core
 *
 * This is the main class loaded in the frontend editor. It has two main purposes:
 * - act as event bridge for the frontend editor components
 * - provide the interface to the TYPO3 backend in the outer frame.
 *
 * @namespace F3.TYPO3.Module.Content.EditorFrontend
 * @extends Ext.util.Observable
 * @singleton
 */
F3.TYPO3.Module.Content.EditorFrontend.Core = Ext.apply(new Ext.util.Observable(), {

	/**
	 * The list of modules loaded in the EditorFrontend.
	 *
	 * @var array
	 * @private
	 */
	_modules: [],

	/**
	 * The Internationalization component, used to get translated strings.
	 * This is just a shortcut to the localization component in the parent frame.
	 *
	 * @var F3.TYPO3.Core.I18n
	 */
	I18n: null,

	/**
	 * @event afterPageLoad
	 *
	 * Thrown when a page is fully loaded, and if the TYPO3 Backend is reachable.
	 * Use this event to add behavior at page load.
	 */

	/**
	 * @event enableNavigationMode
	 *
	 * Thrown when the navigation mode is entered.
	 */

	/**
	 * @event disableNavigationMode
	 *
	 * Thrown when the navigation mode is left, prior to entering another mode.
	 */

	/**
	 * @event enableSelectionMode
	 *
	 * Thrown when the selection mode is entered.
	 */

	/**
	 * @event disableSelectionMode
	 *
	 * Thrown when the selection mode is left, prior to entering another mode.
	 */

	/**
	 * @event enableEditingMode
	 * @param {DOMEvent} event the DOM event which was used to trigger this mode. Optional parameter.
	 *
	 * Thrown when the editing mode is entered.
	 */

	/**
	 * @event disableEditingMode
	 *
	 * Thrown when the editing mode is left, prior to entering another mode.
	 */

	/**
	 * @event keyDown-[keycode]
	 *
	 * Thrown when a key is pressed. The keycodes currently supported are ESC and CTRL
	 */

	/**
	 * @event keyUp-[keycode]
	 *
	 * Thrown when a key is released. The keycodes currently supported are ESC and CTRL
	 */

	/**
	 * @event windowResize
	 *
	 * Thrown when the window is resized.
	 */

	/**
	 * @event beforeSave
	 *
	 * Thrown immediately before data is saved to the server
	 */

	/**
	 * @event afterSave
	 *
	 * Thrown immediately after data has been saved to the server.
	 */

	/**
	 * @event loadNewlyCreatedContentElement
	 *
	 * Thrown when a new content element is created, and added to the page
	 */

	/**
	 * @event modifiedContent
	 *
	 * Thrown when content is changed
	 */


	/**
	 * Initializer. Called on Ext.onReady().
	 *
	 * @return {void}
	 */
	initialize: function() {
		if (window.parent !== undefined && window.parent.F3 !== undefined && window.parent.F3.TYPO3.Module.ContentModule !== undefined) {
			this.I18n = window.parent.F3.TYPO3.Core.I18n;

			Ext.ns('F3.TYPO3.Core');
			F3.TYPO3.Core.Registry = window.parent.F3.TYPO3.Core.Registry;
			F3.TYPO3.Core.I18n = window.parent.F3.TYPO3.Core.I18n;

			Ext.each(this._modules, function(module) {
				module.initialize(this);
			}, this);

			this._registerEventListeners();
			this.fireEvent('afterPageLoad');

			if (this._getWebsiteContainer().isNavigationModeEnabled()) {
				this._enableNavigationMode();
			} else if (this._getWebsiteContainer().isSelectionModeEnabled()) {
				this._enableSelectionMode();
			} else if (this._getWebsiteContainer().isEditingModeEnabled()) {
				this._enableEditingMode();
			}
		} else {
			this.I18n = {
				get: Ext.emptyFn
			}
		}
	},

	/**
	 * Register the internal event listeners
	 *
	 * @return {void}
	 * @private
	 */
	_registerEventListeners: function() {
		jQuery(document).dblclick(this._onDblClick.createDelegate(this));
		window.addEventListener('resize', this._onResize.createDelegate(this), false);
		window.addEventListener('keydown', this._onKeyDown.createDelegate(this), false);
		window.addEventListener('keyup', this._onKeyUp.createDelegate(this), false);

		this.on('keyDown-ESC', this._getWebsiteContainer().leaveCurrentMode, this._getWebsiteContainer());
		this.on('modifiedContent', function() {
			this._getWebsiteContainer().fireEvent('container.modifiedContent');
		}, this);
	},

	/**
	 * Register a Content Editor module.
	 * The passed object needs to have an initialize() method,
	 * which is called on loading the module.
	 *
	 * @param {Object} module
	 * @return {void}
	 * @api
	 */
	registerModule: function(module) {
		this._modules.push(module);
	},

	/**
	 * Call this function from custom EditorFrontend Modules to enable
	 * the navigation mode.
	 *
	 * @return {void}
	 * @api
	 */
	shouldEnableNavigationMode: function() {
		this._getWebsiteContainer().enableNavigationMode();
	},

	/**
	 * Call this function from custom EditorFrontend Modules to enable
	 * the selection mode.
	 *
	 * @return {void}
	 * @api
	 */
	shouldEnableSelectionMode: function() {
		this._getWebsiteContainer().enableSelectionMode();
	},

	/**
	 * Call this function from custom EditorFrontend Modules to enable
	 * the selection mode.
	 *
	 * @param {DOMEvent} event the DOM event which was used to trigger this mode. Optional parameter.
	 * @return {void}
	 * @api
	 */
	shouldEnableEditingMode: function(event) {
		this._getWebsiteContainer().enableEditingMode(event);
	},

	/**
	 * Called from the outside frame to enable navigation mode.
	 * DO NOT CALL DIRECTLY, instead, use shouldEnableNavigationMode().
	 *
	 * @return {void}
	 * @private
	 */
	_enableNavigationMode: function() {
		this.fireEvent('enableNavigationMode');
	},

	/**
	 * Called from the outside frame to disable navigation mode.
	 * DO NOT CALL DIRECTLY, instead, use should*Mode() methods
	 * to switch to another mode.
	 *
	 * @return {void}
	 * @private
	 */
	_disableNavigationMode: function() {
		this.fireEvent('disableNavigationMode');
	},

	/**
	 * Called from the outside frame to enable selection mode.
	 * DO NOT CALL DIRECTLY, instead, use shouldEnableSelectionMode().
	 *
	 * @return {void}
	 * @private
	 */
	_enableSelectionMode: function() {
		Ext.getBody().addClass('f3-typo3-selection-enabled');
		this.fireEvent('enableSelectionMode');
	},

	/**
	 * Called from the outside frame to disable selection mode.
	 * DO NOT CALL DIRECTLY, instead, use should*Mode() methods
	 * to switch to another mode.
	 *
	 * @return {void}
	 * @private
	 */
	_disableSelectionMode: function() {
		Ext.getBody().removeClass('f3-typo3-selection-enabled');
		this.fireEvent('disableSelectionMode');
	},

	/**
	 * Called from the outside frame to enable editing mode.
	 * DO NOT CALL DIRECTLY, instead, use shouldEnableEditingMode().
	 *
	 * @param {DOMEvent} event the DOM event which was used to trigger this mode. Optional parameter.
	 * @return {void}
	 * @private
	 */
	_enableEditingMode: function(event) {
		Ext.getBody().addClass('f3-typo3-editing-enabled');
		this.fireEvent('enableEditingMode', event);
	},

	/**
	 * Called from the outside frame to disable selection mode.
	 * DO NOT CALL DIRECTLY, instead, use should*Mode() methods
	 * to switch to another mode.
	 *
	 * @return {void}
	 * @private
	 */
	_disableEditingMode: function() {
		Ext.getBody().removeClass('f3-typo3-editing-enabled');
		this.fireEvent('disableEditingMode');
		VIE.ContainerManager.cleanup();
	},

	/**
	 * Access the content module in the parent window.
	 *
	 * @return {F3.TYPO3.Module.ContentModule} the parent content module
	 * @private
	 */
	_getContentModule: function() {
		return window.parent.F3.TYPO3.Module.ContentModule;
	},

	/**
	 * Access the website container in the parent window
	 *
	 * @return {F3.TYPO3.Module.Content.WebsiteContainer}
	 * @private
	 */
	_getWebsiteContainer: function() {
		return this._getContentModule().getWebsiteContainer();
	},

	/**
	 * Create a new content element on the page
	 *
	 * @param {string} nameOfContentType
	 * @param {object} referenceNode
	 * @param {DOMElement} referenceDomElement
	 * @param {integer} position
	 * @return {void}
	 */
	createNewContentElement: function(nameOfContentType, referenceNode, referenceDomElement, position) {
		var position = !position || position == 1 ? 1 : -1;
		var loadIndicatorContent = '<div>' + this.I18n.get('TYPO3', 'loading') + '</div>';

		if (position == -1) {
			var loadingIndicatorDom = Ext.DomHelper.insertBefore(referenceDomElement, loadIndicatorContent);
		} else {
			var loadingIndicatorDom = Ext.DomHelper.insertAfter(referenceDomElement, loadIndicatorContent);
		}

		window.parent.F3.TYPO3_Service_ExtDirect_V1_Controller_NodeController.create(referenceNode, {contentType: nameOfContentType}, position, function(result) {
			this._loadNewlyCreatedContentElement(result.data.nextUri, loadingIndicatorDom);
		}.createDelegate(this));
	},

	/**
	 * Load the HTML source of the new content element using AJAX.
	 * After that insert it, remove the loading indicator and fire the loadNewlyCreatedContentElement event
	 *
	 * @param {string} uri
	 * @param {DOMElement} loadingIndicatorDom
	 * @return {void}
	 * @private
	 */
	_loadNewlyCreatedContentElement: function(uri, loadingIndicatorDom) {
		var scope = this;
		Ext.Ajax.request({
			url: uri,
			method: 'GET',
			success: function(response) {
				var newContentElement = Ext.DomHelper.insertBefore(loadingIndicatorDom, Ext.util.Format.trim(response.responseText));
				Ext.fly(loadingIndicatorDom).remove();
				scope.fireEvent('loadNewlyCreatedContentElement', newContentElement);
			}
		});
	},

	/**
	 * Save a node
	 *
	 * @param {String} contextNodePath the path of the node which should be saved.
	 * @param {Object} properties
	 * @param {Function} callback
	 * @param {Object} scope
	 * @private
	 */
	saveNode: function(contextNodePath, properties, callback, scope) {
		var data = {__contextNodePath: contextNodePath };

		data.properties = properties;

		this.fireEvent('beforeSave');
		this._getWebsiteContainer().fireEvent('container.beforeSave');
		window.parent.F3.TYPO3_Service_ExtDirect_V1_Controller_NodeController.update(data, function(result) {
			this.fireEvent('afterSave');
			this._getWebsiteContainer().fireEvent('container.afterSave');

			if (callback) {
				callback.call(scope);
			}
		}.createDelegate(this));
	},

	/**
	 * Load the uri in current frame
	 *
	 * @param {String} uri
	 * @return {void}
	 */
	loadPage: function(uri) {
		this._getWebsiteContainer().loadPage(uri);
	},

	/**
	 * Double click handler. If we are not in editing mode, we enable it.
	 *
	 * @param {DOMEvent} event the DOM event
	 * @return {void}
	 * @private
	 */
	_onDblClick: function(event) {
		if (!this._getWebsiteContainer().isEditingModeEnabled()) {
			event.preventDefault();
			event.stopPropagation();
			this.shouldEnableEditingMode(event);
		}
		return false;
	},

	/**
	 * Handler on window resize.
	 *
	 * @return {void}
	 * @private
	 */
	_onResize: function() {
		this.fireEvent('windowResize');
	},

	/**
	 * Event handler on key down
	 *
	 * @return {void}
	 * @private
	 */
	_onKeyDown: function(event) {
		if (event.keyCode == 27) { // ESC
			this.fireEvent('keyDown-ESC');
		} else if (event.keyCode == 17) { // CTRL
			this.fireEvent('keyDown-CTRL');
		}
	},

	/**
	 * Event handler on key up
	 *
	 * @return {void}
	 * @private
	 */
	_onKeyUp: function(event) {
		if (event.keyCode == 27) { // ESC
			this.fireEvent('keyUp-ESC');
		} else if (event.keyCode == 17) { // CTRL
			this.fireEvent('keyUp-CTRL');
		}
	}
});


	// Override backbone sync for node model (actual saving of nodes)
Backbone.sync = function(method, model, success, error) {
	var properties = {};
	jQuery.each(model.attributes, function(propertyName, value) {
		if (propertyName == 'id') {
			return;
		}
			// TODO If TYPO3 supports mapping of fully qualified properties, send with namespace
		properties[propertyName.split(':', 2)[1]] = value;
	});
	F3.TYPO3.Module.Content.EditorFrontend.Core.saveNode(model.id, properties, function() {});
};

Ext.onReady(function() {
	F3.TYPO3.Module.Content.EditorFrontend.Core.initialize();
});