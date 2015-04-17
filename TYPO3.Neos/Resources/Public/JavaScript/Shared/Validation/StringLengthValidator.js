/**
 * Validator for string length
 */
define(
	[
		'./AbstractValidator'
	],
	function(AbstractValidator) {
		return AbstractValidator.extend({
			/**
			 * @var {object}
			 */
			supportedOptions: {
				'minimum': [0, 'Minimum length for a valid string', 'integer'],
				'maximum': [Number.MAX_VALUE, 'Maximum length for a valid string', 'integer']
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
					if (minimum > 0 && maximum < Number.MAX_VALUE) {
						this.addError('The length of this text must be between ' + minimum + ' and ' + maximum + ' characters.');
					} else if (minimum > 0) {
						this.addError('This field must contain at least ' + minimum + ' characters.');
					} else {
						this.addError('This text may not exceed ' + maximum + ' characters.');
					}
				}
			}
		});
	}
);