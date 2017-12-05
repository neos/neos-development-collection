define(
	[
		'emberjs',
		'Library/jquery-with-dependencies',
		'./SelectBoxEditor',
		'Shared/Configuration',
		'Shared/NodeTypeService',
		'Shared/I18n'
	],
	function (Ember, $, SelectBoxEditor, Configuration, NodeTypeService, I18n) {
		return SelectBoxEditor.extend({
			placeholder: I18n.translate('Neos.Neos:Main:loading', 'Loading') + ' ...',
			baseNodeType: 'Neos.Neos:Content',

			values: function () {
				var nodeTypes = [],
					groupedNodeTypes = [],
					subNodeTypeCounter = 0,
					allowedNodeTypes,
					baseNodeType = this.get('baseNodeType'),
					parentNodeName = this.get('inspector.selectedNode.attributes.__parentNodeName'),
					parentNodeType = this.get('inspector.selectedNode.attributes.__parentNodeType');

				// 1. Figure out the Node Types which are allowed according to the Node Type Constraints
				if (parentNodeName) {
					// parent IS auto created; as only then the ContentElementWrappingService adds this property.
					allowedNodeTypes = NodeTypeService.getAllowedChildNodeTypesForAutocreatedNode(
						this.get('inspector.selectedNode.attributes.__grandparentNodeType'),
						parentNodeName
					);
				} else if(parentNodeType) {
					// parent is NOT auto created
					allowedNodeTypes = NodeTypeService.getAllowedChildNodeTypes(parentNodeType);
				} else {
					allowedNodeTypes = NodeTypeService.getAllowedChildNodeTypes(baseNodeType);
				}

				// 2. Filter for subtypes of BaseNodeType
				allowedNodeTypes = allowedNodeTypes.filter(function(nodeTypeName) {
					return NodeTypeService.isOfType(nodeTypeName, baseNodeType);
				});

				// 3. Pre-process the selector and then fill it
				$.each(allowedNodeTypes, function(index, nodeTypeName) {
					var nodeType = NodeTypeService.getNodeTypeDefinition(nodeTypeName),
						group;

					if (!nodeType || !nodeType.ui) {
						return;
					}

					group = 'group' in nodeType.ui ? nodeType.ui.group : 'general';

					nodeTypes.push({
						'group': group,
						'value': nodeTypeName,
						'label': nodeType.ui.label,
						'icon': 'icon' in nodeType.ui ? nodeType.ui.icon : 'icon-file',
						'position': nodeType.ui.position
					});

					subNodeTypeCounter++;
				});

				if (subNodeTypeCounter > 0) {
					Configuration.get('nodeTypes.groups').forEach(function(group) {
						var nodeTypesInGroup = nodeTypes.filterBy('group', group.name);
						nodeTypesInGroup.sort(function(a, b) {
							return (Ember.get(a, 'position') || 9999) - (Ember.get(b, 'position') || 9999);
						});
						nodeTypesInGroup.forEach(function(nodeType) {
							groupedNodeTypes.push($.extend(nodeType, {group: I18n.translate(group.label)}));
						});
					});
				} else {
					var placeholder = I18n.translate('Neos.Neos:Main:content.inspector.editors.nodeTypeEditor.unableToLoadSubNodeTypes', 'Unable to load sub node types of:') + ' ' + this.get('baseNodeType');
					this.set('placeholder', placeholder);
				}

				return groupedNodeTypes;
			}.property('inspector.selectedNode')
		});
	}
);
