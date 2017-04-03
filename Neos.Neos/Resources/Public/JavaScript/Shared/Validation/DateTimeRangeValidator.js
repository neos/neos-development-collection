/**
 * Validator for checking Date and Time boundaries
 */
define(
	[
		'./AbstractValidator',
		'Library/iso8601-js-period',
		'Shared/I18n'
	],
	function(AbstractValidator, iso8601JsPeriod, I18n) {
		return AbstractValidator.extend({
			/**
			 * @var {object}
			 */
			supportedOptions: {
				'latestDate': [null, 'The latest date to accept', 'string'],
				'earliestDate': [null, 'The earliest date to accept', 'string']
			},

			/**
			 * The given value is valid if it is an array or object that contains the specified amount of elements.
			 *
			 * @param {mixed} value The value that should be validated
			 * @return {void}
			 */
			isValid: function(value) {
				if (/^(\d{4})-(\d{2})-(\d{2})T(\d{2})\:(\d{2})\:(\d{2})[+-](\d{2})\:(\d{2})$/.test(value) === false || /Invalid|NaN/.test(new Date(value).toString())) {
					this.addError(I18n.translate('content.inspector.validators.dateTimeRangeValidator.invalidDate'));
					return;
				}

				var date = new Date(value),
					latestDate = this.get('options.latestDate') !== null ? this.parseReferenceDate(this.get('options.latestDate')) : null,
					earliestDate = this.get('options.earliestDate') !== null ? this.parseReferenceDate(this.get('options.earliestDate')) : null;

				var formatEarliestDate = this.formatDate(earliestDate);
				var formatLatestDate = this.formatDate(latestDate);

				if (earliestDate !== null && latestDate !== null) {
					if (date < earliestDate || date > latestDate) {
						this.addError(I18n.translate('content.inspector.validators.dateTimeRangeValidator.mustBeBetween', null, null, null, {formatEarliestDate: formatEarliestDate, formatLatestDate: formatLatestDate}));
					}
				} else if (earliestDate !== null) {
					if (date < earliestDate) {
						this.addError(I18n.translate('content.inspector.validators.dateTimeRangeValidator.mustBeAfter', null, null, null, {formatEarliestDate: formatEarliestDate}));
					}
				} else if (latestDate !== null) {
					if (date > latestDate) {
						this.addError(I18n.translate('content.inspector.validators.dateTimeRangeValidator.mustBeBefore', null, null, null, {formatEarliestDate: formatLatestDate}));
					}
				}
			},

			/**
			 * Calculates a Date object from a given Time interval
			 *
			 * @param {string} referenceDateString being one of <time>, <start>/<offset> or <offset>/<end>
			 * @return {Date}
			 */
			parseReferenceDate: function(referenceDateString) {
				var referenceDateParts = referenceDateString.split('/');

				if (referenceDateParts.length === 1) {
						// assume a valid date string
					return new Date(referenceDateParts[0]);
				}

				var interval,
					date;
					// check if the period (the interval) is the first or second item:
				if (referenceDateParts[0].charAt(0) === 'P') {
					interval = iso8601JsPeriod.Period.parseToTotalSeconds(referenceDateParts[0]);
					date = new Date(referenceDateParts[1] === 'now' ? new Date() : new Date(referenceDateParts[1]));
					return new Date(date.getTime() - interval * 1000);
				} else if (referenceDateParts[1].charAt(0) === 'P') {
					interval = iso8601JsPeriod.Period.parseToTotalSeconds(referenceDateParts[1]);
					date = new Date(referenceDateParts[0] === 'now' ? new Date() : new Date(referenceDateParts[0]));
					return new Date(date.getTime() + interval * 1000);
				} else {
					throw 'There is no valid interval declaration in "' + referenceDateString + '". Exactly one part must begin with "P".';
				}
			},

			/**
			 * Formats a Date object
			 *
			 * @param {Date} date
			 * @return {string}
			 */
			formatDate: function (date) {
				function pad(n) {
					var s = n.toString();
					return s.length < 2 ? '0'+s : s;
				}
				return [
					date.getFullYear(),
					pad(date.getMonth() + 1),
					pad(date.getDate())
				].join('-');
			}
		});
	}
);