/**
 * T3.Common
 *
 * Contains JavaScript which is needed in all modules
 */

define(
['phoenix/fixture', 'text!phoenix/common/launcher.html', 'text!phoenix/common/launcherpanel.html'],
function(fixture, launcherTemplate, launcherPanelTemplate) {

	var T3 = window.T3 || {};
	T3.Common = {};
	var $ = window.alohaQuery || window.jQuery;

	/**
	 * T3.Common.ModulesController
	 *
	 * Contains a list of available modules
	 */
	T3.Common.ModulesController = SC.Object.create({
		availableModules: [],
		filterValue: null,
		filteredModules: [],
		init: function() {
			this.setAvailableModules(fixture.availableModules);
		},
		setAvailableModules: function(modules) {
			var wrappedModules = modules.map(function(module) {
				return SC.Object.create(module);
			});
			this.set('availableModules', wrappedModules);
			this.set('filteredModules', wrappedModules);
		},
		_filterValueChange: function() {
			var lcFilterValue = this.get('filterValue').toLowerCase();
			if (lcFilterValue === '') {
				this.set('filteredModules', this.get('availableModules'));
			} else {
				this.set('filteredModules', this.get('availableModules').filter(function(module) {
					return module.get('label').toLowerCase().indexOf(lcFilterValue) >= 0;
				}, this));
			}
		}.observes('filterValue')
	});

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
		template: SC.Handlebars.compile(launcherTemplate)
	});

	/**
	 * @internal
	 */
	T3.Common.Launcher.TextField = SC.TextField.extend({
		cancel: function() {
			this.set('value', '');
			this.$().blur();
		},
		focusIn: function() {
			this.set('value', '');
			this.set('open', true);
		},
		focusOut: function() {
			this.set('open', false);
			this._super();
		},
		keyDown: function(event) {
			// TODO Move to controller
			if (event.keyCode === 9) {
				this.$().closest('.t3-launcher').find('.t3-launcher-panel-modules li:first-child a').first().focus();
				return false;
			}
		}
	});

	/**
	 * @internal
	 */
	T3.Common.Launcher.Panel = SC.View.extend({
		tagName: 'div',
		classNames: ['t3-launcher-panel'],
		classNameBindings: ['open'],
		isVisible: false,
		open: false,
		focussed: false,
		template: SC.Handlebars.compile(launcherPanelTemplate),
		_openDidChange: function() {
			var that = this;
			// Delay the execution a bit to give the focus change a chance
			setTimeout(function() {
				var open = that.get('open');
				if (open) {
					that.$().slideDown('fast');
				} else {
					if (that.get('focussed')) return;
					that.$().slideUp('fast');
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

		/**
		 * Display message in a confirmation dialog (with OK and Cancel button),
		 * if OK is pressed, the successCallback is executed.
		 *
		 * @param {String} message the message to show
		 * @param {Function} successCallback callback which is executed when "OK" is pressed.
		 */
		confirm: function(message, successCallback) {
			this._showDialog('<div>' + message + '</div>', {
				buttons: {
					Ok: function() {
						successCallback();
						$(this).dialog("close");
					},
					Cancel: function() {
						$(this).dialog("close");
					}
				}
			});
		},

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
		 */
		open: function(url, data, commands) {
			var that = this;
			this._showDialog('<div>Loading...</div>', {
				open: function() {
					that._fetchUrlForDialog(url, data, commands, $(this))
				}
			});
		},

		/**
		 * Internal helper which implements the re-writing logic explained in the doc comment
		 * for the "open" method.
		 *
		 * @param {String} url the URL to load data from
		 * @param {Object} data the GET data to append
		 * @param {Object} commands Command-Name --> Callback function list
		 * @param {jQuery} $dialog reference to the dialog, into which the fetched page is loaded
		 */
		_fetchUrlForDialog: function(url, data, commands, $dialog) {
			var that = this;
			$.get(url, data, function(data) {
				$dialog.html(data);

				// Check if we find commands in the returned HTML. If yes,
				// execute them and close the dialog.
				var commandsExecuted = false;
				$dialog.find('a[rel|="typo3"]').each(function() {
					var commandName = $(this).attr('rel').substr(6);
					if (commands[commandName]) {
						commands[commandName]($(this));
						commandsExecuted = true;
					}
				});
				if (commandsExecuted) {
					$dialog.dialog('close');
					return;
				}

				// <h1> is used as dialog title
				$dialog.dialog('option', 'title', $dialog.find('h1').html());
				$dialog.find('h1').remove();

				// <a> links get rewritten to use ajax
				$dialog.find('a').click(function() {
					that._fetchUrlForDialog($(this).attr('href'), {}, commands, $dialog);
					return false;
				});
			});
		},

		/**
		 * Internal helper to show a jQuery UI dialog box.
		 */
		_showDialog: function(html, options) {
			options = $.extend({
				modal: true,
				zIndex: 10001
			}, options);

			$(html).dialog(options);
		}
	});
	window.T3 = T3;
});