define(
	[
		'emberjs',
		'Library/jquery-with-dependencies',
		'Shared/Configuration',
		'Shared/ResourceCache'
	],
	function(Ember, $, Configuration, ResourceCache) {

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

				var schema = this.getSubNodeTypes(matchType);

				return matchType in schema;
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
				var nodeType = $('#neos-page-metainformation').attr('typeof').slice(6);
				return this.getNodeTypeDefinition(nodeType);
			}
		}).create();
	}
);