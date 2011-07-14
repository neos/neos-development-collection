require({
	paths: {
		'aloha': Aloha.settings.basePath + 'aloha',
		'util': Aloha.settings.basePath + 'util',
		'vendor': Aloha.settings.basePath + 'vendor',
		'dep': Aloha.settings.basePath + 'dep',
		'Library': Aloha.settings.basePath + '../../../'
	}
},
[
	'order!vendor/jquery-1.6.1',
	'order!Library/jquery-ui/js/jquery-ui-1.8.14.custom.min',
	'css!Library/jquery-ui/css/ui-darkness/jquery-ui-1.8.14.custom.css',
	'order!libs/sproutcore',
	'order!aloha',
	'order!phoenix/contentmodule'],
function() {
	var T3 = window.T3;

	SC.$(document).ready(function() {
		T3.ContentModule.bootstrap();
		ExtDirectInitialization();
	});

});