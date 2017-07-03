define(
	[
		'emberjs',
		'Library/jquery-with-dependencies',
		'Shared/Configuration',
		'Content/Application',
		'Shared/LocalStorage',
		'Shared/ResourceCache',
		'Shared/Notification',
		'Shared/EventDispatcher',
		'Shared/HttpRestClient'
	],
	function(
		Ember,
		$,
		Configuration,
		ContentModule,
		LocalStorage,
		ResourceCache,
		Notification,
		EventDispatcher,
		HttpRestClient
	) {

		var Workspace;

		/**
		 * Helper class for a Workspace
		 */
		Workspace = Ember.Object.extend({
			name: Ember.required(),
			title: null,
			description: null,
			baseWorkspace: null,
			readOnly: null,
			isUserWorkspace: false,
			isCurrentUserWorkspace: false,
			label: function() {
				var output = (this.get('title') != '' ? this.get('title') : this.get('name'));
				if (this.get('readOnly')) {
					output = '<span class="icon icon-lock"></span> ' + output;
				}
				return output;
			}.property('name', 'title', 'readOnly')
		});

		/**
		 * Controller displaying the target workspace selector
		 */
		return Ember.Controller.extend({
			classNames: 'neos-target-workspace-selector',
			targetWorkspaceLabel: '',

			/**
			 * Retrieve the available workspaces via the REST service and set the local configuration accordingly.
			 */
			_loadConfiguration: function() {
				var that = this;
				HttpRestClient.getResource('neos-service-workspaces').then(function(result) {
					var configuration = {},
					currentUserWorkspaceName = $('#neos-document-metadata').data('neos-context-workspace-name');

					$.each($('.workspaces', result.resource).children('li'), function(key, workspaceSnippet) {
						var workspaceName = $('.workspace-name', workspaceSnippet).text();
						configuration[workspaceName] = {
							name: workspaceName,
							title: $('.workspace-title', workspaceSnippet).text(),
							description: $('.workspace-description', workspaceSnippet).text(),
							baseWorkspace: $('.workspace-baseworkspace-name', workspaceSnippet).text(),
							readOnly: $('.workspace-readonly', workspaceSnippet).length ? true : false,
							isUserWorkspace: (workspaceName.substr(0, 5) === 'user-'),
							isCurrentUserWorkspace: (currentUserWorkspaceName == workspaceName)
						};
					});

					that.set('configuration', configuration);
				});
			},

			/**
			 * Computed property: available workspaces and their properties
			 */
			workspaces: function() {
				var workspaces = [];
				if (!this.get('configuration')) {
					return workspaces;
				}

				$.each(this.get('configuration'), function(workspaceName, workspaceConfiguration) {
					workspaces.push(Workspace.create($.extend(true, {}, workspaceConfiguration, {name: workspaceName})));
				});
				$.each(workspaces, function(index, workspace) {
					if (workspace.baseWorkspace) {
						workspace.baseWorkspace = workspaces.findBy('name', workspace.baseWorkspace);
					}
				});

				return workspaces;
			}.property('configuration'),

			/**
			 * Computed property: available workspaces which are not user workspaces
			 */
			targetWorkspaces: function() {
				var workspaces = [];
				if (!this.get('configuration')) {
					return workspaces;
				}

				$.each(this.get('configuration'), function(workspaceName, workspaceConfiguration) {
					if (!workspaceConfiguration.isUserWorkspace) {
						workspaces.push(Workspace.create($.extend(true, {}, workspaceConfiguration, {name: workspaceName})));
					}
				});

				return workspaces;
			}.property('configuration'),

			/**
			 * Computed property: the current user workspace
			 */
			userWorkspace: function() {
				var workspaces = this.get('workspaces');
				if (workspaces) {
					return workspaces.findBy('isCurrentUserWorkspace', true);
				}
			}.property('workspaces'),

			/**
			 * Computed property: the current target workspace
			 */
			targetWorkspace: function() {
				return this.get('userWorkspace.baseWorkspace');
			}.property('workspaces'),

			/**
			 * Switches user's target workspace to the specified workspace
			 */
			setTargetWorkspace: function(newTargetWorkspaceName) {
				if (this.get('workspaceRebasePending')) {
					return;
				}

				this.set('workspaceRebasePending', true);
				this.set('targetWorkspaceLabel', '');

				var that = this,
					options = {
					data: {
						'workspace[baseWorkspace]' : newTargetWorkspaceName
					}
				};

				HttpRestClient.updateResource('neos-service-workspaces', this.get('userWorkspace.name'), options).then(function(result) {
					that._loadConfiguration();
					ContentModule.reloadPage();
					EventDispatcher.trigger('nodesInvalidated');
					EventDispatcher.trigger('contentChanged');
					that.set('workspaceRebasePending', false);
				}, function(error) {
					console.error('Failed updating base workspace.', error);
				});
			}

		}).create();
	});