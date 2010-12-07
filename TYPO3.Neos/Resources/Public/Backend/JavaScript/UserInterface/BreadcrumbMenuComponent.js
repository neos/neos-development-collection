Ext.namespace('F3.TYPO3.UserInterface');

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
 * @class F3.TYPO3.UserInterface.BreadcrumbMenuComponent
 *
 * Breadcrumb Menu component, which is used in the top area.
 *
 * @namespace F3.TYPO3.UserInterface
 * @extends Ext.BoxComponent
 */
F3.TYPO3.UserInterface.BreadcrumbMenuComponent = function() {
	F3.TYPO3.UserInterface.BreadcrumbMenuComponent.superclass.constructor.apply(this, arguments);
};

Ext.extend(F3.TYPO3.UserInterface.BreadcrumbMenuComponent, Ext.BoxComponent, {

	/**
	 * Base path to an element in the registry for this menu
	 * @cfg string
	 */
	basePath: null,

	/**
	 * @return {void}
	 */
	initComponent: function() {
		var config = {
			cls: 'F3-TYPO3-UserInterface-BreadcrumbMenu',
			height: '47px'
		};
		Ext.apply(this, config);
		F3.TYPO3.UserInterface.BreadcrumbMenuComponent.superclass.initComponent.call(this);

		this.on('afterrender', function() {
			this._renderLevel(this.basePath);
		}, this);
	},

	/**
	 * Renders a new level of the menu, on the right side of all currently
	 * displayed ones.
	 *
	 * @param {String} basePath the base path to render right now.
	 * @return {void}
	 * @private
	 */
	_renderLevel: function(basePath) {
		var levelContainer;

		levelContainer = Ext.DomHelper.append(this.el, {
			tag: 'span',
			cls: 'F3-TYPO3-UserInterface-BreadcrumbMenu-SingleLevel'
		});

		Ext.each(F3.TYPO3.Core.Registry.get(basePath + '/children'), function(menuItem) {
			var menuItemElement, innerMenuItemElement;

			menuItemElement = Ext.DomHelper.append(levelContainer, {
				tag: 'span',
				cls: 'F3-TYPO3-UserInterface-BreadcrumbMenu-MenuItem ' + menuItem.iconCls,
				'data-menupath': basePath + '/children/' + menuItem.key
			});
			innerMenuItemElement = Ext.DomHelper.append(menuItemElement, {
				tag: 'span',
				cls: 'F3-TYPO3-UserInterface-BreadcrumbMenu-MenuItem-Text',
				children: menuItem.text
			});
			Ext.get(menuItemElement).on('click', this._onMenuItemClick, this);
			Ext.get(innerMenuItemElement).on('click', this._onInnerMenuItemClick, this);
		}, this);
	},

	/**
	 * Event handler for clicking a menu item.
	 *
	 * @param {Ext.EventObject} event
	 * @param {DOMElement} clickedMenuItemDomElement the MenuItem which is clicked
	 * @return {void}
	 * @private
	 */
	_onMenuItemClick: function(event, clickedMenuItemDomElement) {
		var clickedMenuItem = Ext.get(clickedMenuItemDomElement);

		if (clickedMenuItem.hasClass('F3-TYPO3-UserInterface-BreadcrumbMenu-MenuItem-active')) {
			this._deactivateActiveItem(clickedMenuItem);
		} else {
			this._activateItem(clickedMenuItem);
		}
	},

	/**
	 * Event handler for clicking a menu item text, which we just
	 * forward to the handler of the current menu item.
	 *
	 * @param {Ext.EventObject} event
	 * @param {DOMElement} clickedMenuItemDomElement the MenuItem which is clicked
	 * @return {void}
	 * @private
	 */
	_onInnerMenuItemClick: function(event, clickedMenuItemDomElement) {
		this._onMenuItemClick(event, clickedMenuItemDomElement.parentNode);
	},

	/**
	 * Activate the currently clicked menu item.
	 *
	 * @param {Ext.Element} clickedMenuItem the clicked menu item element
	 * @return {void}
	 * @private
	 */
	_activateItem: function(clickedMenuItem) {
		var currentlyClickedMenuPath = clickedMenuItem.getAttribute('data-menupath');

		clickedMenuItem.parent().select('.F3-TYPO3-UserInterface-BreadcrumbMenu-MenuItem-active').each(function(activeItem) {
			this._deactivateActiveItem(activeItem);
		}, this);

		clickedMenuItem.addClass('F3-TYPO3-UserInterface-BreadcrumbMenu-MenuItem-active');

		if (this._hasChildren(currentlyClickedMenuPath)) {
			this._hideSiblings(clickedMenuItem);
			this._renderArrow();
			this._renderLevel(currentlyClickedMenuPath);
		}

		F3.TYPO3.UserInterface.UserInterfaceModule.fireEvent('activate-' + currentlyClickedMenuPath, this);

		// show label
		clickedMenuItem.setStyle('width', clickedMenuItem.getTextWidth() + 'px');
	},

	/**
	 * Deactivate the currently clicked menu item.
	 *
	 * @param {Ext.Element} clickedMenuItem the clicked menu item element
	 * @return {void}
	 * @private
	 */
	_deactivateActiveItem: function(clickedMenuItem) {
		var currentlyClickedMenuPath = clickedMenuItem.getAttribute('data-menupath');

		clickedMenuItem.removeClass('F3-TYPO3-UserInterface-BreadcrumbMenu-MenuItem-active');
		clickedMenuItem.setStyle('width', null);

		// Show siblings again
		clickedMenuItem.parent().select('.F3-TYPO3-UserInterface-BreadcrumbMenu-MenuItem-hidden').removeClass('F3-TYPO3-UserInterface-BreadcrumbMenu-MenuItem-hidden');

		// Delete everything right of the parent node, i.e. higher levels of the menu, and fire the right events on it.
		var rightSibling = clickedMenuItem.parent().next();
		while (rightSibling) {
			rightSibling.select('.F3-TYPO3-UserInterface-BreadcrumbMenu-MenuItem-active').each(function(activeItem) {
				F3.TYPO3.UserInterface.UserInterfaceModule.fireEvent('deactivate-' + activeItem.getAttribute('data-menupath'), this);
			}, this);
			rightSibling.remove();
			rightSibling = clickedMenuItem.parent().next();
		}
		F3.TYPO3.UserInterface.UserInterfaceModule.fireEvent('deactivate-' + currentlyClickedMenuPath, this);
	},

	/**
	 * Does the given menuPath have children?
	 *
	 * @param {String} menupath menu path in the registry
	 * @return {Boolean} true if the menu path has children, false otherwise.
	 * @private
	 */
	_hasChildren: function(menupath) {
		var childNodes = F3.TYPO3.Core.Registry.get(menupath + '/children');
		return (childNodes && childNodes.length > 0);
	},

	/**
	 * Render an arrow between menu levels.
	 *
	 * @return {void}
	 * @private
	 */
	_renderArrow: function() {
		Ext.DomHelper.append(this.el, {
			tag: 'span',
			cls: 'F3-TYPO3-UserInterface-BreadcrumbMenu-Separator'
		});
	},

	/**
	 * Hide the siblings of the passed menu item
	 *
	 * @param {Ext.Element} menuItem the clicked menu item element
	 * @return {void}
	 * @private
	 */
	_hideSiblings: function(menuItem) {
		Ext.each(menuItem.dom.parentNode.children, function(sibling) {
			if (sibling !== menuItem.dom) {
				Ext.fly(sibling).addClass('F3-TYPO3-UserInterface-BreadcrumbMenu-MenuItem-hidden');
			}
		});
	},

	/**
	 * Callback method used in event handlers which listen to
	 * activate-* and deactivate-*, to find the ModuleMenu in which the
	 * breadcrumb menu is embedded
	 *
	 * @return {F3.TYPO3.UserInterface.ModuleMenu}
	 */
	getModuleMenu: function() {
		return this.findParentByType(F3.TYPO3.UserInterface.ModuleMenu);
	}
});
Ext.reg('F3.TYPO3.UserInterface.BreadcrumbMenuComponent', F3.TYPO3.UserInterface.BreadcrumbMenuComponent);