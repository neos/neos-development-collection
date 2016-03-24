/**
 * Validator for string length
 */
define(
	[
		'./AbstractValidator',
		'Shared/I18n'
	],
	function(AbstractValidator, I18n) {
		return AbstractValidator.extend({
			/**
			 * @var {object}
			 */
			supportedOptions: {
				'minimum': [0, 'Minimum length for a valid string', 'integer'],
				'maximum': [10000, 'Maximum length for a valid string', 'integer']
			},

			/**
			 * Checks if the given value is a valid string (or can be cast to a string
			 * if an object is given) and its length is between minimum and maximum
			 * specified in the validation options.
			 *
			 * @param {mixed} value The value that should be validated
			 * @return {void}
			 */
			isValid: function(value) {
				var minimum = parseInt(this.get('options.minimum'), 10),
					maximum = parseInt(this.get('options.maximum'), 10);
				if (maximum < minimum) {
					throw 'The maximum is less than the minimum.';
				}

				var stringLength = value.toString().length;
				if (stringLength < minimum || stringLength > maximum) {
					if (minimum > 0 && maximum < 10000) {
						this.addError(I18n.translate('content.inspector.validators.stringLength.outOfBounds', null, null, null, {minimum: minimum, maximum: maximum}));
					} else if (minimum > 0) {
						this.addError(I18n.translate('content.inspector.validators.stringLength.smallerThanMinimum', null, null, null, {minimum: minimum}));
					} else {
						this.addError(I18n.translate('content.inspector.validators.stringLength.greaterThanMaximum', null, null, null, {maximum: maximum}));
					}
				}
			}
		});
	}
);
