define(
	[
		'emberjs',
		'Shared/LocalStorage',
		'./Button',
		'Content/Model/PublishableNodes',
		'./PublishAllDialog',
		'text!./PublishMenu.html'
	],
	function (Ember, LocalStorage, Button, PublishableNodes, PublishAllDialog, template) {
		return Ember.View.extend({
			template: Ember.Handlebars.compile(template),
			elementId: 'neos-publish-menu',
			classNames: ['neos-button-group'],
			autoPublish: function(key, value) {
				if (arguments.length > 1) {
					LocalStorage.setItem('isAutoPublishEnabled', value);
				}
				return LocalStorage.getItem('isAutoPublishEnabled');
			}.property(),

			controller: PublishableNodes,

			_hasWorkspaceWideChanges: function() {
				return !this.get('_noWorkspaceWideChanges');
			}.property('_noWorkspaceWideChanges'),

			_noWorkspaceWideChangesBinding: 'controller.noWorkspaceWideChanges',

			didInsertElement: function() {
				this.$().find('.neos-dropdown-toggle').dropdown();
			},

			PublishButton: Button.extend({
				autoPublish: false,
				classNameBindings: ['connectionStatusClass', '_hasChanges:neos-publish-menu-active'],
				classNames: ['neos-publish-button'],
				controller: PublishableNodes,

				target: 'controller',
				action: 'publishChanges',

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

				_hasChanges: function() {
					return !this.get('_noChanges') && !this.get('autoPublish');
				}.property('_noChanges', 'autoPublish'),

				connectionStatusClass: function() {
					var className = 'neos-connection-status-';
					className += this.get('_connectionFailed') ? 'down' : 'up';
					return className;
				}.property('_connectionFailed')
			}),

			PublishAllButton: Button.extend({
				classNameBindings: ['disabledClass'],
				classNames: ['neos-publish-all-button'],
				attributeBindings: ['title'],
				title: 'Publish all',
				labelIcon: '<i class="icon-upload"></i> ',
				label: function() {
					if (this.get('noWorkspaceWideChanges')) {
						return (this.get('labelIcon') + ' Published').htmlSafe();
					} else {
						return (this.get('labelIcon') + ' Publish all (' + this.get('_numberOfWorkspaceWideChanges') + ')').htmlSafe();
					}
				}.property('_numberOfWorkspaceWideChanges'),
				controller: PublishableNodes,
				confirmationDialog: PublishAllDialog.create(),

				_saveRunningBinding: 'T3.Content.Controller.ServerConnection._saveRunning',
				_noWorkspaceWideChangesBinding: 'controller.noWorkspaceWideChanges',
				_numberOfWorkspaceWideChangesBinding: 'controller.numberOfWorkspaceWidePublishableNodes',

				click: function() {
					this.confirmationDialog.createElement();
				},

				didInsertElement: function() {
					PublishableNodes.getWorkspaceWideUnpublishedNodes();
				},

				disabled: function() {
					return this.get('_noChanges') || this.get('_saveRunning');
				}.property('_noChanges', '_saveRunning'),

				disabledClass: function() {
					return this.get('_noChanges') || this.get('_saveRunning') ? 'disabled' : '';
				}.property('_noChanges', '_saveRunning')
			})
		});
	}
);
