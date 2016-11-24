define(
[
	'emberjs',
	'vie',
	'Content/Components/AbstractInsertNodePanel',
	'Content/Application',
	'Shared/Configuration',
	'Content/Model/NodeActions',
	'Shared/NodeTypeService',
	'InlineEditing/ContentCommands',
	'Shared/I18n',
	'Shared/HelpMessage'
],
function(
	Ember,
	vie,
	AbstractInsertNodePanel,
	ContentModule,
	Configuration,
	NodeActions,
	NodeTypeService,
	ContentCommands,
	I18n,
	HelpMessage
) {
	return AbstractInsertNodePanel.extend({
		_node: null,
		_position: null,

		init: function() {
			this._super();
			var groups = {},
				node = this.get('_node'),
				position = this.get('_position'),
				types;

			if (position === 'into') {
				types = ContentCommands.getAllowedChildNodeTypesForNode(node);
			} else {
				types = ContentCommands.getAllowedSiblingNodeTypesForNode(node);
			}

			types = types.map(function(nodeType) {
				return 'typo3:' + nodeType;
			});

			var contentTypes = NodeTypeService.getSubNodeTypes('Neos.Neos:Content'),
				nodeTypeGroups = this.get('nodeTypeGroups'),
				vieTypes = this.get('_node._vieEntity._enclosingCollectionWidget').options.vie.types;
			types.forEach(function(nodeType) {
				var type = vieTypes.get(nodeType);
				if (!type || !type.metadata || type.metadata.abstract === true) {
					return;
				}

				var nodeTypeName = type.id.slice(1, -1).replace(type.metadata.url, '');
				if (!contentTypes.hasOwnProperty(nodeTypeName)) {
					return;
				}

				var label = I18n.translate(type.metadata.ui.label);

				var helpMessage = '';
				if (type.metadata.ui.help) {
					helpMessage = HelpMessage(type.metadata.ui.help, label);
				}

				var groupName = 'group' in type.metadata.ui ? type.metadata.ui.group : 'general';
				if (groupName) {
					var group = nodeTypeGroups.findBy('name', groupName);
					if (group) {
						group.get('nodeTypes').pushObject({
							'nodeType': nodeTypeName,
							'label': label,
							'helpMessage': helpMessage,
							'icon': 'icon' in type.metadata.ui ? type.metadata.ui.icon : 'icon-file',
							'position': type.metadata.ui.position
						});
					} else {
						window.console.warn('Node type group "' + groupName + '" not found for node type "' + nodeTypeName + '", defined in "Settings" configuration "Neos.Neos.nodeTypes.groups"');
					}
				}
			});
		},

		/**
		 * @param {string} nodeType
		 */
		insertNode: function(nodeType) {
			var referenceNode = this.get('_node');
			switch (this.get('_position')) {
				case 'before':
					NodeActions.addAbove(nodeType, referenceNode);
				break;
				case 'after':
					NodeActions.addBelow(nodeType, referenceNode);
				break;
				case 'into':
					NodeActions.addInside(nodeType, referenceNode);
				break;
			}
			this.destroy();
		}
	});
});
