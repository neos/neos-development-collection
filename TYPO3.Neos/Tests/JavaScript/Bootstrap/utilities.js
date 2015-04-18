function initializeRequirejsConfig(pathToPublicJavaScriptFolder) {
	var testBasePath = window.location.pathname.replace(/Tests\/JavaScript\/.*/, 'Tests/JavaScript/');

	require.config({
		baseUrl: pathToPublicJavaScriptFolder,
		paths: {
			'Library': '../Library/',
			'text': '../Library/requirejs/text',
			'Mock': testBasePath + 'Mock/'
		}
	});
}