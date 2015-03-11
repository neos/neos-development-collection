/**
 * Localization for the UserInterface
 *
 * Usage examples:
 *
 * Value data bind or parsed as string::
 *
 *   {{translate value="Default label"}}
 *
 * Fallback and id::
 *
 *   {{translate fallback="Default label" id="button.createNew"}}
 *
 * Value, id and package::
 *
 *   {{translate fallback="Default label" id="createNew" package="Your.Package"}}
 *
 * Data binding::
 *
 *   {{translate idBinding="view.label" fallback="Default label"}}
 *   {{translate idBinding="view.label" fallbackBinding="view.fallbackLabel"}}
 *
 * All arguments are allowed to data bind.
 * Allowed identifier combinations::
 *
 *   i18n:Catalog:Package.Key:Identifier
 *   i18n:Package.Key:Identifier
 *   i18n:Catalog:Identifier
 *   i18n:Identifier
 *   Identifier
 */
define(
[
	'emberjs',
	'Library/underscore'
], function(
	Ember,
	_
	) {

	function identifierFormatHelper(id) {
		id = id.replace(/\./g, "-");
		if (id.length <= 1) {
			id = id.toLowerCase();
		} else {
			id = id.substring(0, 1).toLowerCase() + id.substring(1);
		}
		return id;
	}

	function translate(id, fallback, packageKey, source) {
		var translatedValue, translationParts, identifier;
		packageKey = packageKey || 'TYPO3.Neos';
		source = source || 'Main';

		if (_.isUndefined(id) || id === '' || _.isObject(id)) {
			/**
			 * Only default content given
			 */
			return fallback;
		}

		if (!id) {
			/**
			 * No identifier and default value given
			 */
			return Ember.I18n.translate('TYPO3.Neos.translate.requiredProperty') + 'id';
		}
		/**
		 * i18n path given
		 *
		 * The identifier.replace() calls, converts the dots used in the fluid templates to match the generated
		 * json file where dashes are used
		 *
		 * Current implementation allow only the given:
		 * i18n:Catalog:Package.Key:Identifier
		 * i18n:Package.Key:Identifier
		 * i18n:Catalog:Identifier
		 * i18n:Identifier
		 * Identifier
		 *
		 * Defaults are:
		 » packageKey = 'TYPO3.Neos'
		 » source = 'Main'
		 */
		id = id.trim().replace('i18n:', '');
		translationParts = id.split(':');
		if (translationParts.length === 3) {
			// i18n:Catalog:Package.Key:Identifier
			identifier = identifierFormatHelper(translationParts[2]);
			id = translationParts[1] + '.' + translationParts[0] + '.' + identifier;
		} else if (translationParts.length === 2) {
			if (translationParts[0].indexOf('.') === -1) {
				// i18n:Catalog:Identifier
				identifier = identifierFormatHelper(translationParts[1]);
				id = packageKey + '.' + translationParts[0] + '.' + identifier;
			} else {
				// i18n:Package.Key:Identifier
				identifier = identifierFormatHelper(translationParts[1]);
				id = translationParts[0] + '.' + source + '.' + identifier;
			}
		} else {
			// i18n:Identifier or Identifier
			identifier = identifierFormatHelper(translationParts[0]);
			id = packageKey + '.' + source + '.' + identifier;
		}

		translatedValue = Ember.I18n.translate(id);
		if (translatedValue.indexOf('Missing translation:') !== -1) {
			return fallback;
		}

		return translatedValue;
	}

	Ember.Handlebars.registerBoundHelper('translate', function (options) {

		var attrs;
		attrs = options.hash;
		return translate(attrs.id, attrs.fallback, attrs.package, attrs.source);
	});

	return Ember.Object.extend({
		translate: translate
	}).create();
});
