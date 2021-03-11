window.T3 = {
	isContentModule: false
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
		context: 'neos',
		waitSeconds: window.T3Configuration.UserInterface.requireJsWaitSeconds

	},
	[
		'Library/jquery-with-dependencies',
		'emberjs',
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
		ResourceCache,
		Notification,
		Configuration,
		ExternalApi,
		_,
		I18n
	) {
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
		});

		// Export external Neos API
		window.Typo3Neos = ExternalApi;
		window.NeosCMS = Object.assign(!!window.NeosCMS ? window.NeosCMS : {}, ExternalApi);
	}
);

