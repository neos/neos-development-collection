/**
 * Login dialog shown when the session is expired
 */
define(
[
	'emberjs',
	'Library/jquery-with-dependencies',
	'text!./LoginDialog.html',
	'Shared/Notification'
], function(
	Ember,
	$,
	template,
	Notification
) {
	return Ember.View.extend({
		classNames: ['neos-login-dialog'],
		template: Ember.Handlebars.compile(template),

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

		didInsertElement: function() {
			$('input[type="password"]', this.$()).focus();
		},

		login: function() {
			var that = this;
			if (this.get('_username') === '' && this.get('_password') === '') {
				Notification.error('Please enter a username and password.');
				return;
			}

			$.ajax({
				type: 'POST',
				url: this._endpoint,
				data: $('form', this.$()).serialize()
			}).done(function(result) {
				if (result.success === true) {
					Notification.ok('Authentication successful.');
					that.destroy();
					that.get('callback')();
				} else {
					Notification.error('The entered username or password was wrong.');
					that.set('_password', '');
				}
			}).fail(function(jqXHR, textStatus, errorThrown) {
				Notification.error('An error occurred while trying to login.');
			});
		}
	});
});