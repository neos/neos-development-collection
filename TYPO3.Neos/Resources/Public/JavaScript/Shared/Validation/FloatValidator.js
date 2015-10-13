/**
 * Validator for floats.
 */
define(
	[
		'./AbstractValidator',
		'Library/xregexp',
		'Shared/I18n'
	],
	function(AbstractValidator, XRegExp, I18n) {
		return AbstractValidator.extend({
			/**
			 * The given value is valid if it is of type float or a string matching the regular expression [0-9.e+-]
			 *
			 * @param {mixed} value The value that should be validated
			 * @return {void}
			 */
			isValid: function(value) {
				if (+value === value && (!isFinite(value) || !!(value % 1))) {
					return;
				}

				if (typeof value !== 'string' || value.indexOf('.') === false || XRegExp('^[0-9.e+-]+$').test(value) === false) {
					this.addError(I18n.translate('content.inspector.validators.floatValidator.validFloatExpected'));
				}
			}
		});
	}
);