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
									'TYPO3.Neos:Document': [
										'TYPO3.Neos:Shortcut',
										'TYPO3.Neos.NodeTypes:Page',
										'TYPO3.Neos:Page',
										'Neos.Demo:Chapter',
										'TYPO3.NonExisting:NodeType'
									]
								}
							},
							nodeTypes: {
								'TYPO3.Neos:Document': {},
								'TYPO3.Neos:Shortcut': {},
								'TYPO3.Neos.NodeTypes:Page': {},
								'TYPO3.Neos:Page': {},
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
					var documentSubNodeTypes = NodeTypeService.getSubNodeTypes('TYPO3.Neos:Document');

					assert.ok(documentSubNodeTypes['TYPO3.Neos:Shortcut'], 'TYPO3.Neos:Shortcut');
					assert.ok(documentSubNodeTypes['TYPO3.Neos.NodeTypes:Page'], 'TYPO3.Neos.NodeTypes:Page');
					assert.ok(documentSubNodeTypes['TYPO3.Neos:Page'], 'TYPO3.Neos:Page');
					assert.ok(documentSubNodeTypes['Neos.Demo:Chapter'], 'Neos.Demo:Chapter');

					assert.ok(!documentSubNodeTypes['TYPO3.NonExisting:NodeType'], 'TYPO3.NonExisting:NodeType');
				});

				QUnit.load();
				QUnit.start();
			}
		);
	});
})();