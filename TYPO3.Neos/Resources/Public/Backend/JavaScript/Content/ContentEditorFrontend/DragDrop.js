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
 * @class F3.TYPO3.Content.ContentEditorFrontend.DragDrop
 *
 * The aloha initializer which is loaded INSIDE the iframe.
 *
 * @namespace F3.TYPO3.Content.ContentEditorFrontend
 * @singleton
 */
F3.TYPO3.Core.Application.createModule('F3.TYPO3.Content.ContentEditorFrontend.DragDrop', {
	/**
	 * shortcut reference to the Content Module
	 *
	 * @var {F3.TYPO3.Content.ContentModule}
	 * @private
	 */
	_contentModule: null,

	/**
	 * Editing enabled
	 *
	 * @var {boolean}
	 * @private
	 */
	_editingEnabled: false,

	/**
	 * Initialize drag & drop
	 *
	 * @param {F3.TYPO3.Core.Application} application
	 * @return {void}
	 */
	initialize: function(application) {

		application.on('backendAvailable', function(contentModule) {
			this._contentModule = contentModule;
			this._addDropZones();
		}, this);

		application.afterInitializationOf('F3.TYPO3.Content.ContentEditorFrontend.Aloha.Initializer', function(alohaInitializer) {
			alohaInitializer.on('enableEditing', function() {
				this._editingEnabled = true;
			}, this);

			alohaInitializer.on('disableEditing', function() {
				this._editingEnabled = false;
			}, this);
		}, this);
	},

	/**
	 * Add the drop zones
	 *
	 * @return {void}
	 * @private
	 */
	_addDropZones: function() {
		var elementDefinition = {
			tag: 'div',
			cls: 'f3-typo3-dropzone',
			html: 'Drop content here'
		};

		Ext.select('.f3-typo3-contentelement').each(function(el) {
			Ext.DomHelper.insertBefore(el, Ext.apply(elementDefinition, {
				'data-nodepath': el.getAttribute('data-nodepath'),
				'data-position': 'before'
			}));
		});

		Ext.select('.f3-typo3-contentelement').each(function(el) {
			if (!el.next().hasClass('f3-typo3-dropzone')) {
				Ext.DomHelper.insertBefore(el, Ext.apply(elementDefinition, {
					'data-nodepath': el.getAttribute('data-nodepath'),
					'data-position': 'after'
				}));
			}
		});
	}
});