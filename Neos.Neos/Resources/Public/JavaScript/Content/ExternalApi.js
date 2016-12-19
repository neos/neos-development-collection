define(
[
	'./ContentModule'
],
function(ContentModule) {
	var ExternalApi = {
		reloadPage: ContentModule.reloadPage.bind(ContentModule)
	};
	return ExternalApi;
});