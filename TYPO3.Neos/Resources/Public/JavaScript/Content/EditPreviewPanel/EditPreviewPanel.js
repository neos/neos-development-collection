define(
[
	'emberjs',
	'Library/jquery-with-dependencies',
	'./EditPreviewPanelController',
	'text!./EditPreviewPanel.html'
], function(
	Ember,
	$,
	EditPreviewPanelController,
	template
) {
	return Ember.View.extend({
		elementId: 'neos-edit-preview-panel',
		template: Ember.Handlebars.compile(template),
		isVisibleBinding: 'controller.visible',

		controller: EditPreviewPanelController,

		onEditPreviewPanelModeChanged: function() {
			if (this.get('controller.editPreviewPanelMode') === true) {
				$('body').addClass('neos-edit-preview-panel-open');
			} else {
				$('body').removeClass('neos-edit-preview-panel-open');
			}
		}.observes('controller.editPreviewPanelMode').on('init')
	});
});