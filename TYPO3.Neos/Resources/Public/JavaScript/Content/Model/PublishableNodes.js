/**
 * Publishable nodes
 *
 * Contains the currently publishable (proxy) nodes.
 */
define(
[
	'emberjs',
	'Library/jquery-with-dependencies',
	'vie/instance',
	'vie/entity',
	'Shared/EventDispatcher',
	'Shared/ResourceCache',
	'Shared/Configuration',
	'Shared/Notification',
	'Shared/Endpoint/WorkspaceEndpoint'
], function(
	Ember,
	$,
	vie,
	EntityWrapper,
	EventDispatcher,
	ResourceCache,
	Configuration,
	Notification,
	WorkspaceEndpoint
) {
	return Ember.Object.extend({
		publishableEntitySubjects: [],
		workspaceWidePublishableEntitySubjects: [],

		noChanges: function() {
			return this.get('publishableEntitySubjects').length === 0;
		}.property('publishableEntitySubjects.length'),

		numberOfPublishableNodes: function() {
			return this.get('publishableEntitySubjects').length;
		}.property('publishableEntitySubjects.length'),

		noWorkspaceWideChanges: function() {
			return this.get('workspaceWidePublishableEntitySubjects').length === 0;
		}.property('workspaceWidePublishableEntitySubjects.length'),

		numberOfWorkspaceWidePublishableNodes: function() {
			return this.get('workspaceWidePublishableEntitySubjects').length;
		}.property('workspaceWidePublishableEntitySubjects.length'),

		initialize: function() {
			vie.entities.on('change', this._updatePublishableEntities, this);
			this._updatePublishableEntities();

			var that = this;
			EventDispatcher.on('nodeDeleted', function(parentNode) {
				that.getWorkspaceWideUnpublishedNodes();
			});

			EventDispatcher.on('nodeMoved', function(node) {
				that.getWorkspaceWideUnpublishedNodes();
			});
		},

		/**
		 * @return {void}
		 */
		_updatePublishableEntities: function() {
			var publishableEntitySubjects = [];
			var pageNodeContextPath = $('#neos-page-metainformation').attr('about'),
				pageNodePath = pageNodeContextPath.substr(0, pageNodeContextPath.lastIndexOf('@'));

			vie.entities.forEach(function(entity) {
				if (this._isEntityPublishable(entity)) {
					var nodePath = entity.id.substr(1, entity.id.lastIndexOf('@') - 1);

					if (!this.get('workspaceWidePublishableEntitySubjects').findBy('nodePath', nodePath)) {
						this.get('workspaceWidePublishableEntitySubjects').addObject({
							nodePath: nodePath,
							pageNodePath: pageNodePath
						});
					}
					publishableEntitySubjects.push(entity.id);
				}
			}, this);

			this.set('publishableEntitySubjects', publishableEntitySubjects);
		},

		/**
		 * Check whether the entity is publishable or not. Currently, everything
		 * which is not in the live workspace is publishable.
		 *
		 * @param {object} entity
		 * @return {boolean}
		 */
		_isEntityPublishable: function(entity) {
			var attributes = EntityWrapper.extractAttributesFromVieEntity(entity);
			return attributes.__workspacename !== 'live';
		},

		/**
		 * Publish all blocks which are unsaved *and* on current page.
		 *
		 * @param {mixed} autoPublish
		 * @return {void}
		 */
		publishChanges: function(autoPublish) {
			var that = this,
				targetWorkspace = 'live',
				transformFn = function(subject) {
					var entity = vie.entities.get(subject);
					return [entity.fromReference(subject), targetWorkspace];
				},
				numberOfUnsavedRecords = this.get('publishableEntitySubjects.length');

			this.get('publishableEntitySubjects').forEach(function(element) {
				// Force copy of array
				var args = transformFn(element).slice();

				WorkspaceEndpoint.publishNode(args[0], targetWorkspace).then(
					function() {
						that._removeNodeFromPublishableEntitySubjects(element, 'live');
						numberOfUnsavedRecords--;
						if (numberOfUnsavedRecords <= 0) {
							if (autoPublish !== true) {
								var nodeType, title = $('#neos-page-metainformation').attr('data-neos-title');
								// TODO: The fetching of the current node type should be handled using the NodeTypeService
								// which at the moment causes issues with promises which still have to be optimized
								ResourceCache.getItem(Configuration.get('NodeTypeSchemaUri') + '&superType=TYPO3.Neos:Document').then(
									function(schema) {
										nodeType = schema[$('#neos-page-metainformation').attr('data-neos-__nodetype')].ui.label;
										Notification.ok('Published changes for ' + nodeType + ' "' + title + '"');
									}
								);
							}
						}
					}
				);
			});
		},

		/**
		 * @param {string} subject
		 * @param {string} workspaceOverride
		 * @return {void}
		 */
		_removeNodeFromPublishableEntitySubjects: function(subject, workspaceOverride) {
			var that = this,
				entity = vie.entities.get(subject);
			if (workspaceOverride) {
				entity.set('typo3:__workspacename', workspaceOverride);
			}

			var nodePath = entity.id.substr(1, entity.id.lastIndexOf('@') - 1),
				node = that.get('workspaceWidePublishableEntitySubjects').findBy('nodePath', nodePath);
			if (node) {
				that.get('workspaceWidePublishableEntitySubjects').removeObject(node);
			}
		},

		/**
		 * Discard all blocks which are unsaved *and* on current page.
		 *
		 * @return {void}
		 */
		discardChanges: function() {
			var that = this,
				transformFn = function(subject) {
					var entity = vie.entities.get(subject);
					return [entity.fromReference(subject)];
				},
				numberOfUnsavedRecords = this.get('publishableEntitySubjects.length');

			this.get('publishableEntitySubjects').forEach(function(element) {
				// Force copy of array
				var args = transformFn(element).slice();
				WorkspaceEndpoint.discardNode(args[0]).then(
					function() {
						that._removeNodeFromPublishableEntitySubjects(element);
						numberOfUnsavedRecords--;
						if (numberOfUnsavedRecords <= 0) {
							require(
								{context: 'neos'},
								[
									'Content/Application'
								],
								function(ContentModule) {
									ContentModule.reloadPage();
									ContentModule.one('pageLoaded', function() {
										Ember.run.next(function() {
											EventDispatcher.trigger('nodesInvalidated');
											EventDispatcher.trigger('contentChanged');
										});
									});
								}
							);
							var nodeType, title = $('#neos-page-metainformation').attr('data-neos-title');

							// TODO: The fetching of the current node type should be handled using the NodeTypeService
							// which at the moment causes issues with promises which still have to be optimized
							ResourceCache.getItem(Configuration.get('NodeTypeSchemaUri') + '&superType=TYPO3.Neos:Document').then(
								function(schema) {
									nodeType = schema[$('#neos-page-metainformation').attr('data-neos-__nodetype')].ui.label;
									Notification.ok('Discarded changes for ' + nodeType + ' "' + title + '"');
								}
							);
						}
					}
				);
			});
		},

		/**
		 * Publishes everything inside the current workspace.
		 *
		 * @return {void}
		 */
		publishAll: function() {
			var workspaceName = $('#neos-page-metainformation').attr('data-context-__workspacename'),
				publishableEntities = this.get('publishableEntitySubjects'),
				that = this;

			WorkspaceEndpoint.publishAll(workspaceName).then(
				function() {
					$.each(publishableEntities, function(index, element) {
						vie.entities.get(element).set('typo3:__workspacename', 'live');
					});

					that.getWorkspaceWideUnpublishedNodes();
					Notification.ok('Published all changes.');
				},
				function(error) {
					Notification.error('Unexpected error while publishing all changes: ' + JSON.stringify(error));
				}
			);
		},

		/**
		 * Discards everything inside the current workspace.
		 *
		 * @return {void}
		 */
		discardAll: function() {
			var workspaceName = $('#neos-page-metainformation').attr('data-context-__workspacename'),
				that = this;
			WorkspaceEndpoint.discardAll(workspaceName).then(
				function() {
					require(
						{context: 'neos'},
						[
							'Content/Application'
						],
						function(ContentModule) {
							ContentModule.reloadPage();
							ContentModule.one('pageLoaded', function() {
								Ember.run.next(function() {
									EventDispatcher.trigger('nodesInvalidated');
									EventDispatcher.trigger('contentChanged');
								});
							});
						}
					);
					that.getWorkspaceWideUnpublishedNodes();
					Notification.ok('Discarded all changes.');
				},
				function(error) {
					Notification.error('Unexpected error while discarding all changes: ' + JSON.stringify(error));
				}
			);
		},

		/**
		 * Get all unpublished nodes inside the current workspace.
		 *
		 * @return {void}
		 */
		getWorkspaceWideUnpublishedNodes: function() {
			var workspaceName = $('#neos-page-metainformation').attr('data-context-__workspacename'),
				that = this;

			WorkspaceEndpoint.getWorkspaceWideUnpublishedNodes(workspaceName).then(
				function(result) {
					that.set('workspaceWidePublishableEntitySubjects', result.data);
				}
			);
		}
	}).create();
});
