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
  function (Ember,
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
            template) {
    return Ember.View.extend({
      template: Ember.Handlebars.compile(template),
      elementId: 'neos-publish-menu',
      classNameBindings: [':neos-button-group', '_actionRunning:neos-publish-menu-action-running'],
      autoPublish: function (key, value) {
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
      _isTargetWorkspaceSelectorVisible: function () {
        return this.targetWorkspaceController.get('targetWorkspaces').length > 1;
      }.property('targetWorkspaceController.targetWorkspaces'),

      _actionRunning: function () {
        return this.get('controller.publishAllRunning') || this.get('controller.discardRunning') || this.get('controller.discardAllRunning');
      }.property('controller.publishAllRunning', 'controller.discardRunning', 'controller.discardAllRunning'),

      /**
       * The URI of the Workspaces Management backend module
       */
      _workspacesManagementModuleUri: $('link[rel="neos-module-workspacesmanagement"]').attr('href'),

      _currentWorkspaceManagementModuleUri: function() {
        return $('link[rel="neos-module-workspacesmanagement-show"]').attr('href') + '?moduleArguments[workspace]=' + this.get('targetWorkspaceController.userWorkspace.name');
      }.property('targetWorkspaceController.targetWorkspace'),

      _hasWorkspaceWideChanges: function () {
        return !this.get('_noWorkspaceWideChanges');
      }.property('_noWorkspaceWideChanges'),

      _noWorkspaceWideChangesBinding: 'controller.noWorkspaceWideChanges',

      didInsertElement: function () {
        this.$().find('.neos-dropdown-toggle').dropdown();
        this.scheduleTooltips();
      },

      scheduleTooltips: function() {
        Ember.run.scheduleOnce('afterRender', this, this.activateTooltips);
      }.observes('title').on('init'),

      activateTooltips: function() {
        if (this.$()) {
          this.$().find('[data-neos-tooltip]').tooltip({container: '#neos-application'});
        }
      },

      PublishButton: Button.extend({
        autoPublish: false,
        'data-neos-tooltip': '',
        'data-placement': 'left',
        attributeBindings: ['data-neos-tooltip', 'data-placement', 'data-original-title'],
        classNameBindings: ['connectionStatusClass', '_hasChanges:neos-publish-menu-active', '_disabled:neos-disabled'],
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
        _targetWorkspaceReadOnlyBinding: 'targetWorkspaceController.targetWorkspace.readOnly',

        label: function () {
          if (this.get('_savePending')) {
            return (I18n.translate('TYPO3.Neos:Main:saving') + '<span class="neos-ellipsis"></span>').htmlSafe();
          } else if (this.get('_publishRunning')) {
            return (I18n.translate('TYPO3.Neos:Main:publishing') + '<span class="neos-ellipsis"></span>').htmlSafe();
          } else if (this.get('autoPublish')) {
            if (this.get('_label')) {
              return I18n.translate('autoPublishTo', '', 'TYPO3.Neos', 'Main', [this.get('_label')]).htmlSafe();
            } else {
              return I18n.translate('autoPublish', '', 'TYPO3.Neos', 'Main').htmlSafe();
            }
          }

          if (this.get('_noChanges')) {
            return new Ember.Handlebars.SafeString(I18n.translate('TYPO3.Neos:Main:published') + (this.get('_label') ? ' - ' + this.get('_label') : ''));
          }

          if (this.get('_label')) {
            return new Ember.Handlebars.SafeString(I18n.translate('TYPO3.Neos:Main:publishTo', '', 'TYPO3.Neos', 'Main', [this.get('_label')]) + ' <span class="neos-badge">' + this.get('_numberOfChanges') + '</span>');
          } else {
            return I18n.translate('TYPO3.Neos:Main:publish') + ' (' + this.get('_numberOfChanges') + ')';
          }

        }.property('_noChanges', 'autoPublish', '_numberOfChanges', '_savePending', '_publishRunning', '_label'),

        'data-original-title': function () {
          if (this.get('_targetWorkspaceReadOnly')) {
            return I18n.translate('TYPO3.Neos:Main:cantPublishBecauseTargetWorkspaceIsReadOnly');
          }
          if (this.get('autoPublish') || !this.get('_noChanges')) {
            return I18n.translate('TYPO3.Neos:Main:publishAllChangesForCurrentPage');
          }
          return '';
        }.property('_noChanges', 'autoPublish', '_numberOfChanges', '_targetWorkspaceReadOnly'),

        _autoPublishTimer: null,

        _autoPublishTimerOnAutoPublish: function () {
          var that = this;

          if (this.get('autoPublish') && !this._autoPublishTimer) {
            this._autoPublishTimer = window.setInterval(function () {
              if (!that.get('_saveRunning') && !that.get('noChanges')) {
                PublishableNodes.publishChanges(true);
              }
            }, 10000);
          } else if (this._autoPublishTimer) {
            window.clearInterval(this._autoPublishTimer);
            this._autoPublishTimer = null;
          }
        }.observes('autoPublish').on('init'),

        _disabled: function () {
          return this.get('_noChanges') || this.get('autoPublish') || this.get('_saveRunning') || this.get('_savePending') || this.get('_publishRunning') || this.get('_workspaceRebasePending') || this.get('_targetWorkspaceReadOnly');
        }.property('_noChanges', 'autoPublish', '_saveRunning', '_savePending', '_publishRunning', '_workspaceRebasePending', '_targetWorkspaceReadOnly'),

        triggerAction: function() {
          if (this.get('_disabled')) {
            return;
          }
          this._super();
        },

        _hasChanges: function () {
          if (this.get('autoPublish')) {
            return false;
          }
          return !this.get('_noChanges') || this.get('_saveRunning') || this.get('_savePending');
        }.property('_noChanges', 'autoPublish', '_saveRunning', '_savePending'),

        connectionStatusClass: function () {
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

        label: function () {
          return ('<i class="icon-ban-circle"></i> ' + I18n.translate('TYPO3.Neos:Main:discard') + (this.get('_noChanges') ? '' : ' (' + this.get('_numberOfChanges') + ')')).htmlSafe();
        }.property('_noChanges', '_numberOfChanges'),

        disabled: function () {
          return this.get('_noChanges') || this.get('autoPublish') || this.get('_saveRunning') || this.get('controller.discardRunning');
        }.property('_noChanges', 'autoPublish', '_saveRunning', 'controller.discardRunning')
      }),

      PublishAllButton: Button.extend({
        classNameBindings: ['disabledClass'],
        classNames: ['neos-publish-all-button'],
        controller: PublishableNodes,
        labelIcon: '<i class="icon-upload"></i> ',

        label: function () {
          return (this.get('labelIcon') + ' ' + I18n.translate('TYPO3.Neos:Main:publishAll') + (this.get('_noWorkspaceWideChanges') ? '' : ' (' + this.get('_numberOfWorkspaceWideChanges') + ')')).htmlSafe();
        }.property('_numberOfWorkspaceWideChanges'),

        _noWorkspaceWideChangesBinding: 'controller.noWorkspaceWideChanges',
        _numberOfWorkspaceWideChangesBinding: 'controller.numberOfWorkspaceWidePublishableNodes',

        _nodeEndpoint: NodeEndpoint,
        _saveRunningBinding: '_nodeEndpoint._saveRunning',

        click: function () {
          PublishAllDialog.create();
        },

        didInsertElement: function () {
          PublishableNodes.getWorkspaceWideUnpublishedNodes();
        },

        disabled: function () {
          return this.get('_noWorkspaceWideChanges') || this.get('_saveRunning') || this.get('controller.publishAllRunning');
        }.property('_noWorkspaceWideChanges', '_saveRunning', 'controller.publishAllRunning'),

        disabledClass: function () {
          return this.get('_noWorkspaceWideChanges') || this.get('_saveRunning') ? 'disabled' : '';
        }.property('_noWorkspaceWideChanges', '_saveRunning')
      }),

      DiscardAllButton: Button.extend({
        classNameBindings: ['disabledClass'],
        classNames: ['neos-publish-all-button'],
        controller: PublishableNodes,
        labelIcon: '<i class="icon-ban-circle"></i> ',

        label: function () {
          var label = I18n.translate('TYPO3.Neos:Main:discardAll') + (this.get('_noWorkspaceWideChanges') ? ' (' + this.get('_numberOfWorkspaceWideChanges') + ')' : '');
          return (this.get('labelIcon') + ' ' + label).htmlSafe();
        }.property('_numberOfWorkspaceWideChanges'),

        _noWorkspaceWideChangesBinding: 'controller.noWorkspaceWideChanges',
        _numberOfWorkspaceWideChangesBinding: 'controller.numberOfWorkspaceWidePublishableNodes',

        _nodeEndpoint: NodeEndpoint,
        _saveRunningBinding: '_nodeEndpoint._saveRunning',

        click: function () {
          DiscardAllDialog.create();
        },

        disabled: function () {
          return this.get('_noWorkspaceWideChanges') || this.get('_saveRunning') || this.get('controller.discardAllRunning');
        }.property('_noWorkspaceWideChanges', '_saveRunning', 'controller.discardAllRunning'),

        disabledClass: function () {
          return this.get('_noWorkspaceWideChanges') || this.get('_saveRunning') ? 'disabled' : '';
        }.property('_noWorkspaceWideChanges', '_saveRunning')
      })
    });
  }
);
