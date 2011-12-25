/**
 * T3.Common
 *
 * Contains JavaScript which is needed in all modules
 */

define(
[
	'phoenix/fixture',
	'text!phoenix/templates/common/launcher.html',
	'text!phoenix/templates/common/launcherpanel.html',
	'text!phoenix/templates/common/confirmationDialog.html'
],
function(fixture, launcherTemplate, launcherPanelTemplate, confirmationdialogTemplate) {

	var T3 = window.T3 || {};
	T3.Common = {};
	var $ = window.Aloha.jQuery || window.jQuery;

	/**
	 * T3.Common.Launcher
	 *
	 * Implements the quicksilver-like launch bar. Consists of a textfield
	 * and a panel which is opened when the textfield is focussed.
	 */
	T3.Common.Launcher = SC.View.extend({
		tagName: 'div',
		classNames: ['t3-launcher'],
		value: '',
		open: false,
		template: SC.Handlebars.compile(launcherTemplate),

		activate: function() {
			this.$().find('.t3-launcher-container input').focus();
		}
	});

	/**
	 * T3.Common.SearchController
	 *
	 * Contains a list of available search items
	 */
	T3.Common.Launcher.SearchController = SC.Object.create({
		_launcherTextField: null,
		_requestIndex: 0,
		_value: '',
		_searching: null,
		_delay: 300,
		_minLength: 1,
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
			TYPO3_TYPO3_Service_ExtDirect_V1_Controller_LauncherController.search(
				value,
				requestIndex,
				function(result) {
					var data = result.data;
					if (that._requestIndex === data.requestIndex) {
						if (result.success) {
							that._response(data);
						} else {
							this._clear();
						}
					}
				}
			);
		},

		_response: function(data) {
			var results = [];
			$.each(data.results, function(key, group) {
				group = SC.Object.create(group);
				var wrappedSearchItems = group.get('items').map(function(searchItem) {
					return SC.Object.create(searchItem);
				});
				group.set('items', wrappedSearchItems);
				results.push(group);
			});
			this.set('searchItems', results);
		},

		_clear: function() {
			this.set('searchItems', []);
		}
	});

	/**
	 * @internal
	 */
	T3.Common.Launcher.TextField = SC.TextField.extend({
		_notEmpty: function() {
			var parent = this.$().parent(),
				notEmptyClass = 'not-empty';
			if (this.get('value') !== null && this.get('value') !== '') {
				parent.addClass(notEmptyClass);
			} else {
				parent.removeClass(notEmptyClass);
			}
		}.observes('value'),

		init: function() {
			T3.Common.Launcher.SearchController.set('_launcherTextField', this);
			this._super();
		},

		cancel: function() {
			this.set('value', '');
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
	T3.Common.Launcher.Panel = SC.View.extend({
		tagName: 'div',
		classNames: ['t3-launcher-panel'],
		open: false,
		focussed: false,
		scrollingInitialized: false,
		template: SC.Handlebars.compile(launcherPanelTemplate),
		_openDidChange: function() {
			var that = this;
			// Delay the execution a bit to give the focus change a chance
			setTimeout(function() {
				var open = that.get('open');
				// TODO: Move position calculations to css transitions (sass)
				if (open) {
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
	 * Implements various types of dialogs which are shown in a lightbox-like manner and overlay
	 * the whole UI.
	 *
	 * @singleton
	 */
	T3.Common.Dialog = SC.Object.create({

		_options: {
			preventLeft: false,
			preventRight: false,
			preventTop: false,
			preventBottom: false,
			zIndex: 10090,
			openEvent: function() {
			}
		},

		_handle: null,

		/**
		 * Render HTML fetched from a certain URL into the dialog / lightbox.
		 *
		 * A <h1> tag in the response is used as dialog title, displayed
		 * in the title bar.
		 * All <a> links get rewritten, such that they do not open inside a new
		 * window, but are loaded inside the lightbox as well.
		 *
		 * Furthermore, all <a> links with a rel starting with "typo3-" are
		 * considered *COMMANDS*. For them, the callback specified in {commands}
		 * gets executed immediately, and then the dialog is closed.
		 *
		 * EXAMPLE
		 * =======
		 *
		 * If one calls:
		 *
		 * ...open('/my/url', {foo: 'bar'}, {
		 *   'my-command': function($domElement) {
		 *     alert('my command executed, href is ' + $domElement.attr('href'));
		 *   }
		 * });
		 *
		 * The following happens:
		 * - The URL /my/url?foo=bar is loaded and displayed inside the dialog
		 * - If you click onto a link, the appropriate URL is loaded and displayed in the dialog
		 * - When a response contains <a href="/something" rel="typo3-my-command">My special command</a>,
		 *   then our callback is executed which we defined above; and the dialog is closed.
		 *
		 * @param {String} url the URL to load data from
		 * @param {Object} data the GET data to append
		 * @param {Object} commands Command-Name --> Callback function list
		 * @param {jQuery} the handle to which the dialog is appended to
		 * @param {Object} options to be overriden for the popover
		 */
		openFromUrl: function(url, data, commands, $handle, overrideOptions) {
			var that = this;
			that._handle = $handle;
			that._overrideOptions(overrideOptions);

			this._fetchUrlForDialog(url, data, commands, function() {
				that._showDialog();
			});
		},

		/**
		 *
		 * @param {Object} options
		 * @param {jQuery} $handle
		 */
		openConfirmPopover: function(options, $handle) {
			var that = this;
			that._handle = $handle;

			var handlerEvents = $handle.data('events');
			if (!handlerEvents['showPopover']) {
					// Set popover content
				that._options.header = (options.title) ? '<h1>' + options.title + '</h1>': null;
				that._options.content = $(options.content === undefined ? '<div />' : '<div>' + options.content + '</div>');

				var view = SC.View.create({
					template: SC.Handlebars.compile(confirmationdialogTemplate),
					save: function() {
						if (options.onOk) {
							options.onOk.call(that);
						}
						$handle.trigger('hidePopover');
					},
					cancel: function() {
						if (options.onCancel) {
							options.onCancel.call(that);
						}
						$handle.trigger('hidePopover');
					}
				});
				if (options.positioning) {
					that._options.positioning = options.positioning;
				}

				that._showDialog();

				view.appendTo(that._options.content);
				if (options.onDialogOpen) {
					options.onDialogOpen.call(that);
				}
			}

			if (options.onDialogOpen) {
				options.onDialogOpen.call(this);
			}
		},

		/**
		 * Show the actual dialog based on configured settings
		 * @return {void}
		 */
		_showDialog: function() {
			this._handle.popover(this._options).trigger('showPopover').removeClass('t3-handle-loading');
		},

		/**
		 * Internal helper which implements the re-writing logic explained in the doc comment
		 * for the "open" method.
		 *
		 * @param {String} url the URL to load data from
		 * @param {Object} data the GET data to append
		 * @param {Object} commands Command-Name --> Callback function list
		 * @param {Function} callback function to be called when data is fetched and processed
		 * @return {void}
		 */
		_fetchUrlForDialog: function(url, data, commands, callback) {
			var that = this;

			$.get(url, data, function(data) {
				var dialog = $('<div />');
				dialog.html(data);

				// Check if we find commands in the returned HTML. If yes,
				// execute them and close the dialog.
				var commandsExecuted = false;
				dialog.find('a[rel|="typo3"]').each(function() {
					var commandName = $(this).attr('rel').substr(6);
					if (commands[commandName]) {
						commands[commandName]($(this));
						commandsExecuted = true;
					}
				});
				if (commandsExecuted) {
					that._handle.trigger('hidePopover')
					return false;
				}

				// <a> links get rewritten to use ajax
				dialog.find('a.t3-ajax-link').click(function() {
					that._fetchUrlForDialog($(this).attr('href'), {}, commands, dialog);
					return false;
				});

				// <h1> is used as dialog title
				that._options.header = dialog.find('h1').first();
				dialog.find('h1').first().remove();
				that._options.content = dialog;

				if (callback) {
					callback.call(this);
				}
			});
		},

		/**
		 * Internal helper to merge override options with defaults
		 *
		 * @param {Object} overrideOptions
		 * @return void
		 */
		_overrideOptions: function(overrideOptions) {
			this._options = $.extend(this._options, overrideOptions || {});
		}
	});


	/**
	 * Notification handler
	 *
	 * @singleton
	 */
	T3.Common.Notification = SC.Object.create({

		ok: function(msg, stay) {
			$.noticeAdd({
				text: msg,
				stay: stay !== undefined ? stay : false,
				type: 'typo3-notification-ok'
			});
		},

		info: function(msg, stay) {
			$.noticeAdd({
				text: msg,
				stay: stay !== undefined ? stay : false,
				type: 'typo3-notification-info'
			});
		},

		notice: function(msg, stay) {
			$.noticeAdd({
				text: msg,
				stay: stay !== undefined ? stay : false,
				type: 'typo3-notification-notice'
			});
		},

		warning: function(msg, stay) {
			$.noticeAdd({
				text: msg,
				stay: stay !== undefined ? stay : true,
				type: 'typo3-notification-warning'
			});
		},

		error: function(msg, stay) {
			$.noticeAdd({
				text: msg,
				stay: stay !== undefined ? stay : true,
				type: 'typo3-notification-error'
			});
		}
	});

	T3.Common.Util = SC.Object.create({
		isValidJsonString: function(jsonString) {
			// The following regular expression comes from http://tools.ietf.org/html/rfc4627 and checks if the JSON is valid
			return !/[^,:{}\[\]0-9.\-+Eaeflnr-u \n\r\t]/.test(jsonString.replace(/"(\\.|[^"\\])*"/g, ''));
		}
	});

	/**
	 * Wrapper class for the localStorage, supporting storage of objects and arrays.
	 * Internally, all values are JSON encoded and decoded automatically.
	 */
	T3.Common.LocalStorage = SC.Object.create({

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
	});

	window.T3 = T3;
});
