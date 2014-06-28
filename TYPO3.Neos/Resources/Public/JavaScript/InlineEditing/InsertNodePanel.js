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
		_entity: null,
		_index: null,

		content: function() {
			var groups = {},
				namespace = Configuration.get('TYPO3_NAMESPACE'),
				$collectionElement = this.get('_entity._enclosingCollectionWidget').element,
				// $collectionElement is currently *ALWAYS* autocreated!!!
				types = NodeTypeService.getAllowedChildNodeTypesForAutocreatedNode($collectionElement.attr('data-neos-_parentnodetype'), $collectionElement.attr('data-neos-_nodename'));

			types = _.map(types, function(nodeType) {
				return 'typo3:' + nodeType;
			});

			_.each(types, function(nodeType) {
				var type = this.get('_entity._enclosingCollectionWidget').options.vie.types.get(nodeType);
				if (!type || !type.metadata || type.metadata.abstract === true) {
					return;
				}

				type.metadata.nodeType = type.id.substring(1, type.id.length - 1).replace(namespace, '');

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
			NodeActions.set('_elementIsAddingNewContent', this.get('_entity').getSubjectUri());

			this.get('_entity._enclosingCollectionWidget').options.collection.add({
				'@type': 'typo3:' + nodeType
			}, {at: this.get('_index')});

			this.destroy();
		},

		cancel: function() {
			this.destroy();
		}
	});
});