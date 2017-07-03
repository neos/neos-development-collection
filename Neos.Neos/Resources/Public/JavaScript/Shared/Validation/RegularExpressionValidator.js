/**
 * Validator based on regular expressions.
 */
define(
	[
		'./AbstractValidator',
		'Library/xregexp',
		'Shared/I18n'
	],
	function(AbstractValidator, XRegExp, I18n) {
		return AbstractValidator.extend({
			/**
			 * @var {object}
			 */
			supportedOptions: {
				'regularExpression': ['', 'The regular expression to use for validation, used as given', 'string', true]
			},

			/**
			 * Checks if the given value matches the specified regular expression.
			 *
			 * @param {mixed} value The value that should be validated
			 * @return {void}
			 */
			isValid: function(value) {
				var pattern = this.get('options.regularExpression'),
					modifier = '';
				if (pattern.charAt(0) === '/') {
					pattern = pattern.slice(1);
				}
				if (pattern.charAt(pattern.length - 2) === '/') {
					modifier = pattern.slice(-1);
					// Only allow modifiers supported by XRegExp & PHP
					if (['i', 'm', 's', 'x'].indexOf(modifier) === -1) {
						modifier = '';
					}
					pattern = pattern.substring(0, pattern.length - 2);
				} else if (pattern.slice(-1) === '/') {
					pattern = pattern.substring(0, pattern.length - 1);
				}
				if (typeof value !== 'string' || XRegExp(pattern, modifier).test(value) === false) {
					this.addError(I18n.translate('content.inspector.validators.regularExpressionValidator.patternDoesNotMatch', null, null, null, {pattern: pattern}));
				}
			}
		});
	}
);