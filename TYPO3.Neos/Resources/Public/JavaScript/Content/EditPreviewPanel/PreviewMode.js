define(
[
	'./Mode',
	'Library/jquery-with-dependencies',
	'Shared/EventDispatcher',
	'Content/Model/NodeSelection'
], function(
	Mode,
	$,
	EventDispatcher,
	NodeSelection
) {
	return Mode.extend({
		isPreviewMode: true,

		_hideControlsWhileInPreviewMode: function() {
			$('body').addClass('neos-preview-mode');
			require(
				{context: 'neos'},
				[
					'create'
				],
				function(CreateJS) {
					CreateJS.disableEdit();
					if (NodeSelection.get('selectedNode')) {
						NodeSelection.updateSelection();
					}
				}
			);
			EventDispatcher.triggerExternalEvent('Neos.PreviewModeDeactivated', 'Neos preview mode was deactivated.');
		}.on('activate'),

		_showControlsWhileLeavingPreviewMode: function() {
			$('body').removeClass('neos-preview-mode');
			require(
				{context: 'neos'},
				[
					'create'
				],
				function(CreateJS) {
					CreateJS.enableEdit();
				}
			);
			EventDispatcher.triggerExternalEvent('Neos.PreviewModeActivated', 'Neos preview was activated.');
		}.on('deactivate')
	});
});