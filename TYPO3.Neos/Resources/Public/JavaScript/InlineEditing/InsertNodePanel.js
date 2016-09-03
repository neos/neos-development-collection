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
	'LibraryExtensions/Mousetrap',
	'Content/InputEvents/KeyboardEvents',
	'Shared/Navigator'
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
	Mousetrap,
	KeyboardEvents,
	Navigator
) {
	return AbstractInsertNodePanel.extend({
		_node: null,
		_position: null,
		_preselectedNodeType: null,

		init: function() {
			this._super();
			var that = this;
			var groups = {},
				node = this.get('_node'),
				currentNodeTypeName = node.$element.data('node-_nodeType'),
				position = this.get('_position'),
				types;

			Mousetrap.bind(['mod+shift+a'], function () {
				that.insertSameNode();
				return false;
			});

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

				var nodeTypeName = type.id.slice(1, -1).replace(type.metadata.url, '');
				if (!contentTypes.hasOwnProperty(nodeTypeName)) {
					return;
				}

				var helpMessage = '';
				if (type.metadata.ui.help && type.metadata.ui.help.message) {
					helpMessage = type.metadata.ui.help.message;
				}

				var groupName = 'group' in type.metadata.ui ? type.metadata.ui.group : 'general';
				if (groupName) {
					var group = nodeTypeGroups.findBy('name', groupName);
					if (group) {
						var nodeTypeData = {
							'nodeType': nodeTypeName,
							'label': I18n.translate(type.metadata.ui.label),
							'helpMessage': helpMessage,
							'icon': 'icon' in type.metadata.ui ? type.metadata.ui.icon : 'icon-file',
							'position': type.metadata.ui.position
						};
						if (nodeTypeName === currentNodeTypeName) {
							nodeTypeData.shortcut = Navigator.modKey() + ' + shift + a';
							that.set('_preselectedNodeType', nodeTypeData);
						}
						group.get('nodeTypes').pushObject(nodeTypeData);
					} else {
						window.console.warn('Node type group "' + groupName + '" not found for node type "' + nodeTypeName + '", defined in "Settings" configuration "TYPO3.Neos.nodeTypes.groups"');
					}
				}
			});
		},

		insertSameNode: function() {
			nodeTypeData = this.get('_preselectedNodeType');
			if (nodeTypeData === null) {
				return;
			}
			this.insertNode(nodeTypeData.nodeType);
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
		},

		destroy: function() {
			this._super();
			KeyboardEvents.initializeContentModuleEvents();
		},
	});
});
