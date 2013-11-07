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

				return matchType in schema;
			},

			getSubNodeTypes: function(superType) {
				var schema = {};
				// the node type schema has already been loaded here; and we rely on the fact that the "done" part of the promise
				// runs *SYNCHRONOUSLY* because of that. This is somewhat of a hack; and we need to watch out about that; as there
				// are other promise implementations where the "done" closure always runs asynchronously.
				ResourceCache.getItem(Configuration.get('NodeTypeSchemaUri') + '&superType=' + superType).then(function(nodeTypeSchema) {
					schema = nodeTypeSchema;
				});

				return schema;
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

			getCurrentNodeTypeSchema: function() {
				var nodeType = $('#neos-page-metainformation').data('neos-__nodetype'),
					superType = 'TYPO3.Neos:Document';

				return this.getNodeTypeSchema(nodeType, superType);
			}

		}).create();
	}
);