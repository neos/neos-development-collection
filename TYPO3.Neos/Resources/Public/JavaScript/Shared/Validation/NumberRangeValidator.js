/**
 * Validator for number range
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
				'minimum': [0, 'The minimum value to accept', 'integer'],
				'maximum': [Number.MAX_VALUE, 'The maximum value to accept', 'integer']
			},

			/**
			 * The given value is valid if it is a number in the specified range.
			 *
			 * @param {mixed} value The value that should be validated
			 * @return {void}
			 */
			isValid: function(value) {
				if (!((typeof value === 'number') || (typeof value  === 'string') && !isNaN(value))) {
					this.addError('A valid number is expected.');
					return;
				}

				var minimum = parseInt(this.get('options.minimum'), 10),
					maximum = parseInt(this.get('options.maximum'), 10);

				if (minimum > maximum) {
					var x = minimum;
					minimum = maximum;
					maximum = x;
				}
				if (value < minimum || value > maximum) {
					this.addError('Please enter a valid number between ' + minimum + ' and ' + maximum + '.');
				}
			}
		});
	}
);