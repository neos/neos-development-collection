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
				$.when(ResourceCache.getItem(Configuration.get('NodeTypeSchemaUri') + '&superType=' + superType)).done(function(NodeTypeSchemaString) {
					schema = JSON.parse(NodeTypeSchemaString);
				});

				return schema;
			}

		}).create();
	}
);