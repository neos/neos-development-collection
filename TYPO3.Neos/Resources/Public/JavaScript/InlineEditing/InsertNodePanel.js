define(
[
	'emberjs',
	'Library/underscore',
	'vie',
	'Content/Application',
	'Shared/Configuration',
	'Content/Model/NodeActions',
	'Shared/NodeTypeService',
	'LibraryExtensions/Mousetrap',
	'text!./InsertNodePanel.html'
],
function(
	Ember,
	_,
	vie,
	ContentModule,
	Configuration,
	NodeActions,
	NodeTypeService,
	Mousetrap,
	template
) {
	return Ember.View.extend({
		template: Ember.Handlebars.compile(template),

		classNames: ['neos-overlay-component'],

		_node: null,
		_index: null,

		content: function() {
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
							children: []
						};
					}
					groups[type.metadata.ui.group].children.push(type.metadata);
				}
			}, this);

			// Make the data object an array for usage in #each helper
			var data = [];
			Configuration.get('nodeTypes.groups').forEach(function(group) {
				if (groups[group.name]) {
					groups[group.name].children.sort(function(a, b) {
						return (Ember.get(a, 'ui.position') || 9999) - (Ember.get(b, 'ui.position') || 9999);
					});
					groups[group.name].label = group.label;
					data.push(groups[group.name]);
				}
			});

			return data;
		}.property(),

		didInsertElement: function() {
			var that = this;
			Mousetrap.bind('esc', function() {
				that.cancel();
			});
		},

		destroyElement: function() {
			Mousetrap.unbind('esc');
			this._super();
		},

		insertNode: function(nodeType) {
			NodeActions.set('_elementIsAddingNewContent', this.get('_node.nodePath'));

			this.get('_node._vieEntity._enclosingCollectionWidget').options.collection.add({
				'@type': 'typo3:' + nodeType
			}, {at: this.get('_index')});

			this.destroy();
		},

		cancel: function() {
			this.destroy();
		}
	});
});
