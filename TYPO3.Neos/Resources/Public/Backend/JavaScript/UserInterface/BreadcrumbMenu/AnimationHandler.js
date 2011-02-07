Ext.namespace('F3.TYPO3.UserInterface.BreadcrumbMenu');

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
 * @class F3.TYPO3.UserInterface.BreadcrumbMenu.AnimationHandler
 *
 * Animation handler for the BreadcrumbMenuComponent.
 * Queues animations and triggers them in the right order.
 *
 * @namespace F3.TYPO3.UserInterface.BreadcrumbMenu
 */

F3.TYPO3.UserInterface.BreadcrumbMenu.AnimationHandler = function() {
	this._queue = new F3.TYPO3.Queue.TimeBasedQueue();
}

F3.TYPO3.UserInterface.BreadcrumbMenu.AnimationHandler = Ext.extend(F3.TYPO3.UserInterface.BreadcrumbMenu.AnimationHandler, {

	/**
	 * @private
	 */
	_defaultAnimationDuration: 50,

	/**
	 * @var F3.TYPO3.Queue.TimeBasedQueue
	 * @private
	 */
	_queue: null,

	/**
	 * Start the queue
	 *
	 * @return {void}
	 */
	start: function() {
		this._queue.start();
	},

	/**
	 * Add an action call to the animationHandler
	 *
	 * @param {Function} fn functionality to call
	 * @param {Integer} delayAfter time in milliseconds to wait after calling the method
	 * @return {void}
	 */
	add: function (fn, delayAfter) {
		if (Ext.isEmpty(delayAfter)) {
			delayAfter = this._defaultAnimationDuration;
		}
		this._queue.add(fn, delayAfter);
	},

	/**
	 * Returns if the queue is running right now.
	 *
	 * @return {Boolean} true if the queue is running, false otherwise.
	 */
	isRunning: function() {
		return this._queue.isRunning();
	},

	/**
	 * Add a delay to the queue
	 *
	 * @param {Integer} delay
	 * @return {void}
	 */
	addDelay: function(delay) {
		this._queue.add(function() {}, delay);
	},

	/**
	 * @param {Ext.Element} element DOMElement to add the class to
	 * @param {String} className name of the CSS class
	 * @param {Integer} duration time in milliseconds an possible CSS transition would take
	 * @return {void}
	 */
	addClass: function (element, className, duration) {
		this.add(
			function() {
				element.addClass(className);
			},
			duration
		);
	},

	/**
	 * @param {Ext.Element} element DOMElement to remove the class from
	 * @param {String} className name of the CSS class
	 * @param {Integer} duration time in milliseconds an possible CSS transition would take
	 * @return {void}
	 */
	removeClass: function (element, className, duration) {
		this.add(
			function() {
				element.removeClass(className);
			},
			duration
		);
	},

	/**
	 * Hide the siblings of the passed menu item
	 *
	 * @param {Ext.Element} menuItem the clicked menu item element
	 * @return {void}
	 */
	hideSiblings: function(menuItem) {
			// Traverse all siblings backwards
		var i = menuItem.dom.parentNode.children.length - 1;
		do {
			if (    !Ext.isEmpty(menuItem.dom.parentNode.children[i]) &&
					menuItem.dom.parentNode.children[i] !== menuItem.dom &&
					Ext.get(menuItem.dom.parentNode.children[i]).hasClass('F3-TYPO3-UserInterface-BreadcrumbMenu-MenuItem')) {
				this.addClass(Ext.get(menuItem.dom.parentNode.children[i]), 'F3-TYPO3-UserInterface-BreadcrumbMenu-MenuItem-hidden');
			}
			i --;
		} while (i >= 0);
	},

	/**
	 * Show the siblings of a menu item
	 *
	 * @param {Ext.Element} menuItem the clicked menu item element
	 * @return {Void}
	 */
	showSiblings: function (menuItem) {
		menuItem.parent().select('.F3-TYPO3-UserInterface-BreadcrumbMenu-MenuItem-hidden').each(function(element) {
			this.removeClass(Ext.get(element.dom), 'F3-TYPO3-UserInterface-BreadcrumbMenu-MenuItem-hidden');
		}, this);
	},

	/**
	 * Render a label for the given menu item.
	 *
	 * @param {Ext.Element} menuItem menu item which the label has to be added to
	 * @return {void}
	 */
	renderMenuItemLabel: function(menuItem) {
		var labelElement;

		this.add(function() {
			labelElement = Ext.DomHelper.insertAfter(menuItem, {
				tag: 'span',
				cls: 'F3-TYPO3-UserInterface-BreadcrumbMenu-MenuItem-Text',
				children: menuItem.getAttribute('data-label')
			});
			labelElement = Ext.get(labelElement);
			labelElement.setWidth(labelElement.getTextWidth());
		});

		this.addDelay(5);
		this.add(function() {
			labelElement.addClass('F3-TYPO3-UserInterface-BreadcrumbMenu-MenuItem-Text-active');
		}, 0);
	},

	/**
	 * Remove a label for the given menu item
	 *
	 * @param {Ext.Element} menuItem menu item for which the label should be removed
	 * @return {void}
	 */
	removeMenuItemLabel: function(menuItem) {
		this.add(function() {
			if (menuItem.next() && menuItem.next().hasClass('F3-TYPO3-UserInterface-BreadcrumbMenu-MenuItem-Text')) {
				menuItem.next().removeClass('F3-TYPO3-UserInterface-BreadcrumbMenu-MenuItem-Text-active');
				menuItem.next().setWidth(0);
			}
		});

		this.add(function() {
			if (menuItem.next() && menuItem.next().hasClass('F3-TYPO3-UserInterface-BreadcrumbMenu-MenuItem-Text')) {
				menuItem.next().remove();
			}
		});
	},

	/**
	 * Render an arrow as the last element of the passed container, and fade it in.
	 *
	 * @param {DOMElement} container the container to add the arrow to.
	 * @return {void}
	 */
	renderArrow: function(container) {
		var element;

		this.add(function() {
			element = Ext.DomHelper.append(container, {
				tag: 'span',
				cls: 'F3-TYPO3-UserInterface-BreadcrumbMenu-Separator',
				style: 'display: inline-block'
			});

			element = Ext.get(element);
		});

		this.add(function() {
			element.addClass('F3-TYPO3-UserInterface-BreadcrumbMenu-Separator-active');
		});
	},

	/**
	 * Remove the given arrow
	 *
	 * @param {DOMElement} element the arrow element to remove
	 * @return {void}
	 */
	removeArrow: function(element) {
		element = Ext.get(element);
		this.removeClass(element, 'F3-TYPO3-UserInterface-BreadcrumbMenu-Separator-active');
		this.add(function() {
			element.remove();
		});
	}
});

Ext.reg('F3.TYPO3.UserInterface.BreadcrumbMenu.AnimationHandler', F3.TYPO3.UserInterface.BreadcrumbMenu.AnimationHandler);