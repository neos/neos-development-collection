/**
 * A validator for labels.
 *
 * Labels usually allow all kinds of letters, numbers, punctuation marks and
 * the space character. What you don't want in labels though are tabs, new
 * line characters or HTML tags. This validator is for such uses.
 */
define(
	[
		'./RegularExpressionValidator'
	],
	function(RegularExpressionValidator) {
		return RegularExpressionValidator.extend({
			/**
			 * The given value is valid if it matches the regular expression.
			 *
			 * @var {object}
			 */
			supportedOptions: {
				'regularExpression': ['/^[\\p{L}\\p{Sc} ,.:;?!%§&"\'\/+\-_=\(\)#0-9]*$/u', 'The regular expression to use for validation, used as given', 'string']
			},

			/**
			 * Adds a new validation error to the result
			 *
			 * @param {string} message The error message
			 * @return {void}
			 */
			addError: function(message) {
				this._super('Only letters, numbers, spaces and certain punctuation marks are expected.');
			}
		});
	}
);