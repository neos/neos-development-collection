/**
 * Validator for not empty values.
 */
define(
	[
		'./AbstractValidator',
		'Library/underscore'
	],
	function(AbstractValidator, _) {
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
					this.addError('This property is required.');
				}
			}
		});
	}
);