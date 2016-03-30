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
	'LibraryExtensions/Mousetrap'
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
	I18n
) {
	return AbstractInsertNodePanel.extend({
		_node: null,
		_position: null,

		init: function() {
			this._super();
			var groups = {},
				namespace = Configuration.get('TYPO3_NAMESPACE'),
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

			var contentTypes = NodeTypeService.getSubNodeTypes('TYPO3.Neos:Content'),
				nodeTypeGroups = this.get('nodeTypeGroups'),
				vieTypes = this.get('_node._vieEntity._enclosingCollectionWidget').options.vie.types;
			types.forEach(function(nodeType) {
				var type = vieTypes.get(nodeType);
				if (!type || !type.metadata || type.metadata.abstract === true) {
					return;
				}

				var nodeTypeName = type.id.substring(1, type.id.length - 1).replace(namespace, '');
				if (!contentTypes.hasOwnProperty(nodeTypeName)) {
					return;
				}

				var helpMessage = '';
				if (type.metadata.ui.help && type.metadata.ui.help.message) {
					helpMessage = type.metadata.ui.help.message;
				}

				var groupName = type.metadata.ui.group || 'general';
				nodeTypeGroups.findBy('name', groupName).get('nodeTypes').pushObject({
					'nodeType': nodeTypeName,
					'label': I18n.translate(type.metadata.ui.label),
					'helpMessage': helpMessage,
					'icon': 'icon' in type.metadata.ui ? type.metadata.ui.icon : 'icon-file',
					'position': type.metadata.ui.position
				});
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
