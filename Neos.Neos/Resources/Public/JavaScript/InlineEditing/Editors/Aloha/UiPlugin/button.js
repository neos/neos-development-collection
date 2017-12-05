define([
	'originalButton'
], function (
	OriginalButton
) {
	'use strict';
	return OriginalButton.extend({
		// Needed for the custom table buttons
		adoptParent: function () {}
	});
});
