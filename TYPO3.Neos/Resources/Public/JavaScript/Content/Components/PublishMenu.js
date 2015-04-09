define(
	[
		'emberjs',
		'Library/jquery-with-dependencies',
		'Shared/LocalStorage',
		'./Button',
		'Content/Model/PublishableNodes',
		'./StorageManager',
		'./PublishAllDialog',
		'./DiscardAllDialog',
		'Shared/Endpoint/NodeEndpoint',
		'Shared/HttpClient',
		'Shared/I18n',
		'text!./PublishMenu.html'
	],
	function (Ember, $, LocalStorage, Button, PublishableNodes, StorageManager, PublishAllDialog, DiscardAllDialog, NodeEndpoint, HttpClient, I18n, template) {
		return Ember.View.extend({
			template: Ember.Handlebars.compile(template),
			elementId: 'neos-publish-menu',
			classNameBindings: [':neos-button-group', '_actionRunning:neos-publish-menu-action-running'],
			autoPublish: function(key, value) {
				if (arguments.length > 1) {
					LocalStorage.setItem('isAutoPublishEnabled', value);
				}
				return LocalStorage.getItem('isAutoPublishEnabled');
			}.property(),

			controller: PublishableNodes,

			_actionRunning: function() {
				return this.get('controller.publishAllRunning') || this.get('controller.discardRunning') || this.get('controller.discardAllRunning');
			}.property('controller.publishAllRunning', 'controller.discardRunning', 'controller.discardAllRunning'),

			/**
			 * The URI of the Workspaces Management backend module
			 */
			_workspacesManagementModuleUri: $('link[rel="neos-module-workspacesmanagement"]').attr('href'),

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
				_storageManager: StorageManager,

				_httpClient: HttpClient,
				_connectionFailedBinding: '_httpClient._failedRequest',

				_saveRunningBinding: '_nodeEndpoint._saveRunning',
				_savePendingBinding: '_storageManager.savePending',

				_publishRunningBinding: 'controller.publishRunning',
				_noChangesBinding: 'controller.noChanges',
				_numberOfChangesBinding: 'controller.numberOfPublishableNodes',

				defaultTemplate: Ember.Handlebars.compile('{{view.label}}'),
				label: function() {
					if (this.get('_savePending')) {
						return 'Saving<span class="neos-ellipsis"></span>'.htmlSafe();
					} else if (this.get('_publishRunning')) {
						return 'Publishing<span class="neos-ellipsis"></span>'.htmlSafe();
					} else if (this.get('autoPublish')) {
						return 'Auto-Publish';
					}

					if (this.get('_noChanges')) {
						return I18n.translate('Main:TYPO3.Neos:published');
					}

					return I18n.translate('Main:TYPO3.Neos:publish') + ' (' + this.get('_numberOfChanges') + ')';
				}.property('_noChanges', 'autoPublish', '_numberOfChanges', '_savePending', '_publishRunning'),

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
					return this.get('_noChanges') || this.get('autoPublish') || this.get('_saveRunning') || this.get('_savePending') || this.get('_publishRunning');
				}.property('_noChanges', 'autoPublish', '_saveRunning', '_savePending', '_publishRunning'),

				_hasChanges: function() {
					if (this.get('autoPublish')) {
						return false;
					}
					return !this.get('_noChanges') || this.get('_saveRunning') || this.get('_savePending');
				}.property('_noChanges', 'autoPublish', '_saveRunning', '_savePending'),

				connectionStatusClass: function() {
					var className = 'neos-connection-status-';
					className += this.get('_connectionFailed') ? 'down' : 'up';
					return className;
				}.property('_connectionFailed')
			}),

			DiscardButton: Button.extend({
				classNames: ['neos-discard-button'],
				controller: PublishableNodes,

				target: 'controller',
				action: 'discardChanges',

				_nodeEndpoint: NodeEndpoint,
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
					return this.get('_noChanges') || this.get('autoPublish') || this.get('_saveRunning') || this.get('controller.discardRunning');
				}.property('_noChanges', 'autoPublish', '_saveRunning', 'controller.discardRunning')
			}),

			PublishAllButton: Button.extend({
				classNameBindings: ['disabledClass'],
				classNames: ['neos-publish-all-button'],
				attributeBindings: ['title'],
				title: 'Publish all',
				labelIcon: '<i class="icon-upload"></i> ',
				label: function() {
					return (this.get('labelIcon') + ' Publish all' + (this.get('_noWorkspaceWideChanges') ? '' : ' (' + this.get('_numberOfWorkspaceWideChanges') + ')')).htmlSafe();
				}.property('_numberOfWorkspaceWideChanges'),
				controller: PublishableNodes,

				_noWorkspaceWideChangesBinding: 'controller.noWorkspaceWideChanges',
				_numberOfWorkspaceWideChangesBinding: 'controller.numberOfWorkspaceWidePublishableNodes',

				_nodeEndpoint: NodeEndpoint,
				_saveRunningBinding: '_nodeEndpoint._saveRunning',

				click: function() {
					PublishAllDialog.create();
				},

				didInsertElement: function() {
					PublishableNodes.getWorkspaceWideUnpublishedNodes();
				},

				disabled: function() {
					return this.get('_noWorkspaceWideChanges') || this.get('_saveRunning') || this.get('controller.publishAllRunning');
				}.property('_noWorkspaceWideChanges', '_saveRunning', 'controller.publishAllRunning'),

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

				_noWorkspaceWideChangesBinding: 'controller.noWorkspaceWideChanges',
				_numberOfWorkspaceWideChangesBinding: 'controller.numberOfWorkspaceWidePublishableNodes',

				_nodeEndpoint: NodeEndpoint,
				_saveRunningBinding: '_nodeEndpoint._saveRunning',

				click: function() {
					DiscardAllDialog.create();
				},

				disabled: function() {
					return this.get('_noWorkspaceWideChanges') || this.get('_saveRunning') || this.get('controller.discardAllRunning');
				}.property('_noWorkspaceWideChanges', '_saveRunning', 'controller.discardAllRunning'),

				disabledClass: function() {
					return this.get('_noWorkspaceWideChanges') || this.get('_saveRunning') ? 'disabled' : '';
				}.property('_noWorkspaceWideChanges', '_saveRunning')
			})
		});
	}
);
