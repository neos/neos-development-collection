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
		urlArgs: window.T3Configuration.neosJavascriptVersion ? 'bust=' +  window.T3Configuration.neosJavascriptVersion : '',
		paths: requirePaths,
		context: 'neos',
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
		'ExternalApi',
		'Shared/NodeTypeService',
		'Shared/HttpClient',
		'InlineEditing/PositioningHelper',
		'storage'
	],
	function(
		Ember,
		ContentModule,
		ApplicationView,
		PublishMenu,
		ResourceCache,
		Notification,
		Configuration,
		ExternalApi
	) {
		ResourceCache.fetch(Configuration.get('VieSchemaUri'));

		Ember.$(document).ready(function() {
			ContentModule.bootstrap();

			ContentModule.advanceReadiness();

			// Wait until the NodeTypeService is usable by resolving the promise
			ResourceCache.getItem(Configuration.get('NodeTypeSchemaUri')).then(function() {
				ApplicationView.create().appendTo('#neos-application');
				if (window.T3.isContentModule) {
					PublishMenu.create().appendTo('#neos-top-bar-right');
				}
			});
		});

		// Export external Neos API
		window.Typo3Neos = ExternalApi;
	}
);