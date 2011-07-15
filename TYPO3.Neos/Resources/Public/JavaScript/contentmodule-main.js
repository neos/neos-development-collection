require({
	paths: {
		'aloha': Aloha.settings.basePath + 'aloha',
		'util': Aloha.settings.basePath + 'util',
		'vendor': Aloha.settings.basePath + 'vendor',
		'dep': Aloha.settings.basePath + 'dep'
	}
},
[
	'order!vendor/jquery-1.6.1',
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