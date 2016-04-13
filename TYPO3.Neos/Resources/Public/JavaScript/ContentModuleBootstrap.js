window.T3 = {
	isContentModule: location.pathname.match(/@user-/)
} || window.T3;

requirePaths = window.T3Configuration.requirejs.paths || {};
requirePaths['Library'] = '../Library';
requirePaths['text'] = '../Library/requirejs/text';

/**
 * WARNING: if changing any of the require() statements below, make sure to also
 * update them inside build.js!
 */
require(
	{
		baseUrl: window.T3Configuration.neosJavascriptBasePath,
		urlArgs: window.T3Configuration.neosJavascriptVersion ? 'bust=' +  window.T3Configuration.neosJavascriptVersion : '',
		paths: requirePaths,
		context: 'neos'
	},
	[
		'Library/jquery-with-dependencies',
		'emberjs',
		'Content/ContentModule',
		'Content/ApplicationView',
		'Content/Components/PublishMenu',
		'Shared/ResourceCache',
		'Shared/Notification',
		'Shared/Configuration',
		'ExternalApi',
		'Library/underscore',
		'Shared/I18n',
		'Shared/NodeTypeService',
		'InlineEditing/PositioningHelper',
		'storage'
	],
	function(
		$,
		Ember,
		ContentModule,
		ApplicationView,
		PublishMenu,
		ResourceCache,
		Notification,
		Configuration,
		ExternalApi,
		_,
		I18n
	) {
		ResourceCache.fetch(Configuration.get('VieSchemaUri'));

		/**
		 * Load all translations, and then bootstrap the Neos interface
		 */
		Ember.RSVP.Promise(function (resolve, reject) {
			// Get all translations and merge them
			ResourceCache.getItem(Configuration.get('XliffUri')).then(function(labels) {
				try {
					$.extend(Ember.I18n.translations, labels);
					I18n.set('initialized', true);
				} catch (exception) {
					if ('localStorage' in window && 'showDevelopmentFeatures' in window.localStorage) {
						console.error('Could not parse JSON for locale file ' + labels[iterator].substr(5));
					}
				}
				resolve();
			});
		}).then(function () {
			// Bootstrap the content module
			Ember.$(document).ready(function () {
				ContentModule.bootstrap();
				ContentModule.advanceReadiness();

				// Wait until the NodeTypeService is usable by resolving the promise
				ResourceCache.getItem(Configuration.get('NodeTypeSchemaUri')).then(function () {
					ApplicationView.create().appendTo('#neos-application');
					if (window.T3.isContentModule) {
						PublishMenu.create().appendTo('#neos-top-bar-right');
					}
				});
			});
		}, function (reason) {
			console.log('Neos failed to initialize', reason);
		});

		// Export external Neos API
		window.Typo3Neos = ExternalApi;
	}
);
