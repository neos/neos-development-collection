define(
[
	'emberjs',
	'Library/jquery-with-dependencies',
	'text!./DateTimeEditor.html'
],
function (Ember, $, template) {
	return Ember.View.extend({
		attributeBindings: ['placeholder', 'name', 'value'],
		value: '',
		_timeOnly: false,
		isOpen: false,
		template: Ember.Handlebars.compile(template),

		/**
		 * The date format, combination of p, P, h, hh, i, ii, s, ss, d, dd, m, mm, M, MM, yy, yyyy.
		 *
		 * p : meridian in lower case ('am' or 'pm') - according to locale file
		 * P : meridian in upper case ('AM' or 'PM') - according to locale file
		 * s : seconds without leading zeros
		 * ss : seconds, 2 digits with leading zeros
		 * i : minutes without leading zeros
		 * ii : minutes, 2 digits with leading zeros
		 * h : hour without leading zeros - 24-hour format
		 * hh : hour, 2 digits with leading zeros - 24-hour format
		 * H : hour without leading zeros - 12-hour format
		 * HH : hour, 2 digits with leading zeros - 12-hour format
		 * d : day of the month without leading zeros
		 * dd : day of the month, 2 digits with leading zeros
		 * m : numeric representation of month without leading zeros
		 * mm : numeric representation of the month, 2 digits with leading zeros
		 * M : short textual representation of a month, three letters
		 * MM : full textual representation of a month, such as January or March
		 * yy : two digit representation of a year
		 * yyyy : full numeric representation of a year, 4 digits
		 */
		format: 'dd-mm-yyyy',

		/**
		 * The increment used to build the hour view. A preset is created for each minuteStep minutes.
		 */
		minuteStep: 5,

		/**
		 * The placeholder shown when no date is selected
		 */
		placeholder: 'No date set',

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
		 * @return {string}
		 */
		formatDate: function(date, format) {
			return $.fn.datetimepicker.DPGlobal.formatDate(
				date,
				$.fn.datetimepicker.DPGlobal.parseFormat(format, 'standard'),
				'en',
				'standard'
			);
		},

		/**
		 * @param {string} date
		 * @param {string} format
		 * @return {string}
		 */
		parseDate: function(date, format) {
			return $.fn.datetimepicker.DPGlobal.parseDate(
				date,
				$.fn.datetimepicker.DPGlobal.parseFormat(format, 'standard'),
				'en',
				'standard'
			);
		},

		/**
		 * @return {string}
		 */
		hrValue: function() {
			if (this.get('value')) {
				return this.formatDate(new Date(this.get('value')), this.get('format'));
			}
			return '';
		}.property(),

		/**
		 * @return {void}
		 */
		onHrValueChanged: function() {
			var value = this.get('hrValue');
			this.set('value', this.formatDate(this.parseDate(value, this.get('format')), 'yyyy-mm-dd'));
			this.get('$datetimepicker').datetimepicker('update');
		}.observes('hrValue'),

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
				weekStart: 1
			}).on('changeDate',function(event) {
				that.set('value', that.formatDate(new Date(event.date), 'yyyy-mm-dd'));
				that.set('hrValue', that.formatDate(new Date(event.date), that.get('format')));
				that.close();
			});

			// Hide datetimepicker by default
			$datetimepicker.hide();

			if (this.get('value')) {
				$datetimepicker.datetimepicker('update', this.get('value'));
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
			this.$('.neos-editor-datetimepicker').datetimepicker('update', null);
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
			var format = this.get('format').toLowerCase(),
				minView = 0,
				maxView = 4,
				startView = 2;
			if (format.indexOf('y') === -1) {
				maxView = 3;
				if (format.indexOf('m') === -1) {
					maxView = 2;
					if (format.indexOf('d') === -1) {
						maxView = 1;
						if (format.indexOf('h') === -1) {
							maxView = 0;
						}
					}
				}
			}

			if (format.indexOf('i') === -1 && format.indexOf('s') === -1) {
				minView = 1;
				if (format.indexOf('h') === -1) {
					minView = 2;
					if (format.indexOf('d') === -1) {
						minView = 3;
						if (format.indexOf('m') === -1) {
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