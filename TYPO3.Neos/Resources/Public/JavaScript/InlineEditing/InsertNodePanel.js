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
	NodeTypeService
) {
	return AbstractInsertNodePanel.extend({
		_node: null,
		_index: null,

		nodeTypeGroups: function() {
			var groups = {},
				namespace = Configuration.get('TYPO3_NAMESPACE'),
				$collectionElement = this.get('_node._vieEntity._enclosingCollectionWidget').element,
				// $collectionElement is currently *ALWAYS* autocreated!!!
				types = NodeTypeService.getAllowedChildNodeTypesForAutocreatedNode($collectionElement.data('node-__parent-node-type'), $collectionElement.data('node-_name'));

			types = _.map(types, function(nodeType) {
				return 'typo3:' + nodeType;
			});

			var contentTypes = NodeTypeService.getSubNodeTypes('TYPO3.Neos:Content');

			_.each(types, function(nodeType) {
				var type = this.get('_node._vieEntity._enclosingCollectionWidget').options.vie.types.get(nodeType);
				if (!type || !type.metadata || type.metadata.abstract === true) {
					return;
				}

				type.metadata.nodeType = type.id.substring(1, type.id.length - 1).replace(namespace, '');
				if (!contentTypes.hasOwnProperty(type.metadata.nodeType)) {
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
					groups[type.metadata.ui.group].nodeTypes.push({
						'nodeType': type.id.substring(1, type.id.length - 1).replace(namespace, ''),
						'label': type.metadata.ui.label,
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
						return (Ember.get(a, 'ui.position') || 9999) - (Ember.get(b, 'ui.position') || 9999);
					});
					groups[group.name].label = group.label;
					data.push(groups[group.name]);
				}
			});

			return data;
		}.property(),

		insertNode: function(nodeType) {
			NodeActions.set('_elementIsAddingNewContent', this.get('_node.nodePath'));

			this.get('_node._vieEntity._enclosingCollectionWidget').options.collection.add({
				'@type': 'typo3:' + nodeType
			}, {at: this.get('_index')});

			this.destroy();
		}
	});
});