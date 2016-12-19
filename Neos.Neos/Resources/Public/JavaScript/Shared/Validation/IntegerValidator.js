/**
 * Validator for integers.
 */
define(
	[
		'./AbstractValidator',
		'Shared/I18n'
	],
	function(AbstractValidator, I18n) {
		return AbstractValidator.extend({
			/**
			 * Checks if the given value is a valid integer.
			 *
			 * @param {mixed} value The value that should be validated
			 * @return {void}
			 */
			isValid: function(value) {
				if (parseFloat(value) !== parseInt(value) || isNaN(value)) {
					this.addError('A valid integer number is expected.');
					this.addError(I18n.translate('content.inspector.validators.integerValidator.aValidIntegerNumberIsExpected'));
				}
			}
		});
	}
);