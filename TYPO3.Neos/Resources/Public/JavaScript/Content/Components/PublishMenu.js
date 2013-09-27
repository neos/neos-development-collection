define(
	[
		'emberjs',
		'./Button',
		'Content/Model/PublishableNodes',
		'text!./PublishMenu.html'
	],
	function (
		Ember,
		Button,
		PublishableNodes,
		template
	) {
		return Ember.View.extend({
			template: Ember.Handlebars.compile(template),
			classNames: ['neos-publish-menu', 'neos-btn-group'],
			classNameBindings: ['_hasChanges:neos-publish-menu-active'],
			autoPublish: false,

			controller: PublishableNodes,

			_hasChanges: function() {
				return !this.get('_noChanges') && !this.get('autoPublish');
			}.property('_noChanges', 'autoPublish'),

			_noChangesBinding: 'controller.noChanges',

			didInsertElement: function() {
				this.$().find('.neos-dropdown-toggle').dropdown();
			},

			PublishButton: Button.extend({
				autoPublish: false,
				classNameBindings: ['connectionStatusClass'],
				classNames: ['neos-publish-button'],
				controller: PublishableNodes,

				target: 'controller',
				action: 'publishAll',
				_connectionFailedBinding: 'T3.Content.Controller.ServerConnection._failedRequest',
				_saveRunningBinding: 'T3.Content.Controller.ServerConnection._saveRunning',

				_noChangesBinding: 'controller.noChanges',
				_numberOfChangesBinding: 'controller.numberOfPublishableNodes',

				label: function() {
					if (this.get('autoPublish')) {
						return 'Auto-Publish';
					} else {
						return this.get('_noChanges') ? 'Published' : 'Publish' + ' ('  + this.get('_numberOfChanges') + ')';
					}
				}.property('_noChanges', 'autoPublish', '_numberOfChanges'),

				title: function() {
					var titleText = 'Publish all ' + this.get('_numberOfChanges') + ' changes for current page';
					if (this.get('autoPublish')) {
						return titleText;
					} else if (!this.get('_noChanges')) {
						return titleText;
					}
				}.property('_noChanges', 'autoPublish', '_numberOfChanges'),

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