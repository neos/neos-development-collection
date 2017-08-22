/**
 * Validator for countable things
 */
define(
	[
		'./AbstractValidator',
		'Library/underscore',
		'Shared/I18n',
		'Shared/Utility'
	],
	function(AbstractValidator, _, I18n, Utility) {
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
			 * If the value is a valid JSON string then the parsed object is checked.
			 *
			 * @param {mixed} value The value that should be validated
			 * @return {void}
			 */
			isValid: function(value) {
				if (Utility.isValidJsonString(value)) {
					value = JSON.parse(value);
				}
				if (typeof value !== 'array' || typeof value !== 'object') {
					this.addError(I18n.translate('content.inspector.validators.countValidator.notCountable'));
					return;
				}

				var minimum = parseInt(this.get('options.minimum'), 10),
					maximum = parseInt(this.get('options.maximum'), 10),
					length = _.keys(value).length;
				if (length < minimum || length > maximum) {
					this.addError(I18n.translate('content.inspector.validators.countValidator.countBetween', null, null, null, {minimum: minimum, maximum: maximum}));
				}
			}
		});
	}
);