Aloha.ready(function() {
	window.jQuery = Aloha.jQuery;

	require({
			baseUrl: window.phoenixJavascriptBasePath,
			urlArgs: window.localStorage.showDevelopmentFeatures ? 'bust=' +  (new Date()).getTime() : '',
			paths: {
				'Library': '../Library/'
			},
			locale: 'en'
		},
		[
		'order!Library/jquery-ui/js/jquery-ui-1.8.16.custom.min',
		'css!Library/jquery-ui/css/custom-theme/jquery-ui-1.8.16.custom.css',
		'order!Library/emberjs/ember-0.9.7' + (window.localStorage.showDevelopmentFeatures === 'true' ? '' : '.min'),
		'order!Library/jquery-lionbars/jQuery.lionbars.0.2.1',
		'order!phoenix/contentmodule'],
		function() {
			var T3 = window.T3;
			T3.Configuration = window.T3Configuration;
			delete window.T3Configuration;

			Ember.$(document).ready(function() {
				T3.ContentModule.bootstrap();

				Ext.Direct.on("exception", function(error) {
					T3.Common.Notification.error('ExtDirect error: ' + error.message);
				});

				// Because our ExtJS styles work only locally and not globally,
				// this breaks the extjs quicktip styling. Thus, we disable them
				// (affects Aloha)
				Ext.QuickTips.disable();

				ExtDirectInitialization();
			});
		}
	);
});