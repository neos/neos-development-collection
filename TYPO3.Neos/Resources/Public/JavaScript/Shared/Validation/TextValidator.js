/**
 * Validator for "plain" text.
 */
define(
	[
		'./AbstractValidator'
	],
	function(AbstractValidator) {
		return AbstractValidator.extend({
			/**
			 * Checks if the given value is a valid text (contains no XML tags).
			 *
			 * @param {mixed} value The value that should be validated
			 * @return {void}
			 */
			isValid: function(value) {
				if (value !== value.replace(/<\/?([a-z][a-z0-9]*)\b[^>]*>/gi, '')) {
					this.addError('Valid text without any XML tags is expected.');
				}
			}
		});
	}
);