/**
 * Abstract validator
 */
define(
	[
		'emberjs',
		'Library/underscore'
	],
	function (Ember, _) {
		return Ember.Object.extend({
			/**
			 * Specifies whether this validator accepts empty values.
			 *
			 * If this is true, the validators isValid() method is not called in case of an empty value
			 * Note: A value is considered empty if it is null or an empty string!
			 * By default all validators except for NotEmpty accept empty values
			 *
			 * @var {boolean}
			 */
			acceptsEmptyValues: true,

			/**
			 * This contains the supported options, their default values, types and descriptions.
			 *
			 * @var {object}
			 */
			supportedOptions: {},

			/**
			 * @var {object}
			 */
			options: {},

			/**
			 * contains an array of strings with the error messages, if any errors occured.
			 *
			 * @var array
			 */
			result: [],

			/**
			 * Constructs the validator and sets validation options
			 *
			 * @return void
			 */
			init: function() {
				var options = this.get('options'),
					supportedOptions = this.get('supportedOptions'),
					defaultValues = {};
				_.each(options, function(option, key) {
					if (typeof supportedOptions[key] === 'undefined') {
						throw 'Unsupported validation option for found: ' + key;
					}
				});
				_.each(supportedOptions, function(supportedOption, key) {
					if (typeof supportedOption[3] !== 'undefined' && typeof options[key] === 'undefined') {
						throw 'Required validation option not set: ' + key;
					}
					defaultValues[key] = supportedOption[0];
				});

				this.set('options', _.extend(defaultValues, options));
			},

			/**
			 * Checks if the given value is valid according to the validator, and returns
			 * the error messages array which occurred.
			 *
			 * @param {mixed} value The value that should be validated
			 * @return {array}
			 */
			validate: function(value) {
				this.set('result', []);
				if (this.get('acceptsEmptyValues') === false || this.isEmpty(value) === false) {
					this.isValid(value);
				}
				return this.get('result');
			},

			/**
			 * Check if value is valid. If it is not valid, needs to add an error
			 * to the result.
			 *
			 * @param {mixed} value The value that should be validated
			 * @return {void}
			 */
			isValid: Ember.required(),

			/**
			 * Adds a new validation error to the result
			 *
			 * @param {string} message The error message
			 * @return {void}
			 */
			addError: function(message) {
				this.get('result').push(message);
			},

			/**
			 * Adds a new validation error to the result
			 *
			 * @param {mixed} value
			 * @return {boolean}
			 */
			isEmpty: function(value) {
				if (typeof value === 'undefined') {
					return true;
				} else if (value === null) {
					return true;
				} else if (value === false) {
					return true;
				} else if (Object.prototype.toString.call(value) === '[object Array]' && value.length === 0) {
					return true;
				} else if (value === '') {
					return true;
				}
				return false;
			}
		});
	}
);