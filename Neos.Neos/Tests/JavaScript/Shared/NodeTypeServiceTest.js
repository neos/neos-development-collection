(function() {
	initializeRequirejsConfig('../../../Resources/Public/JavaScript/');

	define('Shared/Configuration', ['Mock/ConfigurationMock'], function(ConfigurationMock) {
		return ConfigurationMock;
	});

	require(['emberjs'], function(Ember) {
		define('Shared/ResourceCache', function() {
			return {
				getItem: function(resourceUri) {
					if (resourceUri === 'NodeTypeSchemaUri') {
						var result = Ember.Deferred.create();
						result.resolve({
							inheritanceMap: {
								subTypes: {
									'Neos.Neos:Document': [
										'Neos.Neos:Shortcut',
										'Neos.Neos.NodeTypes:Page',
										'Neos.Neos:Page',
										'Neos.Demo:Chapter',
										'TYPO3.NonExisting:NodeType'
									]
								}
							},
							nodeTypes: {
								'Neos.Neos:Document': {},
								'Neos.Neos:Shortcut': {},
								'Neos.Neos.NodeTypes:Page': {},
								'Neos.Neos:Page': {},
								'Neos.Demo:Chapter': {}
							}
						});
						return result;
					}
				}
			};
		});

		require(
			[
				'Shared/NodeTypeService'
			],
			function(NodeTypeService) {

				QUnit.test('getSubNodeTypes ', function(assert) {
					var documentSubNodeTypes = NodeTypeService.getSubNodeTypes('Neos.Neos:Document');

					assert.ok(documentSubNodeTypes['Neos.Neos:Shortcut'], 'Neos.Neos:Shortcut');
					assert.ok(documentSubNodeTypes['Neos.Neos.NodeTypes:Page'], 'Neos.Neos.NodeTypes:Page');
					assert.ok(documentSubNodeTypes['Neos.Neos:Page'], 'Neos.Neos:Page');
					assert.ok(documentSubNodeTypes['Neos.Demo:Chapter'], 'Neos.Demo:Chapter');

					assert.ok(!documentSubNodeTypes['TYPO3.NonExisting:NodeType'], 'TYPO3.NonExisting:NodeType');
				});

				QUnit.load();
				QUnit.start();
			}
		);
	});
})();
