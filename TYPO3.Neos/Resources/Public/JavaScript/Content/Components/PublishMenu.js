define(
	[
		'emberjs',
		'Library/jquery-with-dependencies',
		'Shared/LocalStorage',
		'./Button',
		'Content/Model/PublishableNodes',
		'./StorageManager',
		'./TargetWorkspaceController',
		'./TargetWorkspaceSelector',
		'./PublishAllDialog',
		'./DiscardAllDialog',
		'Shared/Endpoint/NodeEndpoint',
		'Shared/HttpClient',
		'Shared/I18n',
		'text!./PublishMenu.html'
	],
	function (
		Ember,
		$,
		LocalStorage,
		Button,
		PublishableNodes,
		StorageManager,
		TargetWorkspaceController,
		TargetWorkspaceSelector,
		PublishAllDialog,
		DiscardAllDialog,
		NodeEndpoint,
		HttpClient,
		I18n,
		template
	) {
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
			targetWorkspaceController: TargetWorkspaceController,

			/**
			 * Only show the target workspace selector if more than one workspace can be selected
			 */
			_isTargetWorkspaceSelectorVisible: function() {
				return this.targetWorkspaceController.get('targetWorkspaces').length > 1;
			}.property('targetWorkspaceController.targetWorkspaces'),

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
				targetWorkspaceController: TargetWorkspaceController,
				targetWorkspaceSelector: TargetWorkspaceSelector,

				target: 'controller',
				action: 'publishChanges',

				_nodeEndpoint: NodeEndpoint,
				_storageManager: StorageManager,

				_httpClient: HttpClient,
				_connectionFailedBinding: '_httpClient._failedRequest',

				_saveRunningBinding: '_nodeEndpoint._saveRunning',
				_savePendingBinding: '_storageManager.savePending',

				_workspaceRebasePendingBinding: 'targetWorkspaceController.workspaceRebasePending',

				_publishRunningBinding: 'controller.publishRunning',
				_noChangesBinding: 'controller.noChanges',
				_numberOfChangesBinding: 'controller.numberOfPublishableNodes',

				defaultTemplate: Ember.Handlebars.compile('{{view.label}}'),

				_labelBinding: 'targetWorkspaceController.targetWorkspaceLabel',

				label: function() {
					if (this.get('_savePending')) {
						return 'Saving<span class="neos-ellipsis"></span>'.htmlSafe();
					} else if (this.get('_publishRunning')) {
						return 'Publishing<span class="neos-ellipsis"></span>'.htmlSafe();
					} else if (this.get('autoPublish')) {
						return 'Auto-Publish' + (this.get('_label') ? ' (' + this.get('_label') + ')' : '');
					}

					if (this.get('_noChanges')) {
						return I18n.translate('TYPO3.Neos:Main:published') + (this.get('_label') ? ' (' + this.get('_label') + ')' : '');
					}

					return I18n.translate('TYPO3.Neos:Main:publish') + (this.get('_label') ? ' (' + this.get('_label') + ')' : '') + ' (' + this.get('_numberOfChanges') + ')';
				}.property('_noChanges', 'autoPublish', '_numberOfChanges', '_savePending', '_publishRunning', '_label'),

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
					return this.get('_noChanges') || this.get('autoPublish') || this.get('_saveRunning') || this.get('_savePending') || this.get('_publishRunning') || this.get('_workspaceRebasePending');
				}.property('_noChanges', 'autoPublish', '_saveRunning', '_savePending', '_publishRunning', '_workspaceRebasePending'),

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

			TargetWorkspaceSelector: TargetWorkspaceSelector,

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
					return I18n.translate('TYPO3.Neos:Main:discard') + (this.get('_noChanges') ? '' : ' ('  + this.get('_numberOfChanges') + ')');
				}.property('_noChanges', '_numberOfChanges'),

				disabled: function() {
					return this.get('_noChanges') || this.get('autoPublish') || this.get('_saveRunning') || this.get('controller.discardRunning');
				}.property('_noChanges', 'autoPublish', '_saveRunning', 'controller.discardRunning')
			}),

			PublishAllButton: Button.extend({
				classNameBindings: ['disabledClass'],
				classNames: ['neos-publish-all-button'],
				controller: PublishableNodes,
				attributeBindings: ['title'],
				labelIcon: '<i class="icon-upload"></i> ',

				label: function() {
					return (this.get('labelIcon') + ' ' + this.get('title')).htmlSafe();
				}.property('title'),

				title: function() {
					return (I18n.translate('TYPO3.Neos:Main:publishAll') + (this.get('_noWorkspaceWideChanges') ? '' : ' (' + this.get('_numberOfWorkspaceWideChanges') + ')')).htmlSafe();
				}.property('_numberOfWorkspaceWideChanges'),

				_noWorkspaceWideChangesBinding: 'controller.noWorkspaceWideChanges',
				_numberOfWorkspaceWideChangesBinding: 'controller.numberOfWorkspaceWidePublishableNodes',

				_nodeEndpoint: NodeEndpoint,
				_saveRunningBinding: '_nodeEndpoint._saveRunning',

				click: function() {
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
				controller: PublishableNodes,
				attributeBindings: ['title'],
				labelIcon: '<i class="icon-ban-circle"></i> ',

				title: function() {
					if (this.get('_noWorkspaceWideChanges')) {
						return (I18n.translate('TYPO3.Neos:Main:discardAll')).htmlSafe();
					} else {
						return (I18n.translate('TYPO3.Neos:Main:discardAll') + ' (' + this.get('_numberOfWorkspaceWideChanges') + ')').htmlSafe();
					}
				}.property('_numberOfWorkspaceWideChanges'),

				label: function() {
					return (this.get('labelIcon') + ' ' + this.get('title')).htmlSafe();
				}.property('title'),

				_noWorkspaceWideChangesBinding: 'controller.noWorkspaceWideChanges',
				_numberOfWorkspaceWideChangesBinding: 'controller.numberOfWorkspaceWidePublishableNodes',

				_nodeEndpoint: NodeEndpoint,
				_saveRunningBinding: '_nodeEndpoint._saveRunning',

				click: function() {
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
