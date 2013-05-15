
window._requirejsLoadingTrace = [];
window.renderLoadingTrace = function() {
	return JSON.stringify(window._requirejsLoadingTrace);
};
/**
 * WARNING: if changing any of the require() statements below, make sure to also
 * update them inside build.js!
 */
require(
	{
		baseUrl: window.T3Configuration.neosJavascriptBasePath,
		urlArgs: window.localStorage.showDevelopmentFeatures ? 'bust=' +  (new Date()).getTime() : '',

		paths: {
			'Library': '../Library'
		},
		locale: 'en'
	},
	[
		'Library/jquery-with-dependencies',
		'emberjs',
		'neos/contentmodule',
		'neos/resourcecache',
		'storage'
	],
	function($, Ember, ContentModule) {
		if (window._requirejsLoadingTrace) window._requirejsLoadingTrace.push('contentmodule-main');
		var T3 = window.T3;
		T3.Configuration = window.T3Configuration;
		T3.ContentModule = ContentModule;
		delete window.T3Configuration;

		T3.ResourceCache.preload(T3.Configuration.VieSchemaUri);
		T3.ResourceCache.preload(T3.Configuration.NodeTypeSchemaUri);

		Ember.$(document).ready(function() {
			T3.ContentModule.bootstrap();

			Ext.Direct.on('exception', function(error) {
				T3.Content.Controller.ServerConnection.set('_failedRequest', true);
				T3.Common.Notification.error('ExtDirect error: ' + error.message);
				T3.ContentModule.hidePageLoaderSpinner();
			});

			ExtDirectInitialization();
		});
	}
);
