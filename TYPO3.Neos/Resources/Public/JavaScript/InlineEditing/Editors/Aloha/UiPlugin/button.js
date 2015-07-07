define([
	'originalButton',
	'ui/utils'
], function (
	OriginalButton,
	Utils
) {
	'use strict';

	// We patch this class to remove jQuery UI tooltip, replacing it by native browser titles.
	return OriginalButton.extend({
		init: function () {
			this.createButtonElement();
			Utils.makeButton(this.buttonElement, this)
				.uibutton('widget')
				.click(Aloha.jQuery.proxy(function () {
					this._onClick();
				}, this));
		},

		closeTooltip: function() {},

		adoptParent: function () {}
	});
});
