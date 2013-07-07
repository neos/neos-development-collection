define(
	[
		'emberjs',
		'./Button',
		'text!./PublishMenu.html'
	],
	function (Ember, Button, template) {
		return Ember.View.extend({
			template: Ember.Handlebars.compile(template),
			classNames: ['neos-publish-menu', 'btn-group'],
			classNameBindings: ['_disabled:neos-disabled'],
			autopublish: false,

			_disabled: function() {
				return this.get('_noChanges') && !this.get('autopublish');
			}.property('_noChanges', 'autopublish'),

			_noChangesBinding: 'T3.Content.Model.PublishableNodes.noChanges',

			PublishButton: Button.extend({
				autopublish: false,
				classNameBindings: ['connectionStatusClass'],
				classNames: ['neos-publish-button'],

				target: 'T3.Content.Model.PublishableNodes',
				action: 'publishAll',
				_connectionFailedBinding: 'T3.Content.Controller.ServerConnection._failedRequest',
				_saveRunningBinding: 'T3.Content.Controller.ServerConnection._saveRunning',
				_noChangesBinding: 'T3.Content.Model.PublishableNodes.noChanges',

				label: function() {
					if (this.get('autopublish')) {
						return 'Auto-Publish';
					} else {
						return 'Publish';
					}
				}.property('autopublish'),

				_autopublishTimer: null,

				_autopublishTimerOnAutopublish: function() {
					var that = this;

					if (this.get('autopublish') && !this._autopublishTimer) {
						this._autopublishTimer = window.setInterval(function() {
							if (!that.get('_saveRunning') && !that.get('noChanges')) {
								that.triggerAction();
							}
						}, 10000);
					} else if (this._autopublishTimer) {
						window.clearInterval(this._autopublishTimer);
						this._autopublishTimer = null;
					}
				}.observes('autopublish'),

				disabled: function() {
					return this.get('_noChanges') || this.get('_saveRunning');
				}.property('_noChanges', '_saveRunning'),

				connectionStatusClass: function() {
					var className = 'neos-connection-status-';
					className += this.get('_connectionFailed') ? 'down' : 'up';
					return className;
				}.property('_connectionFailed')
			})

		});
	}
);