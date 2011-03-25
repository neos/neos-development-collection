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
	 * @var F3.TYPO3.UserInterface.BreadcrumbMenu.AnimationHandler
	 * @private
	 */
	_animationHandler: null,

	/**
	 * @var {string]
	 * @private
	 */
	_activeMenuPath: null,


	/**
	 * If true, all animations will run a lot slower for better debugging.
	 *
	 * @cfg boolean
	 * @private
	 */
	_debugMode: false,

	/**
	 * @return {void}
	 */
	initComponent: function() {

		var config = {
			cls: 'F3-TYPO3-UserInterface-BreadcrumbMenu',
			height: '47px',
			_animationHandler: new F3.TYPO3.UserInterface.BreadcrumbMenu.AnimationHandler()
		};

		if (this._debugMode) {
			config.cls += ' F3-TYPO3-UserInterface-BreadcrumbMenu-Debug';
			config._animationHandler._defaultAnimationDuration = 500;
		}

		Ext.apply(this, config);

		F3.TYPO3.UserInterface.BreadcrumbMenuComponent.superclass.initComponent.call(this);

		this.on('afterrender', function() {
			this._renderLevel(this.basePath);
			this._animationHandler.start();
		}, this);
	},

	/**
	 * Event handler triggered when a menu item is clicked.
	 *
	 * @param {Ext.EventObject} event
	 * @param {DOMElement} clickedMenuItemDomElement the MenuItem which is clicked
	 * @return {void}
	 * @private
	 */
	_onMenuItemClick: function(event, clickedMenuItemDomElement) {
		if (this._animationHandler.isRunning()) return;

		var clickedMenuItem = Ext.get(clickedMenuItemDomElement);
		if (clickedMenuItem.hasClass('F3-TYPO3-UserInterface-BreadcrumbMenu-MenuItem-active')) {
			this._shouldDeactivateItem(clickedMenuItem);
		} else {
			this._shouldActivateItem(clickedMenuItem);
		}
	},

	/**
	 * Activates the currently clicked menu item.
	 *
	 * @param {Ext.Element} clickedMenuItem the clicked menu item element
	 * @return {void}
	 * @private
	 */
	_shouldActivateItem: function(clickedMenuItem) {
		var currentlyClickedMenuPath = clickedMenuItem.getAttribute('data-menupath');

		if (currentlyClickedMenuPath === this._activeMenuPath) {
			return null;
		}
		this._activeMenuPath = currentlyClickedMenuPath;

			// If a sibling of the to-be-activated node is active, deactivate it
		clickedMenuItem.parent().select('.F3-TYPO3-UserInterface-BreadcrumbMenu-MenuItem-active').each(function(activeItem) {
			this._shouldDeactivateItem(activeItem);
		}, this);

		if (this._hasChildren(currentlyClickedMenuPath)) {
			this._animationHandler.hideSiblings(clickedMenuItem);
		}

		this._animationHandler.addClass(clickedMenuItem, 'F3-TYPO3-UserInterface-BreadcrumbMenu-MenuItem-active');

		this._animationHandler.renderMenuItemLabel(clickedMenuItem);

		if (this._hasChildren(currentlyClickedMenuPath)) {
			this._animationHandler.renderArrow(this.el);
			this._renderLevel(currentlyClickedMenuPath);
		}

		this._animationHandler.add(function() {
			this._activeMenuPath = null;
		}.createDelegate(this), 5);

		this._animationHandler.start();
		F3.TYPO3.UserInterface.UserInterfaceModule.fireEvent('activate-' + currentlyClickedMenuPath, this);
	},

	/**
	 * Deactivate the currently clicked menu item.
	 *
	 * @param {Ext.Element} clickedMenuItem the clicked menu item element
	 * @return {void}
	 * @private
	 */
	_shouldDeactivateItem: function(clickedMenuItem) {
		var currentlyClickedMenuPath = clickedMenuItem.getAttribute('data-menupath');

		this._removeLowerLevels(clickedMenuItem);
		this._animationHandler.removeMenuItemLabel(clickedMenuItem);

		this._animationHandler.removeClass(clickedMenuItem, 'F3-TYPO3-UserInterface-BreadcrumbMenu-MenuItem-active');

		this._animationHandler.showSiblings(clickedMenuItem);

		this._animationHandler.start();
		F3.TYPO3.UserInterface.UserInterfaceModule.fireEvent('deactivate-' + currentlyClickedMenuPath, this);
	},

	/**
	 * Schedules rendering a new level of the menu, on the right side of all currently
	 * displayed elements:
	 * - First, adds a container for the new level
	 * - Then, in a loop, adds each child individually and removes the rendering classes
	 *
	 * @param {String} basePath the base path to render right now.
	 * @return {void}
	 * @private
	 */
	_renderLevel: function(basePath) {
		var levelContainer;
		var scope = this;
		this._animationHandler.add(function() {
			levelContainer = Ext.DomHelper.append(scope.el, {
				tag: 'span',
				cls: 'F3-TYPO3-UserInterface-BreadcrumbMenu-SingleLevel'
			});
		});

		Ext.each(F3.TYPO3.Core.Registry.get(basePath + '/children'), function(menuItem) {
			var singleElement;
			scope._animationHandler.add(function() {
				singleElement = Ext.DomHelper.append(levelContainer, {
					tag: 'span',
					cls: 'F3-TYPO3-UserInterface-BreadcrumbMenu-MenuItem-onRender ' + menuItem.iconCls,
					'data-menupath': basePath + '/children/' + menuItem.key,
					'data-label': menuItem.text
				});
			}, 20);
			scope._animationHandler.add(function() {
				Ext.fly(singleElement).removeClass('F3-TYPO3-UserInterface-BreadcrumbMenu-MenuItem-onRender');
				Ext.fly(singleElement).addClass('F3-TYPO3-UserInterface-BreadcrumbMenu-MenuItem');
				Ext.fly(singleElement).on('click', scope._onMenuItemClick, scope);
			});
		});
	},

	/**
	 * Remove all levels 'below' (right of) a certain menuitem
	 *
	 * @param {Ext.Element} clickedMenuItem
	 * @return {void}
	 */
	_removeLowerLevels: function(clickedMenuItem) {
		/**
		 * Delete everything right of the parent node, i.e. higher levels of the menu, and fire the right events on it.
		 * We do this from the right to the left to have a fluent visual effect
		 */
		var elmts = [];

		var currentLevel = clickedMenuItem.findParent('.F3-TYPO3-UserInterface-BreadcrumbMenu-SingleLevel', 5, true);
		while (currentLevel.next()) {
			elmts.push(currentLevel.next());
			currentLevel = currentLevel.next();
		}

			// Reverse the items so we work from the right to the left
		elmts.reverse();
		Ext.each(elmts, function(singleLevel) {
			if (singleLevel.hasClass('F3-TYPO3-UserInterface-BreadcrumbMenu-SingleLevel')) {
				this._removeSingleLevel(singleLevel);
			} else if (singleLevel.hasClass('F3-TYPO3-UserInterface-BreadcrumbMenu-Separator')) {
				this._animationHandler.removeArrow(singleLevel.dom);
			}
		}, this);
	},

	/**
	 * Remove a single level of the menu, and fire deactivate events on active subitems
	 *
	 * @param {DOMElement} singleLevel
	 * @return {Void}
	 */
	_removeSingleLevel: function(singleLevel) {
		Ext.get(singleLevel).select('.F3-TYPO3-UserInterface-BreadcrumbMenu-MenuItem-active').each(function(activeItem) {
			F3.TYPO3.UserInterface.UserInterfaceModule.fireEvent('deactivate-' + activeItem.getAttribute('data-menupath'), this);
		}, this);
		var elmts = [];
		var currentElement = Ext.get(singleLevel).first();
		while (currentElement) {
			elmts.push(currentElement);
			currentElement = currentElement.next();
		}
		elmts.reverse();
		Ext.each(elmts, function(singleElement) {
			if (singleElement.hasClass('F3-TYPO3-UserInterface-BreadcrumbMenu-MenuItem')) {
				this._animationHandler.addClass(singleElement, 'F3-TYPO3-UserInterface-BreadcrumbMenu-MenuItem-hidden');
				this._animationHandler.add(function() {
					singleElement.remove();
				}, 20);
			}
		}, this);
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
	 * Callback method used in event handlers which listen to
	 * activate-* and deactivate-*, to find the ModuleMenu in which the
	 * breadcrumb menu is embedded
	 *
	 * @return {F3.TYPO3.UserInterface.ModuleMenu}
	 */
	getModuleMenu: function() {
		return this.findParentByType(F3.TYPO3.UserInterface.ModuleMenu);
	},

	/**
	 * Activate the given menu path, if it is not activated yet.
	 *
	 * @param {String} menupath menu path in the registry
	 * @return {void}
	 */
	activateItem: function(menupath) {
		var targetElement;
		targetElement = this.el.select('*[data-menupath=' + F3.TYPO3.Core.Registry.rewritePath(menupath) + ']').first();
		if (targetElement.hasClass('F3-TYPO3-UserInterface-BreadcrumbMenu-MenuItem-active')) {
			return;
		}
		this._shouldActivateItem(targetElement);
	},

	/**
	 * Deactivate the given menu path, if it is not deactivated yet.
	 *
	 * @param {String} menupath menu path in the registry
	 * @return {void}
	 */
	deactivateItem: function(menupath) {
		var targetElement;
		targetElement = this.el.select('*[data-menupath=' + F3.TYPO3.Core.Registry.rewritePath(menupath) + ']').first();
		if (!targetElement.hasClass('F3-TYPO3-UserInterface-BreadcrumbMenu-MenuItem-active')) {
			return;
		}
		this._shouldDeactivateItem(targetElement);
	}
});

Ext.reg('F3.TYPO3.UserInterface.BreadcrumbMenuComponent', F3.TYPO3.UserInterface.BreadcrumbMenuComponent);