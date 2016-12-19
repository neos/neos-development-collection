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
 *   Vendor.Package:SourceFile:label-identifier
 *   Vendor.Package:label-identifier
 *   label-identifier
 */
define(
[
	'emberjs',
	'Library/underscore'
], function(Ember, _) {

	/**
	 * @singleton
	 */
	return Ember.Object.extend({
		defaultPackage: 'Neos.Neos',
		defaultSource: 'Main',
		initialized: false,

		/**
		 * @return {void}
		 */
		init: function () {
			var self = this,
				translateHelperClosure;

			translateHelperClosure = function (options) {
				var attrs = options.hash;
				parameters = Object.keys(attrs).reduce(function(parameters, attr) {
					if (!attr.match(/(id|fallback|package|source|boundOptions|(.+)Binding)$/)) {
						parameters.push(attr);
					}
					return parameters;
				}, []).reduce(function(parameters, attr) {
					parameters[attr] = attrs[attr];
					return parameters;
				}, {});
				return self.translate(attrs.id, attrs.fallback, attrs.package, attrs.source, parameters);
			};

			Ember.Handlebars.registerHelper('translate', translateHelperClosure);
			Ember.Handlebars.registerBoundHelper('boundTranslate', translateHelperClosure);
		},

		/**
		 * Returns a translated label.
		 *
		 * Replaces all placeholders with corresponding values if they exist in the
		 * translated label.
		 *
		 * @param {string} id Id to use for finding translation (trans-unit id in XLIFF)
		 * @param {string} fallback Fallback value in case the no label translation was found.
		 * @param {string} packageKey Target package key. If not set, the current package key will be used
		 * @param {string} source Name of file with translations
		 * @param {object} parameters Numerically indexed array of values to be inserted into placeholders
		 * @param {string} context
		 * @returns {string}
		 */
		translate: function(id, fallback, packageKey, source, parameters, context) {
			// Prevent caching missing keys when used too early
			if (this.get('initialized') === false) {
				console.error('Labels not initialized when trying to translate "' + id + '"');
				return;
			}

			var translatedValue, translationParts;
			fallback = fallback || id;
			packageKey = packageKey || this.defaultPackage;
			source = source || this.defaultSource;

			if (_.isUndefined(id) || id === '' || _.isObject(id)) {
				return fallback;
			}

			translationParts = this._splitIdentifier(id, packageKey, source);
			Ember.Logger.mute = true; // Mute logging in case the specific label translation is not found
			translatedValue = Ember.I18n.translate(translationParts.getJoinedIdentifier(), context);
			Ember.Logger.mute = false;
			if (translatedValue.indexOf('Missing translation:') !== -1) {
				return fallback;
			}

			if (!_.isEmpty(parameters)) {
				translatedValue = this._resolvePlaceholders(translatedValue, parameters);
			}

			return translatedValue;
		},

		/**
		 * @param {string} id translation id with possible package and source parts.
		 * @param {string} fallbackPackage
		 * @param {string} fallbackSource
		 * @private
		 */
		_splitIdentifier: function(id, fallbackPackage, fallbackSource) {
			id = id.trim();
			var translationParts = {
				id: id,
				packageKey: fallbackPackage,
				source: fallbackSource,

				getJoinedIdentifier: function() {
					return this.packageKey + '.' + this.source + '.' + this.id;
				}
			};

			if (id && id.split) {
				var idParts = id.split(':', 3);
				switch (idParts.length) {
					case 2:
						translationParts.packageKey = idParts[0];
						translationParts.id = idParts[1];
						break;
					case 3:
						translationParts.packageKey = idParts[0];
						translationParts.source = idParts[1];
						translationParts.id = idParts[2];
						break;
				}
			}

			translationParts.id = translationParts.id.replace(/\./g, '_');
			if (translationParts.id.length <= 1) {
				translationParts.id = translationParts.id.toLowerCase();
			} else {
				translationParts.id = translationParts.id.substring(0, 1).toLowerCase() + translationParts.id.substring(1);
			}

			translationParts.packageKey = translationParts.packageKey.replace(/\./g, '_');
			translationParts.source = translationParts.source.replace(/\./g, '_');
			return translationParts;
		},

		/**
		 * @param {string} textWithPlaceholders
		 * @param {object} parameters
		 * @returns {string}
		 * @private
		 */
		_resolvePlaceholders: function(textWithPlaceholders, parameters) {
			var startOfPlaceholder;
			while ((startOfPlaceholder = textWithPlaceholders.indexOf('{')) !== -1) {
				var endOfPlaceholder = textWithPlaceholders.indexOf('}');
				var startOfNextPlaceholder = textWithPlaceholders.indexOf('{', startOfPlaceholder + 1);

				if (endOfPlaceholder === -1 || (startOfPlaceholder + 1) >= endOfPlaceholder || (startOfNextPlaceholder !== -1 && startOfNextPlaceholder < endOfPlaceholder)) {
					// There is no closing bracket, or it is placed before the opening bracket, or there is nothing between brackets
					window.console.error('Text provided contains incorrectly formatted placeholders. Please make sure you conform the placeholder\'s syntax.');
					break;
				}

				var contentBetweenBrackets = textWithPlaceholders.substr(startOfPlaceholder + 1, endOfPlaceholder - startOfPlaceholder - 1);
				var placeholderElements = contentBetweenBrackets.replace(' ', '').split(',');

				var valueIndex = placeholderElements[0];
				if (typeof parameters[valueIndex] === undefined) {
					window.console.error('Placeholder "' + valueIndex + '" was not provided, make sure you provide values for every placeholder.');
					break;
				}

				var formattedPlaceholder;
				if (typeof placeholderElements[1] !== 'undefined') {
					window.console.error('Placeholder formatter not supported.');
					break;
				} else {
					// No formatter defined, just string-cast the value
					formattedPlaceholder = parameters[valueIndex];
				}

				textWithPlaceholders = textWithPlaceholders.replace('{' + contentBetweenBrackets + '}', formattedPlaceholder);
			}

			return textWithPlaceholders;
		}
	}).create();
});
