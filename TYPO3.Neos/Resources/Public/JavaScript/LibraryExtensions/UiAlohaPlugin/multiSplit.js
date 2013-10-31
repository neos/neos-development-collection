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
		_chosenInitialized: false,

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
				options.push($('<option />', {value: index, text: value.tooltip}));
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
			if (name === null) {
				name = 'removeFormat';
			}

			for (var i = 0; i < this._internalButtons.length; i++) {
				if (this._internalButtons[i].name === name) {
					this.select.val(i);

					if (!this._chosenInitialized) {
						this.select.chosen({width: '185px', disable_search_threshold: 10, display_disabled_options: false});
						this._chosenInitialized = true;
					}

					this.select.trigger('chosen:updated.chosen');
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

			for (var i = 0; i < this._internalButtons.length; i++) {
				if (this._internalButtons[i].name === name) {
					$(this.select.children('option').get(i)).removeAttr('disabled');
				}
			}

			this.select.trigger('chosen:updated.chosen');
		},

		/**
		 * Hide the button with given index
		 * @api
		 * @param {Number} index button index
		 */
		hide: function (name) {
			if (!name) {
				name = null;
			}
			for (var i = 0; i < this._internalButtons.length; i++) {
				if (this._internalButtons[i].name === name) {
					$(this.select.children('option').get(i)).attr('disabled', 'disabled');
				}
			}
			this.select.trigger('chosen:updated.chosen');
		}
	});
});