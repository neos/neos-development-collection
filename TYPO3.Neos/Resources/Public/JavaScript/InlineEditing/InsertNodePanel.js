define(
[
	'emberjs',
	'Library/underscore',
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
	_,
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

		nodeTypeGroups: function() {
			var groups = {},
				node = this.get('_node'),
				position = this.get('_position'),
				types;

			if (position === 'into') {
				types = ContentCommands.getAllowedChildNodeTypesForNode(node);
			} else {
				types = ContentCommands.getAllowedSiblingNodeTypesForNode(node);
			}

			types = _.map(types, function(nodeType) {
				return 'typo3:' + nodeType;
			});

			var contentTypes = NodeTypeService.getSubNodeTypes('TYPO3.Neos:Content');

			_.each(types, function(nodeType) {
				var type = this.get('_node._vieEntity._enclosingCollectionWidget').options.vie.types.get(nodeType);
				if (!type || !type.metadata || type.metadata.abstract === true) {
					return;
				}

				var nodeTypeName = type.id.slice(1, -1).replace(type.metadata.url, '');
				if (!contentTypes.hasOwnProperty(nodeTypeName)) {
					return;
				}

				if (type.metadata.ui && type.metadata.ui.group) {
					if (!groups[type.metadata.ui.group]) {
						groups[type.metadata.ui.group] = {
							name: type.metadata.ui.group,
							label: '',
							nodeTypes: []
						};
					}
					var helpMessage;
					if (type.metadata.ui.help && type.metadata.ui.help.message) {
						helpMessage = type.metadata.ui.help.message;
					} else {
						helpMessage = '';
					}
					groups[type.metadata.ui.group].nodeTypes.push({
						'nodeType': nodeTypeName,
						'label': I18n.translate(type.metadata.ui.label),
						'helpMessage': helpMessage,
						'icon': 'icon' in type.metadata.ui ? type.metadata.ui.icon : 'icon-file',
						'position': type.metadata.ui.position
					});
				}
			}, this);

			// Make the data object an array for usage in #each helper
			var data = [];
			Configuration.get('nodeTypes.groups').forEach(function(group) {
				if (groups[group.name]) {
					groups[group.name].nodeTypes.sort(function(a, b) {
						return (Ember.get(a, 'position') || 9999) - (Ember.get(b, 'position') || 9999);
					});
					groups[group.name].label = I18n.translate(group.label);
					data.push(groups[group.name]);
				}
			});

			return data;
		}.property(),

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
