/**
 * Validator for not empty values.
 */
define(
	[
		'./AbstractValidator',
		'Library/underscore',
		'Shared/I18n'
	],
	function(AbstractValidator, _, I18n) {
		return AbstractValidator.extend({
			/**
			 * Specifies whether this validator accepts empty values.
			 *
			 * @var {boolean}
			 */
			acceptsEmptyValues: false,

			/**
			 * Checks if the given value is not empty (NULL, empty string, empty array.
			 *
			 * @param {mixed} value The value that should be validated
			 * @return {void}
			 */
			isValid: function(value) {
				if (value === null || value === '' || (_.isArray(value) && _.isEmpty(value))) {
					this.addError(I18n.translate('content.inspector.validators.notEmptyValidator.isEmpty'));
				}
			}
		});
	}
);