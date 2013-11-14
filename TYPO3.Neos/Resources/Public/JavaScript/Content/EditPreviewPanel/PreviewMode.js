define(
[
	'./Mode',
	'Library/jquery-with-dependencies',
	'create'
], function(
	Mode,
	$,
	CreateJS
) {
	return Mode.extend({
		isPreviewMode: true,

		_hideControlsWhileInPreviewMode: function() {
			$('body').addClass('neos-previewmode');
			CreateJS.disableEdit();
			//EventDispatcher.triggerExternalEvent('Neos.EnablePreview', 'Neos preview is enabled');
		}.on('activate'),

		_hideControlsWhileInPreviewModeOnPageLoad: function() {
			var that = this;
			// HACK in sync with 800 ms timeout in CreateJS
			window.setTimeout(function() {
				that._hideControlsWhileInPreviewMode();
			}, 2000);
		}.on('activateOnPageLoad'),

		_showControlsWhileLeavingPreviewMode: function() {
			$('body').removeClass('neos-previewmode');
			CreateJS.enableEdit();
			//EventDispatcher.triggerExternalEvent('Neos.DisablePreview', 'Neos preview is disabled.');
		}.on('deactivate')
	});
});