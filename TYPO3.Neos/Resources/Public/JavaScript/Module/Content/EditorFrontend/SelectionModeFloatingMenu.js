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
 * @class F3.TYPO3.Module.Content.EditorFrontend.SelectionModeFloatingMenu
 *
 * Class being responsible for implementing the Floating Menu shown when
 * being in Selection Mode. It uses the BreadcrumbMenuComponent internally.
 *
 * @namespace F3.TYPO3.Module.Content.EditorFrontend
 * @singleton
 */
F3.TYPO3.Module.Content.EditorFrontend.SelectionModeFloatingMenu = {

	/**
	 * A reference to the Floating Menu div currently shown.
	 *
	 * @var {Ext.Element}
	 * @private
	 */
	_floatingMenu: null,

	/**
	 * A reference to the Breadcrumb Menu, if it is currently shown.
	 *
	 * @var {F3.TYPO3.Components.BreadcrumbMenuComponent}
	 * @private
	 */
	_breadcrumbMenu: null,

	/**
	 * Initializer, called on page load. Is used to register event
	 * listeners on the core.
	 *
	 * @param {F3.TYPO3.Module.Content.EditorFrontend.Core} core
	 * @return {void}
	 */
	initialize: function(core) {
		core.on('enableSelectionMode', function() {
			Ext.select('.f3-typo3-contentelement').addListener('mouseenter', this._onMouseEnter, this);
			Ext.select('.f3-typo3-contentelement').addListener('mouseleave', this._onMouseLeave, this);
		}, this);
		core.on('disableSelectionMode', function() {
			Ext.select('.f3-typo3-contentelement').removeListener('mouseenter', this._onMouseEnter, this);
			Ext.select('.f3-typo3-contentelement').removeListener('mouseleave', this._onMouseLeave, this);
			this._removeFloatingMenu();
		}, this);
	},

	/**
	 * Event handler when the mouse enters a content element.
	 *
	 * @param {Event} event the event instance
	 * @param {DOMElement} targetElement the element under the mouse pointer.
	 * @return {void}
	 * @private
	 */
	_onMouseEnter: function(event, targetElement) {
		var contentElement = this._findEnclosingContentElement(targetElement);
		if (this._floatingMenu) this._removeFloatingMenu();
		this._floatingMenu = contentElement.insertFirst({
			cls: 'f3-typo3-selection-floatingmenu'
		});
		this._breadcrumbMenu = Ext.create({
			xtype: 'F3.TYPO3.Components.BreadcrumbMenuComponent',
			basePath: 'menu/selectionModeFloating',

			// If we are under "createNode", and are on a leaf level,
			// we show an up arrow and a down arrow as last level.
			getNextMenuLevel: function(menupath) {
				var childNodes = F3.TYPO3.Core.Registry.get(menupath + '/children');
				if (childNodes !== null) {
					return childNodes;
				}
				if (menupath.indexOf('menu/selectionModeFloating/children/createNode') === 0
					&& !(/(placementBefore|placementAfter)$/.test(menupath))) {
					return [{
						key: 'placementBefore',
						text: 'Before',
						iconCls: 'F3-TYPO3-Content-icon-upArrow'
					}, {
						key: 'placementAfter',
						text: 'After',
						iconCls: 'F3-TYPO3-Content-icon-downArrow'
					}];
				}
				return null;
			}
		});

		this._breadcrumbMenu.on('activate', function(currentlySelectedMenuPath, breadcrumbMenuComponent) {
			if (currentlySelectedMenuPath === 'menu/selectionModeFloating/children/deleteNode') {
				this._deleteContentElement(contentElement);
			} else if(/(placementBefore|placementAfter)$/.test(currentlySelectedMenuPath)) {
				this._insertContentElement(contentElement, currentlySelectedMenuPath);
			}
		}, this);
		this._breadcrumbMenu.render(this._floatingMenu);
	},

	/**
	 * Insert new content element
	 *
	 * @param {DOMElement} referenceContentElement the reference content element where the new element should be inserted
	 * @param {String} menuPath the menu path which has been clicked. The last element is "placementAfter" or "placementBefore", indicating the position of the new element.
	 * @return {void}
	 * @private
	 */
	_insertContentElement: function(referenceContentElement, menuPath) {
		var parentLevelMenuPath = null, position = 0;
		if (/placementBefore$/.test(menuPath)) {
			parentLevelMenuPath = menuPath.substr(0, menuPath.length - 25);
			position = -1;
		} else if (/placementAfter$/.test(menuPath)) {
			parentLevelMenuPath = menuPath.substr(0, menuPath.length - 24);
			position = 1;
		}
		if (parentLevelMenuPath !== null) {
			var parentLevelMenuConfiguration = F3.TYPO3.Core.Registry.get(parentLevelMenuPath);

			F3.TYPO3.Module.Content.EditorFrontend.Core.createNewContentElement(
				parentLevelMenuConfiguration.contentType,
				referenceContentElement.getAttribute('about'),
				referenceContentElement.dom,
				position
			);
		}
	},

	/**
	 * @param {DOMElement} contentElement delete the given content element
	 * @return {void}
	 * @private
	 */
	_deleteContentElement: function(contentElement) {
		contentElement.setOpacity(0.5, true);
		this._removeFloatingMenu();

			// We have to use call() since delete is a reserved word and will invalidate code validation
		window.parent.F3.TYPO3_Service_ExtDirect_V1_Controller_NodeController['delete'].call(this, contentElement.getAttribute('about'), function(result) {
			contentElement.remove();
		}, this);
	},

	/**
	 * Callback being executed when the mouse leaves a given content element.
	 *
	 * @param {Event} event the event instance
	 * @param {DOMElement} targetElement the element under the mouse pointer.
	 * @return {void}
	 * @private
	 */
	_onMouseLeave: function(event, targetElement) {
		this._removeFloatingMenu();
	},

	/**
	 * Find the enclosing content element from the given target element, by going up
	 * the DOM hierarchy.
	 *
	 * @param {DOMElement} targetElement the target element to find the content element for
	 * @return {Ext.Element} the content element container, wrapped in Ext.Element.
	 * @private
	 */
	_findEnclosingContentElement: function(targetElement) {
		if (Ext.fly(targetElement).hasClass('f3-typo3-contentelement')) {
			return Ext.get(targetElement);
		} else {
			return Ext.get(targetElement).parent('.f3-typo3-contentelement');
		}
	},

	/**
	 * Remove the floating menu if it is visible right now.
	 *
	 * @return {void}
	 * @private
	 */
	_removeFloatingMenu: function() {
		if (this._breadcrumbMenu) this._breadcrumbMenu.destroy();
		if (this._floatingMenu) this._floatingMenu.remove();
	}
};
F3.TYPO3.Module.Content.EditorFrontend.Core.registerModule(F3.TYPO3.Module.Content.EditorFrontend.SelectionModeFloatingMenu);