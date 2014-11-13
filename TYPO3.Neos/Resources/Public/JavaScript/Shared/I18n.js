/**
 * Localization for the UserInterface
 */
define(
[
	'Library/jquery-with-dependencies',
	'Library/underscore',
	'Shared/Configuration',
	'Library/handlebars'
], function(
	$,
	_,
	Configuration,
	Handlebars
) {
	var localeIncludes = Configuration.get('localeIncludes'),
		promise = Ember.Deferred.create();

		Handlebars.registerHelper('translate', function() {
			var options = arguments[0].hash,
				translateId = options['id'].replace(/\./g, "-"),
				defaultContent = options['value'] ? options['value'] : '',
				packageKey = options['package'] ? options['package'] : 'TYPO3.Neos',
				sourceCatalog = options['source'] ? options['source'] : 'Main',
				translatedById;

			// {{translate}}
			if (!options['id']) {
				return Ember.I18n.translate('TYPO3.Neos.translate.requiredProperty') + 'id';
			}
			// {{translate id="foo"}}
			if (translateId.length > 0) {
				// Set the translateId to the correct path
				translateId = packageKey + '.' + sourceCatalog + '.' + translateId;

				// Handle the actual translation in case defaultContent is empty
				if (translateId && defaultContent == '' && !arguments[0].fn) {
					translatedById = Ember.I18n.translate(translateId);
					return translatedById;
				}
				// {{#translate id="foo"}}this is the default content{{/translate}}
				if (arguments[0].fn) {
					defaultContent = arguments[0].fn(this);
				}
				// Handle the actual translation
				if (translateId && defaultContent) {
					translatedById = Ember.I18n.translate(translateId);
					// Make sure Embers.I18n output does not interfere with ours
					if (translatedById.substr(0, 21) === 'Missing translation: ') {
						return defaultContent;
					}
					return translatedById;
				}

			}

			return '';
		});

	localeIncludes = _.map(localeIncludes, function(filename) {
		return 'text!' + filename;
	});

	// Get all translations and merge them
	require({context: 'neos'}, localeIncludes, function() {
		_.each(arguments, function(localeInclude, iterator) {
			try {
				$.extend(Ember.I18n.translations, $.parseJSON(localeInclude));
			} catch (exception) {
				if ('localStorage' in window && 'showDevelopmentFeatures' in window.localStorage) {
					console.error('Could not parse JSON for locale file ' + localeIncludes[iterator].substr(5));
				}
			}
		});
		promise.resolve();
	});

	return promise;
});