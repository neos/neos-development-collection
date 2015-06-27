define(
	[
		'emberjs',
		'Library/jquery-with-dependencies',
		'Content/Components/AbstractInsertNodePanel',
		'Shared/Configuration',
		'Shared/NodeTypeService',
		'Shared/I18n'
	],
	function(
		Ember,
		$,
		AbstractInsertNodePanel,
		Configuration,
		NodeTypeService,
		I18n
	) {
		return AbstractInsertNodePanel.extend({
			// List of allowed node types (strings); with constraints already evaluated.
			allowedNodeTypes: Ember.required,

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
							'nodeTypes': []
						};
					}
					if (nodeType.ui.help && nodeType.ui.help.message) {
						var helpMessage = nodeType.ui.help.message;
					} else {
						var helpMessage = '';
					}
					groupedNodeTypes[groupName].nodeTypes.push({
						'nodeType': nodeTypeName,
						'label': I18n.translate(nodeType.ui.label),
						'helpMessage': helpMessage,
						'icon': 'icon' in nodeType.ui ? nodeType.ui.icon : 'icon-file',
						'position': nodeType.ui.position
					});
				});

				Configuration.get('nodeTypes.groups').forEach(function(group) {
					if (groupedNodeTypes[group.name]) {
						groupedNodeTypes[group.name].nodeTypes.sort(function(a, b) {
							return (Ember.get(a, 'position') || 9999) - (Ember.get(b, 'position') || 9999);
						});
						groupedNodeTypes[group.name].label = I18n.translate(group.label);
						nodeTypeGroups.push(groupedNodeTypes[group.name]);
					}
				});

				return nodeTypeGroups;
			}.property('allowedNodeTypes')
		});
	}
);