/**
 * Controllers which are not model- but appearance-related
 */

define(
[
	'Content/Application',
	'Library/jquery-with-dependencies',
	'Library/underscore',
	'Library/backbone',
	'create',
	'emberjs',
	'Shared/LocalStorage',
	'Shared/Notification'
],
function(ContentModule, $, _, Backbone, CreateJS, Ember, LocalStorage, Notification) {
	var T3 = window.T3 || {};
	T3.Content = T3.Content || {};

	var ServerConnection = Ember.Object.extend({
		_lastSuccessfulTransfer: null,
		_failedRequest: false,
		_pendingSave: false,
		_saveRunning: false,

		sendAllToServer: function(collection, transformFn, extDirectFn, callback, elementCallback) {
			var that = this,
				numberOfUnsavedRecords = collection.get('length'),
				responseCallback = function(element) {
					return function(provider, response) {
						if (!response.result || response.result.success !== true) {
								// TODO: Find a way to avoid this notice
							Notification.error('Server communication error, reload the page to return to a safe state if another publish does not work');
							that.set('_failedRequest', true);
							return;
						} else {
							that.set('_failedRequest', false);
							that.set('_lastSuccessfulTransfer', new Date());
						}

						if (elementCallback) {
							elementCallback(element, response);
						}
						numberOfUnsavedRecords--;
						if (numberOfUnsavedRecords <= 0) {
							that.set('_saveRunning', false);
							if (callback) {
								callback();
							}
						}
					};
				};
			collection.forEach(function(element) {
					// Force copy of array
				var args = transformFn(element).slice();
				args.push(responseCallback(element));
				that.set('_saveRunning', true);
				extDirectFn.apply(window, args);
			});
		},

		statusClass: function() {
			this.set('_saveRunning', false);
			return 'neos-connection-status-' + this.get('_failedRequest') ? 'down' : 'up';
		}.observes('_failedRequest')

	}).create();

	T3.Content.Controller = {
		ServerConnection: ServerConnection
	};
	window.T3 = T3;
});
