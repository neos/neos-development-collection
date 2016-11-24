/**
 * Validator for "plain" text.
 */
define(
	[
		'./AbstractValidator',
		'Shared/I18n'
	],
	function(AbstractValidator, I18n) {
		return AbstractValidator.extend({
			/**
			 * Checks if the given value is a valid text (contains no XML tags).
			 *
			 * @param {mixed} value The value that should be validated
			 * @return {void}
			 */
			isValid: function(value) {
				if (value !== value.replace(/<\/?([a-z][a-z0-9]*)\b[^>]*>/gi, '')) {
					this.addError(I18n.translate('content.inspector.validators.textValidator.validTextWithoutAnyXMLtagsIsExpected'));
				}
			}
		});
	}
);