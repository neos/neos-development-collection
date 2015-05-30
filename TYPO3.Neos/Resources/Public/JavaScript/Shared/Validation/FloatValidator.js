/**
 * Validator for floats.
 */
define(
	[
		'./AbstractValidator',
		'Library/xregexp'
	],
	function(AbstractValidator, XRegExp) {
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
					this.addError('A valid float number is expected.');
				}
			}
		});
	}
);