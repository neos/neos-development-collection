define(
[
	'emberjs'
], function(
	Ember
) {
	return Ember.Object.extend({
		identifier: 'to-be-defined',
		title: 'to-be-defined',
		active: false,
		isEditingMode: false,
		isPreviewMode: false

		// Events: activateOnPageLoad, activate, deactivate
	});
});