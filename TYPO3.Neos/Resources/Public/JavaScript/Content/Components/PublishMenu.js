define(
	[
		'emberjs',
		'Shared/LocalStorage',
		'./Button',
		'Content/Model/PublishableNodes',
		'./PublishAllDialog',
		'./DiscardAllDialog',
		'Shared/Endpoint/NodeEndpoint',
		'text!./PublishMenu.html'
	],
	function (Ember, LocalStorage, Button, PublishableNodes, PublishAllDialog, DiscardAllDialog, NodeEndpoint, template) {
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

				_nodeEndpoint: NodeEndpoint,

				_connectionFailedBinding: '_nodeEndpoint._failedRequest',
				_saveRunningBinding: '_nodeEndpoint._saveRunning',

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
								PublishableNodes.publishChanges(true);
							}
						}, 10000);
					} else if (this._autoPublishTimer) {
						window.clearInterval(this._autoPublishTimer);
						this._autoPublishTimer = null;
					}
				}.observes('autoPublish').on('init'),

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

			DiscardButton: Button.extend({
				classNameBindings: ['connectionStatusClass'],
				classNames: ['neos-discard-button'],
				controller: PublishableNodes,

				target: 'controller',
				action: 'discardChanges',

				_nodeEndpoint: NodeEndpoint,

				_connectionFailedBinding: '_nodeEndpoint._failedRequest',
				_saveRunningBinding: '_nodeEndpoint._saveRunning',

				_noChangesBinding: 'controller.noChanges',
				_numberOfChangesBinding: 'controller.numberOfPublishableNodes',

				label: function() {
					return ('<i class="icon-ban-circle"></i> ' + this.get('title')).htmlSafe();
				}.property('title'),

				title: function() {
					return this.get('_noChanges') ? 'Discard' : 'Discard' + ' ('  + this.get('_numberOfChanges') + ')';
				}.property('_noChanges', '_numberOfChanges'),

				disabled: function() {
					return this.get('_noChanges') || this.get('autoPublish') || this.get('_saveRunning');
				}.property('_noChanges', 'autoPublish', '_saveRunning'),

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
					if (this.get('_noWorkspaceWideChanges')) {
						return (this.get('labelIcon') + ' Published').htmlSafe();
					} else {
						return (this.get('labelIcon') + ' Publish all (' + this.get('_numberOfWorkspaceWideChanges') + ')').htmlSafe();
					}
				}.property('_numberOfWorkspaceWideChanges'),
				controller: PublishableNodes,
				confirmationDialog: PublishAllDialog.create(),

				_noWorkspaceWideChangesBinding: 'controller.noWorkspaceWideChanges',
				_numberOfWorkspaceWideChangesBinding: 'controller.numberOfWorkspaceWidePublishableNodes',
				_nodeEndpoint: NodeEndpoint,

				_saveRunningBinding: '_nodeEndpoint._saveRunning',

				click: function() {
					this.confirmationDialog.createElement();
				},

				didInsertElement: function() {
					PublishableNodes.getWorkspaceWideUnpublishedNodes();
				},

				disabled: function() {
					return this.get('_noWorkspaceWideChanges') || this.get('_saveRunning');
				}.property('_noWorkspaceWideChanges', '_saveRunning'),

				disabledClass: function() {
					return this.get('_noWorkspaceWideChanges') || this.get('_saveRunning') ? 'disabled' : '';
				}.property('_noWorkspaceWideChanges', '_saveRunning')
			}),

			DiscardAllButton: Button.extend({
				classNameBindings: ['disabledClass'],
				classNames: ['neos-publish-all-button'],
				attributeBindings: ['title'],
				title: 'Discard all',
				labelIcon: '<i class="icon-ban-circle"></i> ',
				label: function() {
					if (this.get('_noWorkspaceWideChanges')) {
						return (this.get('labelIcon') + ' Discard all').htmlSafe();
					} else {
						return (this.get('labelIcon') + ' Discard all (' + this.get('_numberOfWorkspaceWideChanges') + ')').htmlSafe();
					}
				}.property('_numberOfWorkspaceWideChanges'),
				controller: PublishableNodes,
				confirmationDialog: DiscardAllDialog.create(),

				_noWorkspaceWideChangesBinding: 'controller.noWorkspaceWideChanges',
				_numberOfWorkspaceWideChangesBinding: 'controller.numberOfWorkspaceWidePublishableNodes',
				_nodeEndpoint: NodeEndpoint,

				_saveRunningBinding: '_nodeEndpoint._saveRunning',

				click: function() {
					this.confirmationDialog.createElement();
				},

				disabled: function() {
					return this.get('_noWorkspaceWideChanges') || this.get('_saveRunning');
				}.property('_noWorkspaceWideChanges', '_saveRunning'),

				disabledClass: function() {
					return this.get('_noWorkspaceWideChanges') || this.get('_saveRunning') ? 'disabled' : '';
				}.property('_noWorkspaceWideChanges', '_saveRunning')
			})
		});
	}
);
