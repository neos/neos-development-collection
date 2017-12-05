define(
[
	'emberjs',
	'Library/jquery-with-dependencies',
	'Shared/I18n',
	'text!./DateTimeEditor.html'
],
function (Ember, $, I18n, template) {
	return Ember.View.extend({
		attributeBindings: ['name', 'value'],
		value: '',
		hrValue: '',
		_timeOnly: false,
		isOpen: false,
		template: Ember.Handlebars.compile(template),

		/**
		 * The date format, a combination of y, Y, F, m, M, n, t, d, D, j, l, N, S, w, a, A, g, G, h, H, i, s.
		 *
		 * // year
		 * y: A two digit representation of a year - Examples: 99 or 03
		 * Y: A full numeric representation of a year, 4 digits - Examples: 1999 or 2003
		 * // month
		 * F: A full textual representation of a month, such as January or March - January through December
		 * m: Numeric representation of a month, with leading zeros - 01 through 12
		 * M: A short textual representation of a month, three letters - Jan through Dec
		 * n: Numeric representation of a month, without leading zeros - 1 through 12
		 * t: Number of days in the given month - 28 through 31
		 * // day
		 * d: Day of the month, 2 digits with leading zeros - 01 to 31
		 * D: A textual representation of a day, three letters - Mon through Sun
		 * j: Day of the month without leading zeros - 1 to 31
		 * l: A full textual representation of the day of the week - Sunday through Saturday
		 * N: ISO-8601 numeric representation of the day of the week - 1 (for Monday) through 7 (for Sunday)
		 * S: English ordinal suffix for the day of the month, 2 characters - st, nd, rd or th.
		 * w: Numeric representation of the day of the week - 0 (for Sunday) through 6 (for Saturday)
		 * // hour
		 * a: Lowercase Ante meridiem and Post meridiem - am or pm
		 * A: Uppercase Ante meridiem and Post meridiem - AM or PM
		 * g: hour without leading zeros - 12-hour format - 1 through 12
		 * G: hour without leading zeros - 24-hour format - 0 through 23
		 * h: 12-hour format of an hour with leading zeros - 01 through 12
		 * H: 24-hour format of an hour with leading zeros - 00 through 23
		 * // minute
		 * i: minutes, 2 digits with leading zeros - 00 to 59
		 * // second
		 * s: seconds, 2 digits with leading zeros - 00 through 59
		 */
		format: 'd-m-Y',

		/**
		 * The increment used to build the hour view. A preset is created for each minuteStep minutes.
		 */
		minuteStep: 5,

		/**
		 * The placeholder shown when no date is selected
		 */
		placeholder: '',
		_placeholder: function() {
			return I18n.translate(this.get('placeholder'), 'No date set');
		}.property('placeholder'),

		/**
		 * The DOM element the datetimepicker is bound to
		 */
		$datetimepicker: null,

		/**
		 * The readonly field showing the selected value
		 */
		inputField: Ember.TextField.extend({
			attributeBindings: ['readonly'],
			readonly: true,
			action: 'open',
			focusIn: function() {
				this.get('parentView').open();
			}
		}),

		/**
		 * @param {string} date
		 * @param {string} format
		 * @param {boolean} includeTimezoneOffset
		 * @return {string}
		 */
		formatDate: function(date, format, includeTimezoneOffset) {
			function pad(n) {
				return n < 10 ? '0' + n : n;
			}
			var datetime = $.fn.datetimepicker.DPGlobal.formatDate(
					date,
					$.fn.datetimepicker.DPGlobal.parseFormat(format, 'php'),
					'en',
					'php'
				);
			if (includeTimezoneOffset !== true) {
				return datetime;
			}
			var dateTimeForOffset = $.fn.datetimepicker.DPGlobal.formatDate(
				date,
				$.fn.datetimepicker.DPGlobal.parseFormat('m d, Y H:i:s', 'php'),
				'en',
				'php'
			);
			var offset = new Date(dateTimeForOffset).getTimezoneOffset();
			var timezone = (offset < 0 ? '+' : '-') + pad(parseInt(Math.abs(offset / 60)), 2) + ':' + pad(Math.abs(offset % 60), 2);
			return datetime + timezone;
		},

		/**
		 * @param {string} date
		 * @param {string} format
		 * @return {string}
		 */
		parseDate: function(date, format) {
			return $.fn.datetimepicker.DPGlobal.parseDate(
				date,
				$.fn.datetimepicker.DPGlobal.parseFormat(format, 'php'),
				'en',
				'php'
			);
		},

		/**
		 * @return {void}
		 */
		onValueChanged: function() {
			if (this.get('value') && !/Invalid|NaN/.test(new Date(this.get('value')).toString())) {
				var d = new Date(this.get('value'));
				this.set('hrValue', this.formatDate(new Date(d.getTime() - (d.getTimezoneOffset() * 60000)), this.get('format')));
			} else {
				this.set('hrValue', '');
			}
		}.observes('value'),

		/**
		 * @return {void}
		 */
		didInsertElement: function() {
			var that = this,
				$datetimepicker = this.$('.neos-editor-datetimepicker'),
				viewSettings = this.calculateViewSettings(),
				todayBtn = 'linked';

			this.set('$datetimepicker', $datetimepicker);

			if (viewSettings.maxView < 2) {
				this.set('_timeOnly', true);
				todayBtn = false;
			}

			$datetimepicker.datetimepicker({
				format: this.get('format'),
				minuteStep: this.get('minuteStep'),
				autoclose: true,
				todayHighlight: true,
				todayBtn: todayBtn,
				pickerPosition: 'bottom',
				minView: viewSettings.minView,
				maxView: viewSettings.maxView,
				startView: viewSettings.startView,
				weekStart: 1,
				formatType: 'php'
			}).on('changeDate',function(event) {
				that.set('value', that.formatDate(new Date(event.date), 'Y-m-dTH:i:s', true));
				that.close();
			});

			// Hide datetimepicker by default
			$datetimepicker.hide();

			if (this.get('value') && !/Invalid|NaN/.test(new Date(this.get('value')).toString())) {
				$datetimepicker.datetimepicker('update', new Date(this.get('value')));
			}
		},

		/**
		 * @return {void}
		 */
		open: function() {
			if (this.get('isOpen') === false) {
				this.toggle();
				this._registerEventHandler();
			}
		},

		/**
		 * @return {void}
		 */
		close: function() {
			if (this.get('isOpen') === true) {
				this.toggle();
			}
		},

		/**
		 * @return {void}
		 */
		toggle: function() {
			this.set('isOpen', !this.get('isOpen'));
			this.$('.neos-editor-datetimepicker').slideToggle();
		},

		/**
		 * @return {void}
		 */
		reset: function() {
			this.close();
			this.set('hrValue', '');
			this.$('.neos-editor-datetimepicker').datetimepicker('update', new Date());
			this.$('.neos-editor-datetimepicker').datetimepicker('showMode', 2);
			this.set('value', '');
		},

		/**
		 * 0 or 'hour' for the hour view
		 * 1 or 'day' for the day view
		 * 2 or 'month' for month view (the default)
		 * 3 or 'year' for the 12-month overview
		 * 4 or 'decade' for the 10-year overview. Useful for date-of-birth datetimepickers.
		 *
		 * @return {object}
		 */
		calculateViewSettings: function() {
			var format = this.get('format'),
				minView = 0,
				maxView = 4,
				startView = 2;

			if (!/y|Y/.test(format)) {
				maxView = 3;
				if (!/F|m|M|n|t/.test(format)) {
					maxView = 2;
					if (!/d|D|j|l|N|S|w/.test(format)) {
						maxView = 1;
						if (!/a|A|g|G|h|H/.test(format)) {
							maxView = 0;
						}
					}
				}
			}

			if (!/i|s/.test(format)) {
				minView = 1;
				if (!/a|A|g|G|h|H/.test(format)) {
					minView = 2;
					if (!/d|D|j|l|N|S|w/.test(format)) {
						minView = 3;
						if (!/F|m|M|n|t/.test(format)) {
							minView = 4;
						}
					}
				}
			}

			if (startView < minView) {
				startView = minView;
			}

			if (startView > maxView) {
				startView = maxView;
			}

			return {minView: minView, maxView: maxView, startView: startView};
		},

		/**
		 * @return {void}
		 */
		_registerEventHandler: function() {
			var that = this;
			$(document).on('mousedown.neos-datetimepicker', function(e) {
				// Clicked outside the datetimepicker, hide it
				if ($(e.target).parents('.neos-editor-datetimepicker').length === 0) {
					that.close();
					// Remove event handler if the datepicker is not open
					$(document).off('mousedown.neos-datetimepicker')
				}
			});
		}
	});
});