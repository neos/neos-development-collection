define(
	[
		'emberjs',
		'Library/jquery-with-dependencies',
		'Shared/Configuration',
		'Shared/ResourceCache',
		'LibraryExtensions/Mousetrap',
		'text!./InsertNodePanel.html'
	],
	function(
		Ember,
		$,
		Configuration,
		ResourceCache,
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

				var that = this;
				$.when(ResourceCache.getItem(Configuration.NodeTypeSchemaUri + '&superType=' + this.get('baseNodeType'))).done(function(data) {
					var groupedNodeTypes = [],
						nodeTypeGroups = [];

					$.each(data, function(nodeType, nodeTypeInfo) {
						var groupName = nodeTypeInfo.ui.group ? nodeTypeInfo.ui.group : 'general';
						if (!groupedNodeTypes[groupName]) {
							groupedNodeTypes[groupName] = {
								'name': groupName,
								'label': '',
								'children': []
							};
						}
						groupedNodeTypes[groupName].children.push({
							'nodeType': nodeType,
							'label': nodeTypeInfo.ui.label,
							'icon': nodeTypeInfo.ui.icon ? nodeTypeInfo.ui.icon : 'icon-file'
						});
					});

					Configuration.get('nodeTypes.groups').forEach(function(group) {
						if (groupedNodeTypes[group.name]) {
							groupedNodeTypes[group.name].label = group.label;
							nodeTypeGroups.push(groupedNodeTypes[group.name]);
						}
					});

					that.set('nodeTypeGroups', nodeTypeGroups);
				}).fail(function(xhr, status, error) {
					console.error('Error loading node type schemata.', xhr, status, error);
				});
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