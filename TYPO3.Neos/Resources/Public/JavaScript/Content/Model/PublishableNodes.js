/**
 * Publishable nodes
 *
 * Contains the currently publishable (proxy) nodes.
 */
define(
  [
    'emberjs',
    'Library/jquery-with-dependencies',
    'vie',
    'Content/Model/Node',
    'Content/Model/NodeSelection',
    'Content/Components/TargetWorkspaceController',
    'Shared/EventDispatcher',
    'Shared/NodeTypeService',
    'Shared/ResourceCache',
    'Shared/Configuration',
    'Shared/Notification',
    'Shared/Endpoint/WorkspaceEndpoint',
    'Content/Application',
    'Shared/I18n'
  ], function (Ember,
               $,
               vie,
               EntityWrapper,
               NodeSelection,
               TargetWorkspaceController,
               EventDispatcher,
               NodeTypeService,
               ResourceCache,
               Configuration,
               Notification,
               WorkspaceEndpoint,
               ContentModule,
               I18n) {
    return Ember.Object.extend({
      publishableEntitySubjects: [],
      workspaceWidePublishableEntitySubjects: [],
      publishRunning: false,
      publishAllRunning: false,
      discardRunning: false,
      discardAllRunning: false,
      targetWorkspaceController: TargetWorkspaceController,

      noChanges: function () {
        return this.get('publishableEntitySubjects').length === 0;
      }.property('publishableEntitySubjects.length'),

      numberOfPublishableNodes: function () {
        return this.get('publishableEntitySubjects').length;
      }.property('publishableEntitySubjects.length'),

      noWorkspaceWideChanges: function () {
        return this.get('workspaceWidePublishableEntitySubjects').length === 0;
      }.property('workspaceWidePublishableEntitySubjects.length'),

      numberOfWorkspaceWidePublishableNodes: function () {
        return this.get('workspaceWidePublishableEntitySubjects').length;
      }.property('workspaceWidePublishableEntitySubjects.length'),

      init: function () {
        vie.entities.on('change', this._updatePublishableEntities, this);

        EventDispatcher
          .on('nodeCreated', this, 'getWorkspaceWideUnpublishedNodes')
          .on('nodeDeleted', this, 'getWorkspaceWideUnpublishedNodes')
          .on('nodeUpdated', this, 'getWorkspaceWideUnpublishedNodes')
          .on('nodeMoved', this, 'getWorkspaceWideUnpublishedNodes')
          .on('contentChanged', this, '_updatePublishableEntities')
          .on('nodeUpdated', this, '_updatePublishableEntities')
          .on('nodesUpdated', this, '_updatePublishableEntities');
        ContentModule.on('pageLoaded', this, '_updatePublishableEntities');
      },

      /**
       * @return {void}
       */
      _updatePublishableEntities: function () {
        var publishableEntitySubjects = [],
          documentNodeContextPath = $('#neos-document-metadata').attr('about');

        vie.entities.forEach(function (entity) {
          if (this._isEntityPublishable(entity)) {
            var entitySubject = entity.id,
              nodeContextPath = entitySubject.slice(1, entitySubject.length - 1);
            if (!this.get('workspaceWidePublishableEntitySubjects').findBy('nodeContextPath', nodeContextPath)) {
              this.get('workspaceWidePublishableEntitySubjects').addObject({
                nodeContextPath: nodeContextPath,
                documentNodeContextPath: documentNodeContextPath
              });
            }
            publishableEntitySubjects.push(entitySubject);
          }
        }, this);
        this.set('publishableEntitySubjects', publishableEntitySubjects);
      }.observes('targetWorkspaceController.userWorkspace'),

      /**
       * Check whether the entity is publishable or not. Everything which is in the user workspace is publishable.
       *
       * @param {object} entity
       * @return {boolean}
       */
      _isEntityPublishable: function (entity) {
        var attributes = EntityWrapper.extractAttributesFromVieEntity(entity);
        return attributes.__workspaceName && attributes.__workspaceName === this.get('targetWorkspaceController.userWorkspace.name');
      },

      /**
       * Publish all blocks which are unsaved *and* on current page.
       *
       * @param {bool} autoPublish
       * @return {void}
       */
      publishChanges: function (autoPublish) {
        var that = this,
          targetWorkspaceName = this.get('targetWorkspaceController.targetWorkspace.name'),
          entitySubjects = this.get('publishableEntitySubjects'),
          nodes = entitySubjects.map(function (subject) {
            return vie.entities.get(subject).fromReference(subject);
          });

        if (nodes.length > 0) {
          that.set('publishRunning', true);
          WorkspaceEndpoint.publishNodes(nodes, targetWorkspaceName).then(
            function () {
              entitySubjects.forEach(function (subject) {
                that._removeNodeFromPublishableEntitySubjects(subject, targetWorkspaceName);
              });
              that._updatePublishableEntities();
              that.getWorkspaceWideUnpublishedNodes();

              if (autoPublish !== true) {
                var documentMetadata = $('#neos-document-metadata'),
                  nodeType = documentMetadata.data('node-_node-type'),
                  page = NodeSelection.getNode(documentMetadata.attr('about')),
                  pageTitle = (typeof page !== 'undefined' ? page.getAttribute('title') : null) || '',
                  nodeTypeConfiguration = NodeTypeService.getNodeTypeDefinition(nodeType);
                Notification.ok('Published changes for ' + I18n.translate(nodeTypeConfiguration.ui.label) + ' "' + $('<a />').html(pageTitle).text() + '"');
              }
              that.set('publishRunning', false);
            },
            function (error) {
              that.set('publishRunning', false);
              Notification.error('Unexpected error while publishing changes: ' + JSON.stringify(error));
            }
          ).fail(function(error) {
              Notification.error('An error occurred.');
              console.error('An error occurred:', error);
          });
        }
      },

      /**
       * @param {string} entitySubject
       * @param {string} workspaceOverride
       * @return {void}
       */
      _removeNodeFromPublishableEntitySubjects: function (entitySubject, workspaceOverride) {
        var that = this,
          entity = vie.entities.get(entitySubject);
        if (workspaceOverride) {
          // This is done silently to avoid VIE overriding the existing inline values
          entity.set('typo3:__workspaceName', workspaceOverride, {silent: true});
        }

        var nodeContextPath = entitySubject.slice(1, entitySubject.length - 1),
          node = that.get('workspaceWidePublishableEntitySubjects').findBy('nodeContextPath', nodeContextPath);
        if (node) {
          that.get('workspaceWidePublishableEntitySubjects').removeObject(node);
        }
      },

      /**
       * Discard all blocks which are unsaved *and* on current page.
       *
       * @return {void}
       */
      discardChanges: function () {
        var that = this,
          entitySubjects = this.get('publishableEntitySubjects'),
          nodes = entitySubjects.map(function (subject) {
            return vie.entities.get(subject).fromReference(subject);
          });

        if (nodes.length > 0) {
          that.set('discardRunning', true);
          WorkspaceEndpoint.discardNodes(nodes).then(
            function () {
              entitySubjects.forEach(function (subject) {
                that._removeNodeFromPublishableEntitySubjects(subject);
              });
              that.set('publishableEntitySubjects', []);
              that.getWorkspaceWideUnpublishedNodes();
              require(
                {context: 'neos'},
                [
                  'Content/Application'
                ],
                function (ContentModule) {
                  ContentModule.reloadPage();
                  ContentModule.one('pageLoaded', function () {
                    Ember.run.next(function () {
                      EventDispatcher.trigger('nodesInvalidated');
                      EventDispatcher.trigger('contentChanged');
                    });
                  });
                }
              );
              var documentMetadata = $('#neos-document-metadata'),
                nodeType = documentMetadata.data('node-_node-type'),
                page = NodeSelection.getNode(documentMetadata.attr('about')),
                pageTitle = (typeof page !== 'undefined' ? page.getAttribute('title') : null) || '',
                nodeTypeConfiguration = NodeTypeService.getNodeTypeDefinition(nodeType);
              Notification.ok('Discarded changes for ' + I18n.translate(nodeTypeConfiguration.ui.label) + ' "' + $('<a />').html(pageTitle).text() + '"');
              that.set('discardRunning', false);
            },
            function (error) {
              that.set('discardRunning', false);
              Notification.error('Unexpected error while discarding changes: ' + JSON.stringify(error));
            }
          );
        }
      },

      /**
       * Publishes everything inside the current workspace.
       *
       * @return {void}
       */
      publishAll: function () {
        var that = this,
          entitySubjects = this.get('publishableEntitySubjects'),
          sourceWorkspaceName = this.get('targetWorkspaceController.userWorkspace.name'),
          targetWorkspaceName = this.get('targetWorkspaceController.targetWorkspace.name');

        that.set('publishAllRunning', true);
        WorkspaceEndpoint.publishAll(sourceWorkspaceName, targetWorkspaceName).then(
          function () {
            entitySubjects.forEach(function (subject) {
              that._removeNodeFromPublishableEntitySubjects(subject, targetWorkspaceName);
            });
            that._updatePublishableEntities();
            that.set('workspaceWidePublishableEntitySubjects', []);
            Notification.ok('Published all changes.');
            that.set('publishAllRunning', false);
          },
          function (error) {
            that.set('publishAllRunning', false);
            Notification.error('Unexpected error while publishing all changes: ' + JSON.stringify(error));
          }
        );
      },

      /**
       * Discards everything inside the current workspace.
       *
       * @return {void}
       */
      discardAll: function () {
        var that = this,
          workspaceName = this.get('targetWorkspaceController.userWorkspace.name');

        that.set('discardAllRunning', true);
        WorkspaceEndpoint.discardAll(workspaceName).then(
          function () {
            that.set('publishableEntitySubjects', []);
            that.set('workspaceWidePublishableEntitySubjects', []);
            require(
              {context: 'neos'},
              [
                'Content/Application'
              ],
              function (ContentModule) {
                ContentModule.reloadPage();
                ContentModule.one('pageLoaded', function () {
                  Ember.run.next(function () {
                    EventDispatcher.trigger('nodesInvalidated');
                    EventDispatcher.trigger('contentChanged');
                  });
                });
              }
            );
            Notification.ok('Discarded all changes.');
            that.set('discardAllRunning', false);
          },
          function (error) {
            that.set('discardAllRunning', false);
            Notification.error('Unexpected error while discarding all changes: ' + JSON.stringify(error));
          }
        );
      },

      /**
       * Get all unpublished nodes inside the current workspace.
       *
       * @return {void}
       */
      getWorkspaceWideUnpublishedNodes: function () {
        var workspaceName = $('#neos-document-metadata').data('neos-context-workspace-name'),
          that = this;

        WorkspaceEndpoint.getWorkspaceWideUnpublishedNodes(workspaceName).then(
          function (result) {
            that.set('workspaceWidePublishableEntitySubjects', result.data);
          }
        );
      }
    }).create();
  });
