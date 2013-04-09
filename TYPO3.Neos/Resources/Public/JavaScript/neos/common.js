/**
 * T3.Common
 *
 * Contains JavaScript which is needed in all modules
 */

define(
[
	'jquery',
	'emberjs',
	'text!neos/templates/common/launcher.html',
	'text!neos/templates/common/launcherpanel.html',
	'bootstrap.alert',
	'bootstrap.notify'
],
function($, Ember, launcherTemplate, launcherPanelTemplate) {
	if (window._requirejsLoadingTrace) window._requirejsLoadingTrace.push('neos/common');

	var T3 = window.T3 || {};
	T3.Common = {};

	/**
	 * T3.Common.Launcher
	 *
	 * Implements the quicksilver-like launch bar. Consists of a textfield
	 * and a panel which is opened when the textfield is focussed.
	 */
	T3.Common.Launcher = Ember.View.extend({
		tagName: 'div',
		classNames: ['t3-launcher'],
		value: '',
		open: false,
		template: Ember.Handlebars.compile(launcherTemplate),

		activate: function() {
			this.$().find('.t3-launcher-container input').focus();
		}
	});

	/**
	 * T3.Common.SearchController
	 *
	 * Contains a list of available search items
	 */
	T3.Common.Launcher.SearchController = Ember.Object.extend({
		_launcherTextField: null,
		_requestIndex: 0,
		_value: '',
		_searching: null,
		_delay: 300,
		_minLength: 1,
		isLoading: false,
		searchItems: [],

		keyHandler: function(event) {
			suppressKeyPress = false;
			var keyCode = $.ui.keyCode;
			switch (event.keyCode) {
				case keyCode.UP:
					suppressKeyPress = true;
					this._move('previous', event);
					// prevent moving cursor to beginning of text field in some browsers
					event.preventDefault();
					break;
				case keyCode.DOWN:
					suppressKeyPress = true;
					this._move('next', event);
					// prevent moving cursor to end of text field in some browsers
					event.preventDefault();
					break;
				case keyCode.ENTER:
				case keyCode.NUMPAD_ENTER:
					suppressKeyPress = true;
					event.preventDefault();
					break;
				case keyCode.TAB:
					this._nextGroup();
					break;
				case keyCode.ESCAPE:
					this.get('_launcherTextField').cancel();
					break;
				default:
					// search timeout should be triggered before the input value is changed
					this._searchTimeout(event);
					break;
			}
		},

		_searchTimeout: function(event) {
			var that = this;
			this._searching = setTimeout(function() {
				// only search if the value has changed
				var value = that.get('_launcherTextField').get('value');
				if (that._value !== value) {
					that.set('_value', value);
					that._search(event, value);
				}
			}, this._delay);
		},

		_search: function(event, value) {
			var that = this;

			if (value.length < this._minLength) {
				this._clear();
				return;
			}

			var requestIndex = ++this._requestIndex;
			this.set('isLoading', true);
			TYPO3_Neos_Service_ExtDirect_V1_Controller_LauncherController.search(
				value,
				requestIndex,
				function(result) {
					var data = result.data;
					if (that._requestIndex === data.requestIndex) {
						if (result.success) {
							that._response(data);
						} else {
							that._clear();
						}
						that.set('isLoading', false);
					}
				}
			);
		},

		_response: function(data) {
			var results = [];
			$.each(data.results, function(key, group) {
				group = Ember.Object.create(group);
				var wrappedSearchItems = group.get('items').map(function(searchItem) {
					return Ember.Object.create(searchItem);
				});
				group.set('items', wrappedSearchItems);
				results.push(group);
			});
			this.set('searchItems', results);
		},

		_clear: function() {
			this.set('searchItems', []);
		}
	}).create();

	/**
	 * @internal
	 */
	T3.Common.Launcher.TextField = Ember.TextField.extend({
		_notEmpty: function() {
			var parent = this.$().parent(),
				notEmptyClass = 'not-empty';
			if (this.get('value') !== null && this.get('value') !== '') {
				parent.addClass(notEmptyClass);
			} else {
				parent.removeClass(notEmptyClass);
			}
		}.observes('value'),

		_loadingDidChange: function(object, observing, value) {
			var loadingIndicator = this.$().parent().find('.t3-launcher-loading');
			if (value === true) {
				loadingIndicator.show();
			} else {
				loadingIndicator.hide();
			}
		}.observes('T3.Common.Launcher.SearchController.isLoading'),

		init: function() {
			T3.Common.Launcher.SearchController.set('_launcherTextField', this);
			this._super();
		},

		cancel: function() {
			T3.ContentModule._launcher.set('value', '');
			this.$().blur();
		},

		focusIn: function() {
			this.set('open', true);
		},

		focusOut: function() {
			this.set('open', false);
			this._super();
		},

		keyDown: function(event) {
			T3.Common.Launcher.SearchController.keyHandler(event);
		},

		didInsertElement: function() {
			var that = this;
			this.$().parent().find('.t3-launcher-clear').click(function() {
				that.cancel();
			});
		}
	});

	/**
	 * @internal
	 */
	T3.Common.Launcher.Panel = Ember.View.extend({
		tagName: 'div',
		classNames: ['t3-launcher-panel'],
		open: false,
		focussed: false,
		scrollingInitialized: false,
		template: Ember.Handlebars.compile(launcherPanelTemplate),
		_openDidChange: function() {
			var that = this;
			// Delay the execution a bit to give the focus change a chance
			setTimeout(function() {
				var open = that.get('open');
				// TODO: Move position calculations to css transitions (sass)
				if (open) {
					$(document).trigger('hidePopover');
					$('body').addClass('t3-launcher-open');
					if (!that.scrollingInitialized) {
						$('.c-1 .scroll-content').lionbars('dark', false, true, false);
						that.scrollingInitialized = true;
					}
				} else {
					if (that.get('focussed')) {
						return;
					}
					$('body').removeClass('t3-launcher-open');
				}
			}, 50);
		}.observes('open'),
		focusIn: function() {
			this.set('focussed', true);
		},
		focusOut: function() {
			this.set('focussed', false);
		}
	});

	/**
	 * Notification handler
	 *
	 * @singleton
	 */
	T3.Common.Notification = Ember.Object.extend({
		_timeout: 5000,

		/**
		 * Shows a new notification
		 *
		 * @param {string} message
		 * @param {boolean} fadeout
		 * @param {string} type
		 * @private
		 * @return {void}
		 */
		_show: function(message, fadeout, type) {
			$('.t3-notification-container').notify({
				message: {
					html: message
				},
				type: type,
				fadeOut: {
					enabled: fadeout,
					delay: this.get('_timeout')
				}
			}).show();
		},

		/**
		 * Show ok message
		 *
		 * @param {string} message
		 * @return {void}
		 */
		ok: function(message) {
			this._show('<i class="icon-ok-sign"></i>' + message, true, 'success');
		},

		/**
		 * Show notice message
		 *
		 * @param {string} message
		 * @return {void}
		 */
		notice: function(message) {
			this._show('<i class="icon-info-sign"></i>' + message, true, 'info');
		},

		/**
		 * Show warning message
		 *
		 * @param {string} message
		 * @return {void}
		 */
		warning: function(message) {
			this._show('<i class="icon-warning-sign"></i>' + message, false, 'warning');
		},

		/**
		 * Show error message
		 *
		 * @param {string} message
		 * @return {void}
		 */
		error: function(message) {
			this._show('<i class="icon-exclamation-sign"></i>' + message, false, 'error');
		}
	}).create();

	T3.Common.Util = Ember.Object.extend({
		isValidJsonString: function(jsonString) {
				// The following regular expression comes from http://tools.ietf.org/html/rfc4627 and checks if the JSON is valid
			return !/[^,:{}\[\]0-9.\-+Eaeflnr-u \n\r\t]/.test(jsonString.replace(/"(\\.|[^"\\])*"/g, ''));
		}
	}).create();

	/**
	 * Wrapper class for the localStorage, supporting storage of objects and arrays.
	 * Internally, all values are JSON encoded and decoded automatically.
	 */
	T3.Common.LocalStorage = Ember.Object.extend({
		/**
		* Get an item from localStorage
		*
		* @param {string} key Name of the value to get
		* @return {mixed} Depends on the stored value
		*/
		getItem: function (key) {
			if (!this._supportsLocalStorage()) return undefined;

			try {
				return JSON.parse(window.localStorage.getItem(key));
			} catch (e) {
				return undefined;
			}
		},

		/**
		* Set a value into localStorage
		*
		* @param {string} key
		* @param {mixed} value
		* @return {void}
		*/
		setItem: function (key, value) {
			if (!this._supportsLocalStorage()) return;
			window.localStorage.setItem(key, JSON.stringify(value));
		},

		/**
		* Remove a value form localStorage
		* @param {string} key
		* @return {void}
		*/
		removeItem: function (key) {
			if (!this._supportsLocalStorage()) return;
			window.localStorage.removeItem(key);
		},

		_supportsLocalStorage: function() {
			try {
				return 'localStorage' in window && window['localStorage'] !== null;
			} catch (e) {
				return false;
			}
		}
	}).create();

	window.T3 = T3;
});
