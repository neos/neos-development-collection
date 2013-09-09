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
		'Shared/Notification',
		'Shared/Configuration',
		'storage'
	],
	function(Ember, ContentModule, ApplicationView, PublishMenu, ResourceCache, Notification, Configuration) {
		var T3 = window.T3;

		ResourceCache.fetch(Configuration.get('VieSchemaUri'));
		ResourceCache.fetch(Configuration.get('NodeTypeSchemaUri'));

		Ember.$(document).ready(function() {
			ContentModule.bootstrap();

			Ext.Direct.on('exception', function(error) {
				T3.Content.Controller.ServerConnection.set('_failedRequest', true);
				Notification.error('ExtDirect error: ' + error.message);
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