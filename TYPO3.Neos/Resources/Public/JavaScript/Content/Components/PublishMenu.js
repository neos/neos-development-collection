define(
	[
		'emberjs',
		'./Button',
		'text!./PublishMenu.html'
	],
	function (Ember, Button, template) {
		return Ember.View.extend({
			template: Ember.Handlebars.compile(template),
			classNames: ['neos-publish-menu', 'neos-btn-group'],
			classNameBindings: ['_hasChanges:neos-publish-menu-active'],
			autoPublish: false,

			_hasChanges: function() {
				return !this.get('_noChanges') && !this.get('autoPublish');
			}.property('_noChanges', 'autoPublish'),

			_noChangesBinding: 'T3.Content.Model.PublishableNodes.noChanges',

			didInsertElement: function() {
				this.$().find('.neos-dropdown-toggle').dropdown();
			},

			PublishButton: Button.extend({
				autoPublish: false,
				classNameBindings: ['connectionStatusClass'],
				classNames: ['neos-publish-button'],

				target: 'T3.Content.Model.PublishableNodes',
				action: 'publishAll',
				_connectionFailedBinding: 'T3.Content.Controller.ServerConnection._failedRequest',
				_saveRunningBinding: 'T3.Content.Controller.ServerConnection._saveRunning',
				_noChangesBinding: 'T3.Content.Model.PublishableNodes.noChanges',

				label: function() {
					if (this.get('autoPublish')) {
						return 'Auto-Publish';
					} else {
						return this.get('_noChanges') ? 'Published' : 'Publish';
					}
				}.property('_noChanges', 'autoPublish'),

				_autoPublishTimer: null,

				_autoPublishTimerOnAutoPublish: function() {
					var that = this;

					if (this.get('autoPublish') && !this._autoPublishTimer) {
						this._autoPublishTimer = window.setInterval(function() {
							if (!that.get('_saveRunning') && !that.get('noChanges')) {
								that.triggerAction();
							}
						}, 10000);
					} else if (this._autoPublishTimer) {
						window.clearInterval(this._autoPublishTimer);
						this._autoPublishTimer = null;
					}
				}.observes('autoPublish'),

				disabled: function() {
					return this.get('_noChanges') || this.get('autoPublish') || this.get('_saveRunning');
				}.property('_noChanges', 'autoPublish', '_saveRunning'),

				connectionStatusClass: function() {
					var className = 'neos-connection-status-';
					className += this.get('_connectionFailed') ? 'down' : 'up';
					return className;
				}.property('_connectionFailed')
			})

		});
	}
);