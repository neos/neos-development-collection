/**
 * Validator for integers.
 */
define(
	[
		'./AbstractValidator'
	],
	function(AbstractValidator) {
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
				}
			}
		});
	}
);