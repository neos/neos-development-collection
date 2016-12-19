/**
 * Login dialog shown when the session is expired
 */
define(
[
	'emberjs',
	'Library/jquery-with-dependencies',
	'text!./LoginDialog.html',
	'Shared/Notification',
	'Shared/Configuration'
], function(
	Ember,
	$,
	template,
	Notification,
	Configuration
) {
	return Ember.Object.extend({
		view: null,
		callbacks: [],

		dialog: Ember.View.extend({
			classNames: ['neos-login-dialog'],
			template: Ember.Handlebars.compile(template),
			failed: false,
			authenticating: false,

			/**
			 * @var {String}
			 */
			_endpoint: $('link[rel="neos-login"]').attr('href'),

			/**
			 * @var {String}
			 */
			_username: $('[name="neos-username"]').attr('content'),

			/**
			 * @var {String}
			 */
			_password: '',

			init: function() {
				this._super();
				this.appendTo('#neos-application');
			},

			didInsertElement: function () {
				$('input[type="password"]', this.$()).focus();
				var that = this;
				$('form', this.$()).submit(function(event) {
					event.preventDefault();
					that.login();
				});
			},

			login: function () {
				var that = this;
				if (this.get('_username') === '' && this.get('_password') === '') {
					Notification.error('Please enter a username and password.');
					return;
				}

				this.set('failed', false);
				this.set('authenticating', true);
				$.ajax({
					type: 'POST',
					url: this._endpoint,
					data: $('form', this.$()).serialize()
				}).done(function (result) {
					that.set('authenticating', false);
					if (result.success === true) {
						Notification.ok('Authentication successful.');
						Configuration.override('CsrfToken', result.csrfToken);
						that.get('controller').execute();
						that.destroy();
					} else {
						that.set('failed', true);
						that.set('_password', '');
						$('.neos-modal', that.$()).effect('shake', {times: 1}, 60);
					}
				}).fail(function (jqXHR, textStatus, errorThrown) {
					that.set('authenticating', false);
					Notification.error('An error occurred while trying to login.');
				});
			}
		}),

		show: function(callback) {
			this.get('callbacks').push(callback);
			if (this.get('view') === null) {
				this.set('view', this.get('dialog').create({controller: this}));
			}
		},

		hide: function() {
			if (this.get('view') !== null) {
				this.get('view').destroy();
			}
			this.clear();
		},

		execute: function() {
			this.get('callbacks').forEach(function(callback) {
				callback();
			});
			this.clear();
		},

		clear: function() {
			this.set('callbacks', []);
			this.set('view', null);
		}
	}).create();
});