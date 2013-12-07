/**
 * Validator for Universally Unique Identifiers.
 */
define(
	[
		'./RegularExpressionValidator'
	],
	function(RegularExpressionValidator) {
		return RegularExpressionValidator.extend({
			/**
			 * Checks if the given value is a syntactically valid UUID.
			 *
			 * @var {object}
			 */
			supportedOptions: {
				'regularExpression': ['/([a-f0-9]){8}-([a-f0-9]){4}-([a-f0-9]){4}-([a-f0-9]){4}-([a-f0-9]){12}/', 'The regular expression to use for validation, used as given', 'string', true]
			},

			/**
			 * Adds a new validation error to the result
			 *
			 * @param {string} message The error message
			 * @return {void}
			 */
			addError: function(message) {
				this._super('The given subject is not a valid UUID.');
			}
		});
	}
);