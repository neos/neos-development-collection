define(
	[
		'emberjs',
		'Library/underscore',
		'Library/jquery-with-dependencies',
		'Shared/Configuration',
		'Shared/ResourceCache'
	],
	function(Ember, _, $, Configuration, ResourceCache) {

		/**
		 * @singleton
		 */
		return Ember.Object.extend({
			_schema: {},

			init: function() {
				var that = this;
				ResourceCache.getItem(Configuration.get('NodeTypeSchemaUri')).then(function(nodeTypeSchema) {
					that.set('_schema', nodeTypeSchema);
				});
			},

			isOfType: function(node, nodeType) {
				var matchType;

				if (typeof node === 'string') {
					matchType = node;
				} else {
					matchType = node.get('nodeType');
				}

				if (matchType === nodeType) {
					return true;
				}

				var schema = this.getSubNodeTypes(nodeType);

				return schema[matchType] ? true : false;
			},

			getSubNodeTypes: function(superType) {
				var that = this;

				var subNodeTypes = {};

				if (this.get('_schema').inheritanceMap.subTypes[superType]) {
					$.each(this.get('_schema').inheritanceMap.subTypes[superType], function(index, nodeTypeName) {
						if (that.get('_schema').nodeTypes[nodeTypeName]) {
							subNodeTypes[nodeTypeName] = that.get('_schema').nodeTypes[nodeTypeName];
						}
					});
				}

				return subNodeTypes;
			},

			getAllowedChildNodeTypes: function(nodeType) {
				var constraints = this.get('_schema.constraints');

				return $.map(constraints[nodeType].nodeTypes, function(element, index) {
					return index;
				});
			},

			getAllowedChildNodeTypesForAutocreatedNode: function(nodeType, autoCreatedNodeName) {
				var constraints = this.get('_schema.constraints');
				constraints = constraints[nodeType].childNodes[autoCreatedNodeName];

				if (!constraints) {
					// No constraints configuration found, so no allowedChildNodes
					return [];
				}

				return $.map(constraints.nodeTypes, function(element,index) { return index; });
			},

			/**
			 * @param {string} nodeTypeName
			 * @return {object}
			 */
			getNodeTypeDefinition: function(nodeTypeName) {
				return this.get('_schema').nodeTypes[nodeTypeName];
			},

			/**
			 * Get the node type schema for a specific node type.
			 *
			 * @param {string} nodeType
			 * @param {string} superType
			 * @return {object} nodeTypeSchema
			 */
			getNodeTypeSchema: function(nodeType, superType) {
				var nodeTypeSchema = [],
					schema = this.getSubNodeTypes(superType);

				$.each(schema, function(index, data) {
					if (index === nodeType) {
						nodeTypeSchema = data;
					}
				});

				return nodeTypeSchema;
			},

			/**
			 * @return {object}
			 */
			getCurrentNodeTypeSchema: function() {
				var nodeType = $('#neos-document-metadata').attr('typeof').slice(6);
				return this.getNodeTypeDefinition(nodeType);
			}
		}).create();
	}
);
