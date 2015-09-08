define([
	'emberjs',
	'Shared/AbstractModal',
	'Shared/NodeTypeService',
	'Content/Components/Wizard',
	'text!./AbstractInsertNodePanel.html'
], function(
	Ember,
	AbstractModal,
	NodeTypeService,
	Wizard,
	template
) {
	return AbstractModal.extend({
		template: Ember.Handlebars.compile(template),
		nodeTypeGroups: Ember.required,
		insertNode: Ember.required,
		Wizard: Wizard,
		selectedNodeType: null,
		selectNodeType: function (nodeTypeName, icon) {
			this.set('selectedNodeType', nodeTypeName);
			var nodeTypeDefinition = NodeTypeService.getNodeTypeDefinition(nodeTypeName);
			if(!Ember.get(nodeTypeDefinition, 'ui.wizard.properties')) {
				this.insertNode(nodeTypeName, icon);
			}
		},
		deselectNodeType: function () {
			this.set('selectedNodeType', null);
		}
	});
});