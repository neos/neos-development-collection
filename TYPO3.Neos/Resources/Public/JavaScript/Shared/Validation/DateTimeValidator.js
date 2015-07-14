/**
 * Validator for checking Date
 */
define(
	[
		'./AbstractValidator',
		'Shared/I18n'
	],
	function(AbstractValidator, I18n) {
		return AbstractValidator.extend({
			/**
			 * Checks if the given value is a valid date.
			 *
			 * @param {mixed} value The value that should be validated
			 * @return {void}
			 */
			isValid: function(value) {
				if (/^(\d{4})-(\d{2})-(\d{2})T(\d{2})\:(\d{2})\:(\d{2})[+-](\d{2})\:(\d{2})$/.test(value) === false || /Invalid|NaN/.test(new Date(value).toString())) {
					this.addError(I18n.translate('content.inspector.validators.dateTimeRangeValidator.invalidDate'));
				}
			}
		});
	}
);