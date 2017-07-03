function initializeRequirejsConfig(pathToPublicJavaScriptFolder) {
	var testBasePath = window.location.pathname.replace(/Tests\/JavaScript\/.*/, 'Tests/JavaScript/');
	window.T3Configuration = {
		locale: 'en'
	};

	require.config({
		baseUrl: pathToPublicJavaScriptFolder,
		paths: {
			'Library': '../Library',
			'text': '../Library/requirejs/text',
			'Mock': testBasePath + 'Mock'
		}
	});
}