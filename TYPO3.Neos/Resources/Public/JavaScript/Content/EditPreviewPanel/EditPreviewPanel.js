define(
[
	'emberjs',
	'./EditPreviewPanelController',
	'text!./EditPreviewPanel.html'
], function(
	Ember,
	EditPreviewPanelController,
	template
) {
	return Ember.View.extend({
		elementId: 'neos-views-panel',
		template: Ember.Handlebars.compile(template),
		isVisibleBinding: 'controller.visible',

		controller: EditPreviewPanelController
	});
});