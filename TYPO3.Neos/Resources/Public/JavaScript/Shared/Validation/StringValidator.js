/**
 * Validator for strings.
 */
define(
	[
		'./AbstractValidator'
	],
	function(AbstractValidator) {
		return AbstractValidator.extend({
			/**
			 * Checks if the given value is a string.
			 *
			 * @param {mixed} value The value that should be validated
			 * @return {void}
			 */
			isValid: function(value) {
				if (typeof(value) !== 'string') {
					this.addError('A valid string is expected.');
				}
			}
		});
	}
);