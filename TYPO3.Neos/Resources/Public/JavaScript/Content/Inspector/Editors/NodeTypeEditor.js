define(
	[
		'Library/jquery-with-dependencies',
		'./SelectBoxEditor',
		'Shared/Configuration',
		'Shared/NodeTypeService',
	],
	function ($, SelectBoxEditor, Configuration, NodeTypeService) {
		return SelectBoxEditor.extend({
			baseNodeType: 'TYPO3.Neos:Content',

			// todo add support for optgroup when Select2 replace Chosen
			didInsertElement: function () {
				this._super();
				var schema, values = [], sortedNodeTypes = {}, nodeType, subNodeTypeCounter = 0;

				schema = NodeTypeService.getSubNodeTypes(this.get('baseNodeType'));

				for (nodeType in schema) {
					if (schema.hasOwnProperty(nodeType)) {
						values.push({
							value: nodeType,
							label: schema[nodeType].ui.label
						});
						subNodeTypeCounter++;
					}
				}

				if (subNodeTypeCounter > 0) {
					values = values.sort(function (a, b) {
						return a.label.localeCompare(b.label);
					});

					for (var i = 0; i < values.length; i++) {
						sortedNodeTypes[values[i].value] = values[i];
					}

					this.set('values', sortedNodeTypes);
				} else {
					this.set('placeholder', 'Unable to load sub node types of: ' + this.get('baseNodeType'));
				}
			}
		});
	}
);