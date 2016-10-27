define(
	[
		'emberjs',
		'Content/Components/AbstractInsertNodePanel',
		'Shared/NodeTypeService',
		'Shared/I18n',
		'Library/marked'
	],
	function(
		Ember,
		AbstractInsertNodePanel,
		NodeTypeService,
		I18n,
		Marked
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

					var helpMessage = '';
					if (nodeType.ui.help && nodeType.ui.help.message) {
						helpMessage = Marked(I18n.translate(nodeType.ui.help.message));
					}

					var groupName = 'group' in nodeType.ui ? nodeType.ui.group : 'general';
					if (groupName) {
						var group = nodeTypeGroups.findBy('name', groupName);
						if (group) {
							group.get('nodeTypes').pushObject({
								'nodeType': nodeTypeName,
								'label': I18n.translate(nodeType.ui.label),
								'helpMessage': helpMessage,
								'icon': nodeType.ui.icon || 'icon-file',
								'position': nodeType.ui.position
							});
						} else {
							window.console.warn('Node type group "' + groupName + '" not found for node type "' + nodeTypeName + '", defined in "Settings" configuration "TYPO3.Neos.nodeTypes.groups"');
						}
					}
				});
			}
		});
	}
);
