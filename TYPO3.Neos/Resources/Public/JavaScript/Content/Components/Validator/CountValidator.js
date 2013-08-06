/**
 * Validator for countable things
 */
define(
	[
		'./AbstractValidator',
		'Library/underscore'
	],
	function(AbstractValidator, _) {
		return AbstractValidator.extend({
			/**
			 * @var {object}
			 */
			supportedOptions: {
				'minimum': [0, 'The minimum count to accept', 'integer'],
				'maximum': [Number.MAX_VALUE, 'The maximum count to accept', 'integer']
			},

			/**
			 * The given value is valid if it is an array or object that contains the specified amount of elements.
			 *
			 * @param {mixed} value The value that should be validated
			 * @return {void}
			 */
			isValid: function(value) {
				if (typeof value !== 'array' || typeof value !== 'object') {
					this.addError('The given subject was not countable.');
					return;
				}

				var minimum = parseInt(this.get('options.minimum'), 10),
					maximum = parseInt(this.get('options.maximum'), 10),
					length = _.keys(value).length;
				if (length < minimum || length > maximum) {
					this.addError('The count must be between ' + minimum + ' and ' + maximum + '.');
				}
			}
		});
	}
);