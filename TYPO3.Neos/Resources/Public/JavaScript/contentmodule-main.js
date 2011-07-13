require({
	paths: {
		'core': Aloha.settings.basePath + 'core',
		'util': Aloha.settings.basePath + 'util',
		'aloha': Aloha.settings.basePath + 'aloha',
		'dep': Aloha.settings.basePath + 'dep'
	}
},
[
	// HACK: Load first the aloha jQuery 1.5.1, and then directly override it with jQuery 1.6.1.
	// Lateron, Aloha should use a wrapped version.
	'order!dep/jquery-1.5.1',
	'order!libs/jquery-1.6.1.min',
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