/**
 * Validator for alphanumeric strings.
 */
define(
	[
		'./RegularExpressionValidator'
	],
	function(RegularExpressionValidator) {
		return RegularExpressionValidator.extend({
			/**
			 * The given value is valid if it is an alphanumeric string, which is the defined by the POSIX class "alnum".
			 *
			 * @var {object}
			 */
			supportedOptions: {
				'regularExpression': ['/^[\\p{L}\\p{Nd}]*$/u', 'The regular expression to use for validation, used as given', 'string']
			},

			/**
			 * Adds a new validation error to the result
			 *
			 * @param {string} message The error message
			 * @return {void}
			 */
			addError: function(message) {
				this._super('Only regular characters (a to z, umlauts, ...) and numbers are allowed.');
			}
		});
	}
);