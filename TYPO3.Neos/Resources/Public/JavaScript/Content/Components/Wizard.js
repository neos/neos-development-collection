define([
	'emberjs',
	'Shared/NodeTypeService',
	'Content/PropertyEditor',
	'text!./Wizard.html'
], function(
	Ember,
	NodeTypeService,
	PropertyEditor,
	template
) {
	return Ember.View.extend({
		template: Ember.Handlebars.compile(template),
		PropertyEditor: PropertyEditor,

		validationErrors: Ember.Object.create(),
		selectedNodeType: null,
		nodeProperties: {},

		properties: function() {
			var nodeType = NodeTypeService.getNodeTypeDefinition(this.get('selectedNodeType')),
				propertyObject = Ember.get(nodeType, 'ui.wizard.properties');
			if (propertyObject) {
				var propertyArray = [];
				for(var key in propertyObject) {
					if(propertyObject.hasOwnProperty(key) && key !== "toString"){
						propertyObject[key]['key'] = key;
						propertyArray.push(propertyObject[key]);
					}
				}
				return propertyArray;
			} else {
				return [];
			}
		}.property('selectedNodeType'),

		// Reset editor values
		didInsertElement: function() {
			var nodeProperties = this.get('nodeProperties');
			for (var key in nodeProperties) {
				if(nodeProperties.hasOwnProperty(key)){
					this.set('nodeProperties.' + key, null);
				}
			}
			this._super();
		}

	});
});
