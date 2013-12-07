/**
 * Validator for checking Date and Time boundaries
 */
define(
	[
		'./AbstractValidator',
		'Library/iso8601-js-period'
	],
	function(AbstractValidator, iso8601JsPeriod) {
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
				if (/^\d{4}[\/\-]\d{1,2}[\/\-]\d{1,2}$/.test(value) === false || /Invalid|NaN/.test(new Date(value).toString())) {
					this.addError('The given value was not a valid date.');
					return;
				}

				var date = new Date(value),
					latestDate = this.get('options.latestDate') !== null ? this.parseReferenceDate(this.get('options.latestDate')) : null,
					earliestDate = this.get('options.earliestDate') !== null ? this.parseReferenceDate(this.get('options.earliestDate')) : null;

				if (earliestDate !== null && latestDate !== null) {
					if (date < earliestDate || date > latestDate) {
						this.addError('The given date must be between ' + this.formatDate(earliestDate) + ' and ' + this.formatDate(latestDate));
					}
				} else if (earliestDate !== null) {
					if (date < earliestDate) {
						this.addError('The given date must be after ' + this.formatDate(earliestDate));
					}
				} else if (latestDate !== null) {
					if (date > latestDate) {
						this.addError('The given date must be before ' + this.formatDate(latestDate));
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