window._requirejsLoadingTrace = [];
window.renderLoadingTrace = function() {
	return JSON.stringify(window._requirejsLoadingTrace);
};

window.T3 = {
	isContentModule: location.pathname.substr(0, 6) !== '/neos/'
} || window.T3;
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
		'emberjs',
		'Content/ContentModule',
		'Content/ApplicationView',
		'Content/Components/PublishMenu',
		'Shared/ResourceCache',
		'storage'
	],
	function(Ember, ContentModule, ApplicationView, PublishMenu, ResourceCache) {
		var T3 = window.T3;
		T3.Configuration = window.T3Configuration;
		delete window.T3Configuration;

		ResourceCache.preload(T3.Configuration.VieSchemaUri);
		ResourceCache.preload(T3.Configuration.NodeTypeSchemaUri);

		Ember.$(document).ready(function() {
			ContentModule.bootstrap();

			Ext.Direct.on('exception', function(error) {
				T3.Content.Controller.ServerConnection.set('_failedRequest', true);
				T3.Common.Notification.error('ExtDirect error: ' + error.message);
				ContentModule.hidePageLoaderSpinner();
			});

			ExtDirectInitialization();

			ContentModule.advanceReadiness();
			ApplicationView.create().appendTo('#neos-application');
			if (window.T3.isContentModule) {
				PublishMenu.create().appendTo('#neos-user-actions');
			}

		});
	}
);
