define(
	[
		'emberjs',
		'Library/jquery-with-dependencies',
		'Shared/ResourceCache',
		'text!./InsertDocumentNodePanel.html'
	],
	function(Ember, $, ResourceCache, template) {
		return Ember.View.extend({
			template: Ember.Handlebars.compile(template),
			classNames: ['neos-overlay-component'],
			nodeTypeGroups: [],
			insertNode: Ember.K,

			init: function() {
				this._super();

				var that = this,
					configuration = window.T3Configuration;
				// TODO: Replace window.T3Configuration with configuration object
				$.when(ResourceCache.get(configuration.NodeTypeSchemaUri + '&superType=TYPO3.Neos:Document')).done(function(dataString) {
					var groupedDocumentNodeTypes = [],
						nodeTypeGroups = [];

					$.each(JSON.parse(dataString), function (nodeType, nodeTypeInfo) {
						var groupName = nodeTypeInfo.ui.group ? nodeTypeInfo.ui.group : 'General';
						if (!groupedDocumentNodeTypes[groupName]) {
							groupedDocumentNodeTypes[groupName] = {
								'name': groupName,
								'children': []
							};
						}
						groupedDocumentNodeTypes[groupName].children.push({
							'nodeType': nodeType,
							'label': nodeTypeInfo.ui.label,
							'icon': nodeTypeInfo.ui.icon ? nodeTypeInfo.ui.icon : 'icon-file'
						});
					});

					configuration.nodeTypeGroups.forEach(function(groupName) {
						if (groupedDocumentNodeTypes[groupName]) {
							nodeTypeGroups.push(groupedDocumentNodeTypes[groupName]);
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
		}).create();
	}
);