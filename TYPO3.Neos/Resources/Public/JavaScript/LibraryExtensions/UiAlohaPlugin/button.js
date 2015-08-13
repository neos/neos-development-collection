define([
	'originalButton',
	'ui/utils'
], function (
	OriginalButton,
	Utils
) {
	'use strict';

	// We patch this class to remove jQuery tooltip, replacing it by standard in-browser tooltips.
	return OriginalButton.extend({
		init: function () {
			this.createButtonElement();
			Utils.makeButton(this.buttonElement, this)
				.button('widget')
				.click(Aloha.jQuery.proxy(function () {
					this._onClick();
				}, this));
		},

		adoptParent: function (container) {
		}
	});
});
