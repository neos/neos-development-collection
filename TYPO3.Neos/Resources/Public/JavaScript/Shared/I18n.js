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

		defaultPackage: 'TYPO3.Neos',
		defaultSource: 'Main',

		init: function () {
			var self = this,
				translateHelperClosure;

			translateHelperClosure = function (options) {
				var attrs;
				attrs = options.hash;
				return self.translate(attrs.id, attrs.fallback, attrs.package, attrs.source);
			};

			Ember.Handlebars.registerHelper('translate', translateHelperClosure);
			Ember.Handlebars.registerBoundHelper('boundTranslate', translateHelperClosure);
		},

		translate: function(id, fallback, packageKey, source, context) {
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
				idParts = id.split(':', 3);
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

			translationParts.id = translationParts.id.replace(/\./g, "_");
			if (translationParts.id.length <= 1) {
				translationParts.id = translationParts.id.toLowerCase();
			} else {
				translationParts.id = translationParts.id.substring(0, 1).toLowerCase() + translationParts.id.substring(1);
			}

			translationParts.packageKey = translationParts.packageKey.replace(/\./g, "_");
			translationParts.source = translationParts.source.replace(/\./g, "_");
			return translationParts;
		}
	}).create();
});