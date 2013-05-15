/*global define: true */
/**
 * The multiSplit component groups multiple buttons and other
 * button-like items into an expandable menu.
 */
define([
	'jquery',
	'ui/component',
	'ui/button',
	'ui/utils'
], function (
	$,
	Component,
	Button,
	Utils
) {
	'use strict';

	/**
	 * MultiSplit component type.
	 * @class
	 * @api
	 * @extends {Component}
	 */
	var MultiSplit = Component.extend({

		_activeButton: null,
		_isOpen: false,

		/**
		 * @override
		 */
		init: function () {
			this._super();
			var multiSplit = this;
			var element = this.element = $('<span>');
			var content = this.contentElement = $('<select>').css('width', 'inherit').appendTo(element);

			this.buttons = [];

			var buttons = this.getButtons();
			this._internalButtons = buttons;
			if (0 === buttons.length) {
				element.hide();
			}

			for (var i = 0; i < buttons.length; i++) {
				var option = $('<option />');
				option.attr('value', i);
				option.text(buttons[i].name);
				option.appendTo(content);
			}

			content.on('change', function() {
				buttons[content.val()].click();
			});

			$('body').click(function (event) {
				if (multiSplit._isOpen &&
			        !multiSplit.element.is(event.target) &&
			        0 === multiSplit.element.find(event.target).length) {
					multiSplit.close();
				}
			});
		},

		addButton: function() {
		},

		/**
		 * @api
		 */
		setActiveButton: function (name) {
			if (!name) {
				this.contentElement.val(0);
				return;
			}
			for (var i = 0; i < this._internalButtons.length; i++) {
				if (this._internalButtons[i].name === name) {
					this.contentElement.val(i);
					return;
				}
			}
		},

		/**
		 * Show the button with given index
		 * @api
		 * @param {Number} index button index
		 */
		show: function (name) {
			if (!name) {
				name = null;
			}
			if (null !== name && this.buttons[name] !== undefined) {
				this.buttons[name].element.show();
				this.buttons[name].visible = true;
				// since we show at least one button now, we need to show the multisplit button
				this.element.show();
			}
		},

		/**
		 * Hide the button with given index
		 * @api
		 * @param {Number} index button index
		 */
		hide: function (name) {
			var button, visible = false;

			if (!name) {
				name = null;
			}
			if (null !== name && this.buttons[name] !== undefined) {
				this.buttons[name].element.hide();
				this.buttons[name].visible = false;

				// now check, if there is a visible button
				for (button in this.buttons) {
					if (this.buttons.hasOwnProperty(button)) {
						if (this.buttons[button].visible) {
							this.element.show();
							visible = true;
							break;
						}
					}
				}

				if (!visible) {
					this.element.hide();
				}
			}
		}
	});

	/**
	 * This module is part of the Aloha API.
	 * It is valid to override this module via requirejs to provide a
	 * custom behaviour. An overriding module must implement all API
	 * methods. Every member must have an api annotation. No non-api
	 * members are allowed.
	 * @api
	 */
	return MultiSplit;
});
