define(
	[
		'emberjs',
		'Content/Components/AbstractInsertNodePanel',
		'Shared/NodeTypeService',
		'Shared/I18n',
		'Shared/HelpMessage'
	],
	function(
		Ember,
		AbstractInsertNodePanel,
		NodeTypeService,
		I18n,
		HelpMessage
	) {
		return AbstractInsertNodePanel.extend({
			// List of allowed node types (strings); with constraints already evaluated.
			allowedNodeTypes: Ember.required,

			init: function() {
				this._super();
				var nodeTypeGroups = this.get('nodeTypeGroups');
				this.get('allowedNodeTypes').forEach(function(nodeTypeName) {
					var nodeType = NodeTypeService.getNodeTypeDefinition(nodeTypeName);

					if (!nodeType || !nodeType.ui) {
						return;
					}

					var label = I18n.translate(nodeType.ui.label);

					var helpMessage = '';
					if (nodeType.ui.help) {
						helpMessage = HelpMessage(nodeType.ui.help, label);
					}

					var groupName = 'group' in nodeType.ui ? nodeType.ui.group : 'general';
					if (groupName) {
						var group = nodeTypeGroups.findBy('name', groupName);
						if (group) {
							group.get('nodeTypes').pushObject({
								'nodeType': nodeTypeName,
								'label': label,
								'helpMessage': helpMessage,
								'icon': nodeType.ui.icon || 'icon-file',
								'position': nodeType.ui.position
							});
						} else {
							window.console.warn('Node type group "' + groupName + '" not found for node type "' + nodeTypeName + '", defined in "Settings" configuration "Neos.Neos.nodeTypes.groups"');
						}
					}
				});
			}
		});
	}
);
