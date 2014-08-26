define(
	[
		'emberjs',
		'Library/jquery-with-dependencies',
		'Shared/Configuration',
		'Shared/NodeTypeService',
		'LibraryExtensions/Mousetrap',
		'text!./InsertNodePanel.html'
	],
	function(
		Ember,
		$,
		Configuration,
		NodeTypeService,
		Mousetrap,
		template
	) {
		return Ember.View.extend({
			template: Ember.Handlebars.compile(template),
			classNames: ['neos-overlay-component'],
			nodeTypeGroups: [],
			insertNode: Ember.K,

			init: function() {
				this._super();

				var that = this,
					allowedSubNodeTypes = NodeTypeService.getSubNodeTypes(this.get('baseNodeType')),
					groupedNodeTypes = {},
					nodeTypeGroups = [];

				$.each(allowedSubNodeTypes, function(nodeTypeName, nodeTypeInfo) {
					if (('abstract' in nodeTypeInfo && nodeTypeInfo.abstract === false || !'ui' in nodeTypeInfo)) {
						return;
					}

					var groupName = 'group' in nodeTypeInfo.ui ? nodeTypeInfo.ui.group : 'general';

					if (!groupedNodeTypes[groupName]) {

						groupedNodeTypes[groupName] = {
							'name': groupName,
							'label': '',
							'children': []
						};
					}

					groupedNodeTypes[groupName].children.push({
						'nodeType': nodeTypeName,
						'label': nodeTypeInfo.ui.label,
						'icon': 'icon' in nodeTypeInfo.ui ? nodeTypeInfo.ui.icon : 'icon-file'
					});
				});

				Configuration.get('nodeTypes.groups').forEach(function(group) {
					if (groupedNodeTypes[group.name]) {
						groupedNodeTypes[group.name].label = group.label;
						nodeTypeGroups.push(groupedNodeTypes[group.name]);
					}
				});

				that.set('nodeTypeGroups', nodeTypeGroups);
			},

			createElement: function() {
				this._super();
				this.$().appendTo($('#neos-application'));
				var that = this;
				Mousetrap.bind('esc', function() {
					that.cancel();
				});
			},

			destroyElement: function() {
				Mousetrap.unbind('esc');
				this._super();
			},

			cancel: function() {
				this.destroyElement();
				this.set('insertNode', Ember.K);
			}
		});
	}
);