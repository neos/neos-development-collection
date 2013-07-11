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
	return Component.extend({
		select: $('<select />'),
		_activeButton: null,
		_isOpen: false,

		/**
		 * @override
		 */
		init: function () {
			this._super();
			var that = this,
				element = this.element = $('<span>'),
				select = this.select;

			element.append(select);
			this.buttons = [];

			var buttons = this.getButtons();
			this._internalButtons = buttons;
			if (buttons.length === 0) {
				element.hide();
			}

			var options = [];
			$.each(buttons, function(index, value) {
				options.push($('<option />', {value: index, text: value.name}));
			});
			select.append(options);

			var selectedValue;
			select.off('change').on('change', function() {
				var value = $(this).val();
				if (value !== selectedValue) {
					selectedValue = value;
					buttons[value].click();
				}
			});

			$('body').click(function (event) {
				if (that._isOpen &&
					!that.element.is(event.target) &&
					0 === that.element.find(event.target).length) {
					that.close();
				}
			});
		},

		addButton: function() {
		},

		/**
		 * @api
		 */
		setActiveButton: function (name) {
			var select = this.select;
			if (!name) {
				select.val(0);
				return;
			}
			for (var i = 0; i < this._internalButtons.length; i++) {
				if (this._internalButtons[i].name === name) {
					select.val(i);
					select.trigger('liszt:updated');
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
			this.select.chosen({width: '130px', disable_search_threshold: 10});
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
});