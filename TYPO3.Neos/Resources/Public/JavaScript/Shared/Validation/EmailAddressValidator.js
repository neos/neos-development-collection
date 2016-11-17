/**
 * Validator for email addresses
 */
define(
	[
		'./RegularExpressionValidator',
		'Shared/I18n'
	],
	function(RegularExpressionValidator, I18n) {
		return RegularExpressionValidator.extend({
			/**
			 * Checks if the given value is a valid email address.
			 * Source: http://fightingforalostcause.net/misc/2006/compare-email-regex.php
			 *
			 * @var {object}
			 */
			supportedOptions: {
				'regularExpression': ['/^[-a-z0-9~!$%^&*_=+}{\'?]+(\.[-a-z0-9~!$%^&*_=+}{\'?]+)*@([a-z0-9_][-a-z0-9_]*(\.[-a-z0-9_]+)*\.(aero|arpa|biz|com|coop|edu|gov|info|int|mil|museum|name|net|org|pro|travel|mobi|[a-z][a-z])|([0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}))(:[0-9]{1,5})?$/i', 'The regular expression to use for validation, used as given', 'string']
			},

			/**
			 * Adds a new validation error to the result
			 *
			 * @param {string} message The error message
			 * @return {void}
			 */
			addError: function(message) {
				this._super(I18n.translate('content.inspector.validators.emailAddressValidator.invalidEmail'));
			}
		});
	}
);