define(
	[
		'emberjs',
		'Library/jquery-with-dependencies',
		'Shared/Configuration',
		'Shared/ResourceCache',
		'text!./InsertNodePanel.html'
	],
	function(Ember, $, Configuration, ResourceCache, template) {
		return Ember.View.extend({
			template: Ember.Handlebars.compile(template),
			classNames: ['neos-overlay-component'],
			nodeTypeGroups: [],
			insertNode: Ember.K,

			init: function() {
				this._super();

				var that = this;
				$.when(ResourceCache.getItem(Configuration.NodeTypeSchemaUri + '&superType=' + this.get('baseNodeType'))).done(function(dataString) {
					var groupedNodeTypes = [],
						nodeTypeGroups = [];

					$.each(JSON.parse(dataString), function(nodeType, nodeTypeInfo) {
						var groupName = nodeTypeInfo.ui.group ? nodeTypeInfo.ui.group : 'General';
						if (!groupedNodeTypes[groupName]) {
							groupedNodeTypes[groupName] = {
								'name': groupName,
								'children': []
							};
						}
						groupedNodeTypes[groupName].children.push({
							'nodeType': nodeType,
							'label': nodeTypeInfo.ui.label,
							'icon': nodeTypeInfo.ui.icon ? nodeTypeInfo.ui.icon : 'icon-file'
						});
					});

					Configuration.nodeTypeGroups.forEach(function(groupName) {
						if (groupedNodeTypes[groupName]) {
							nodeTypeGroups.push(groupedNodeTypes[groupName]);
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
			},

			cancel: function() {
				this.destroyElement();
				this.set('insertNode', Ember.K);
			}
		});
	}
);