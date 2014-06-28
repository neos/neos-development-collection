define(
	[
		'emberjs',
		'Library/jquery-with-dependencies',
		'Shared/Configuration',
		'Shared/NodeTypeService',
		'LibraryExtensions/Mousetrap',
		'text!./InsertNodePanel.html'
	],
	function(
		Ember,
		$,
		Configuration,
		NodeTypeService,
		Mousetrap,
		template
	) {
		return Ember.View.extend({
			template: Ember.Handlebars.compile(template),
			classNames: ['neos-overlay-component'],
			// Callback function after selecting a node type
			insertNode: Ember.K,
			// List of allowed node types (strings); with constraints already evaluated.
			allowedNodeTypes: Ember.K,

			nodeTypeGroups: function() {
				var groupedNodeTypes = {},
					nodeTypeGroups = [];

				$.each(this.get('allowedNodeTypes'), function(index, nodeTypeName) {
					var nodeType = NodeTypeService.getNodeTypeDefinition(nodeTypeName),
						groupName;

					if (!nodeType || !nodeType.ui) {
						return;
					}

					groupName = 'group' in nodeType.ui ? nodeType.ui.group : 'general';

					if (!groupedNodeTypes[groupName]) {
						groupedNodeTypes[groupName] = {
							'name': groupName,
							'label': '',
							'children': []
						};
					}

					groupedNodeTypes[groupName].children.push({
						'nodeType': nodeTypeName,
						'label': nodeType.ui.label,
						'icon': 'icon' in nodeType.ui ? nodeType.ui.icon : 'icon-file'
					});
				});

				Configuration.get('nodeTypes.groups').forEach(function(group) {
					if (groupedNodeTypes[group.name]) {
						groupedNodeTypes[group.name].label = group.label;
						nodeTypeGroups.push(groupedNodeTypes[group.name]);
					}
				});

				return nodeTypeGroups;
			}.property('allowedNodeTypes'),

			init: function() {
				this._super();
				var that = this;
				Mousetrap.bind('esc', function() {
					that.cancel();
				});
			},

			destroy: function() {
				Mousetrap.unbind('esc');
				this._super();
			},

			cancel: function() {
				this.destroy();
			}
		});
	}
);