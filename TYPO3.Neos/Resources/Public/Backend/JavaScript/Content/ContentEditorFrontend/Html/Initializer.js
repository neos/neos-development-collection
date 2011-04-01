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
 * @class F3.TYPO3.Content.ContentEditorFrontend.Html.Initializer
 *
 * Initialize Html editor in the ContentEditorFrontend
 *
 * @namespace F3.TYPO3.Content.ContentEditorFrontend.Html
 * @singleton
 */
F3.TYPO3.Content.ContentEditorFrontend.Html.Initializer = Ext.apply({}, {

	/**
	 * Initializer, called on page load. Is used to register event
	 * listeners on the core.
	 *
	 * @param {F3.TYPO3.Content.ContentEditorFrontend.Core} core
	 * @return {void}
	 */
	initialize: function(core) {
		core.on('enableEditingMode', this._registerEventHandlers, this);
		core.on('disableEditingMode', this._unregisterEventHandlers, this);
		//core.on('loadNewlyCreatedContentElement', this._onNewlyCreatedContentElement);
	},

	/**
	 * Atach the event listeners to an instance of the HTML plugin
	 *
	 * @param {Ext.Element} element
	 * @return {void}
	 */
	_registerEventHandlers: function() {
		jQuery('.f3-typo3-contentelement-html').each(function(index, element) {
			var contents = Ext.util.Format.trim(jQuery(element).html());
			if (!contents || contents == '' || contents == '&nbsp;' || contents == '<br />' || contents == '<br>') {
				//jQuery(element).html('<span class="f3-typo3-html-placeholder">[' + F3.TYPO3.Content.ContentEditorFrontend.Core.I18n.get('TYPO3', 'enterSomeContent') + ']</span>');
				jQuery(element).html('TEST HTML');
			}
		});
		jQuery('.f3-typo3-contentelement-html').live('dblclick', function() {
			new F3.TYPO3.Content.ContentEditorFrontend.Html.Plugin(Ext.get(this));
		});
	},

	_unregisterEventHandlers: function() {
		jQuery('.f3-typo3-contentelement-html').die('dblclick');
		jQuery('.f3-typo3-html-placeholder').remove();
	}
});

F3.TYPO3.Content.ContentEditorFrontend.Core.registerModule(F3.TYPO3.Content.ContentEditorFrontend.Html.Initializer);