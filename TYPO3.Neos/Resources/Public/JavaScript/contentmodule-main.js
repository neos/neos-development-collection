
// Fine-tune some Aloha-SmartContentChange settings, making the whole system feel more responsive.
Aloha = window.Aloha || {};
Aloha.settings = Aloha.settings || {};
Aloha.settings.smartContentChange = Aloha.settings.smartContentChange || {};
Aloha.settings.smartContentChange.idle = 500;
Aloha.settings.smartContentChange.delay = 150;

require({
	paths: {
		'aloha': Aloha.settings.basePath + 'aloha',
		'util': Aloha.settings.basePath + 'util',
		'vendor': Aloha.settings.basePath + 'vendor',
		'dep': Aloha.settings.basePath + 'dep',
		'Library': '../Library/'
	}
},
['aloha'],
function() {
	window.jQuery = window.alohaQuery;

	require([
		'order!Library/jquery-ui/js/jquery-ui-1.8.14.custom.min',
		'css!Library/jquery-ui/css/ui-darkness/jquery-ui-1.8.14.custom.css',
		'order!Library/sproutcore/sproutcore-2.0.beta.3',
		'order!phoenix/contentmodule'],
		function() {
			var T3 = window.T3;
			T3.Configuration = window.T3Configuration;
			delete window.T3Configuration;

			SC.$(document).ready(function() {
				T3.ContentModule.bootstrap();

				Ext.Direct.on("exception", function(error) {
					T3.Common.Notification.error('ExtDirect error: ' + error.message);
				});

				ExtDirectInitialization();
			});
		}
	);
});
