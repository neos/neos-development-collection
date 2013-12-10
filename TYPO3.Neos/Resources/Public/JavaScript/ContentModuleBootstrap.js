window.T3 = {
	isContentModule: location.pathname.match(/@user-/)
} || window.T3;

requirePaths = window.T3Configuration.requirejs.paths || {};
requirePaths['Library'] = '../Library';
requirePaths['text'] = '../Library/requirejs/text';
requirePaths['i18n'] = '../Library/requirejs/i18n';

/**
 * WARNING: if changing any of the require() statements below, make sure to also
 * update them inside build.js!
 */
require(
	{
		baseUrl: window.T3Configuration.neosJavascriptBasePath,
		urlArgs: window.localStorage.showDevelopmentFeatures ? 'bust=' +  (new Date()).getTime() : '',
		paths: requirePaths,
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
		'InlineEditing/PositioningHelper',
		'storage'
	],
	function(Ember, ContentModule, ApplicationView, PublishMenu, ResourceCache, Notification, Configuration) {
		var T3 = window.T3;

		ResourceCache.fetch(Configuration.get('VieSchemaUri'));
		ResourceCache.fetch(Configuration.get('NodeTypeSchemaUri'));
		// We have to preload the Document and ContentCollection children for the ContentElementHandles which
		// need them in early stage.
		ResourceCache.fetch(Configuration.get('NodeTypeSchemaUri') + '&superType=TYPO3.Neos:Document');
		ResourceCache.fetch(Configuration.get('NodeTypeSchemaUri') + '&superType=TYPO3.Neos:ContentCollection');

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
				PublishMenu.create().appendTo('#neos-top-bar-right');
			}
		});
	}
);