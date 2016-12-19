/**
 * Validator for strings.
 */
define(
	[
		'./AbstractValidator',
		'Shared/I18n'
	],
	function(AbstractValidator, I18n) {
		return AbstractValidator.extend({
			/**
			 * Checks if the given value is a string.
			 *
			 * @param {mixed} value The value that should be validated
			 * @return {void}
			 */
			isValid: function(value) {
				if (typeof(value) !== 'string') {
					this.addError(I18n.translate('content.inspector.validators.stringValidator.stringIsExpected'));
				}
			}
		});
	}
);