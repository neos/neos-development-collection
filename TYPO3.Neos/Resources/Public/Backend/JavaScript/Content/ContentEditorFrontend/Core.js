Ext.ns('F3.TYPO3.Content.ContentEditorFrontend');

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
 * @class F3.TYPO3.Content.ContentEditorFrontend.Core
 *
 * This is the main class loaded in the frontend editor. It has two main purposes:
 * - act as event bridge for the frontend editor components
 * - provide the interface to the TYPO3 backend in the outer frame.
 *
 * @namespace F3.TYPO3.Content.ContentEditorFrontend
 * @extends Ext.util.Observable
 * @singleton
 */
F3.TYPO3.Content.ContentEditorFrontend.Core = Ext.apply(new Ext.util.Observable(), {

	/**
	 * The list of modules loaded in the ContentEditorFrontend.
	 *
	 * @var array
	 * @private
	 */
	_modules: [],

	/**
	 * @event afterPageLoad
	 *
	 * Thrown when a page is fully loaded, and if the TYPO3 Backend is reachable.
	 * Use this event to add behavior at page load.
	 */

	/**
	 * @event enableEditing
	 *
	 * Thrown when the editing mode is started.
	 */

	/**
	 * @event disableEditing
	 *
	 * Thrown when the editing mode is stopped.
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
	 * Thrown when the window is resized and the user is in editing mode.
	 */

	/**
	 * Initializer. Called on Ext.onReady().
	 *
	 * @return {void}
	 * @private
	 */
	initialize: function() {
		Ext.each(this._modules, function(module) {
			module.initialize(this);
		}, this);

		this._registerEventListeners();

		if (window.parent.F3.TYPO3.Content.ContentModule !== undefined) {
			this.fireEvent('afterPageLoad');

			if (this._getContentModule().isEditing()) {
				this._enableEditing();
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
		window.addEventListener('dblclick', this._onDblClick.createDelegate(this), false);
		window.addEventListener('resize', this._onResize.createDelegate(this), false);
		window.addEventListener('keydown', this._onKeyDown.createDelegate(this), false);
		window.addEventListener('keyup', this._onKeyUp.createDelegate(this), false);

		this.on('keyDown-ESC', this.shouldDisableEditing, this);
	},

	/**
	 * Double click handler. If we are not in editing mode, we enable it.
	 *
	 * @param {DOMEvent} event the DOM event
	 * @return {void}
	 * @private
	 */
	_onDblClick: function(event) {
		if (!this._getContentModule().isEditing()) {
			event.preventDefault();
			event.stopPropagation();
			this._getContentModule().enableEditing();
		}
	},

	/**
	 * Handler on window resize.
	 *
	 * @return {void}
	 * @private
	 */
	_onResize: function() {
		if (this._getContentModule().isEditing()) {
			this.fireEvent('windowResize');
		}
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
	 * Event handler on key down
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
	},

	/**
	 * Register a Content Editor module.
	 * The passed object needs to have an initialize() method,
	 * which is called on loading the module.
	 *
	 * @param {Object} module
	 * @return {void}
	 */
	registerModule: function(module) {
		this._modules.push(module);
	},


	/**
	 * Should be called to enable editing mode.
	 *
	 * @return {void}
	 * @private
	 */
	shouldEnableEditing: function() {
		this._getContentModule().enableEditing();
	},

	/**
	 * Should be called to disable editing mode.
	 *
	 * @return {void}
	 * @private
	 */
	shouldDisableEditing: function() {
		this._getContentModule().disableEditing();
	},

	/**
	 * Called from the outside frame to enable editing. Do not call directly;
	 * instead use shouldEnableEditing().
	 *
	 * @return {void}
	 * @private
	 */
	_enableEditing: function() {
		Ext.getBody().addClass('f3-typo3-editing-enabled');
		this.fireEvent('enableEditing');
	},

	/**
	 * Called from the outside frame to disable editing. Do not call directly;
	 * instead use shouldDisableEditing().
	 *
	 * @return {void}
	 * @private
	 */
	_disableEditing: function() {
		Ext.getBody().removeClass('f3-typo3-editing-enabled');
		this.fireEvent('disableEditing');
	},

	/**
	 * Access the content module in the parent window.
	 *
	 * @return {F3.TYPO3.Content.ContentModule} the parent content module
	 * @private
	 */
	_getContentModule: function() {
		return window.parent.F3.TYPO3.Content.ContentModule;
	}
});

Ext.onReady(function() {
	F3.TYPO3.Content.ContentEditorFrontend.Core.initialize();
});