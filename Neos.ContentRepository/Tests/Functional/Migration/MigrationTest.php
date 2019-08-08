<?php
namespace Neos\ContentRepository\Tests\Functional\Migration;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\ContentRepository\Domain\Model\NodeData;
use Neos\ContentRepository\Domain\Model\Workspace;
use Neos\ContentRepository\Domain\Repository\NodeDataRepository;
use Neos\ContentRepository\Domain\Service\NodeTypeManager;
use Neos\ContentRepository\Migration\Domain\Model\Migration;
use Neos\ContentRepository\Migration\Service\NodeMigration;
use Neos\ContentRepository\Tests\Functional\AbstractNodeTest;

class MigrationTest extends AbstractNodeTest
{
    /**
     * @var NodeTypeManager
     */
    protected $nodeTypeManager;

    public function setUp(): void
    {
        parent::setUp();
        $this->nodeTypeManager = $this->objectManager->get(NodeTypeManager::class);
    }

    public function migrationDataprovider(): array
    {
        return [
            'nodeTypeNoSubTypesShouldMatchType' => [
                'migrationConfiguration' => [
                    'up' => [
                        'comments' => '',
                        'migration' => [
                            [
                                'filters' => [
                                    [
                                        'type' => 'NodeType',
                                        'settings' => [
                                            'nodeType' => 'Neos.ContentRepository.Testing:Page',
                                        ],
                                    ],
                                ],
                                'transformations' => [
                                    [
                                        'type' => 'RemoveProperty',
                                        'settings' => [
                                            'property' => 'title',
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                    'down' => [
                        'comments' => '',
                        'migration' => [
                            'filters' => [],
                            'transformations' => [],
                        ],
                    ],
                ],
                'migratedNodeIdentifier' => '25eaba22-b8ed-11e3-a8b5-c82a1441d728',
                'propertyName' => 'title',
                'expectedBefore' => '<h1>Products</h1>',
                'expectedAfter' => '',
            ],
            'nodeTypeNoSubTypesShouldNotMatchSubtype' => [
                'migrationConfiguration' => [
                    'up' => [
                        'comments' => '',
                        'migration' => [
                            [
                                'filters' => [
                                    [
                                        'type' => 'NodeType',
                                        'settings' => [
                                            'nodeType' => 'Neos.ContentRepository.Testing:Document'
                                        ],
                                    ],
                                ],
                                'transformations' => [
                                    [
                                        'type' => 'RemoveProperty',
                                        'settings' => [
                                            'property' => 'title',
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                    'down' => [
                        'comments' => '',
                        'migration' => [
                            'filters' => [],
                            'transformations' => [],
                        ],
                    ],
                ],
                'migratedNodeIdentifier' => '25eaba22-b8ed-11e3-a8b5-c82a1441d728',
                'propertyName' => 'title',
                'expectedBefore' => '<h1>Products</h1>',
                'expectedAfter' => '<h1>Products</h1>',
            ],
            'nodeTypeWithSubTypesShouldMatchType' => [
                'migrationConfiguration' => [
                    'up' => [
                        'comments' => '',
                        'migration' => [
                            [
                                'filters' => [
                                    [
                                        'type' => 'NodeType',
                                        'settings' => [
                                            'nodeType' => 'Neos.ContentRepository.Testing:Document',
                                            'withSubTypes' => true,
                                        ],
                                    ],
                                ],
                                'transformations' => [
                                    [
                                        'type' => 'RemoveProperty',
                                        'settings' => [
                                            'property' => 'title',
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                    'down' => [
                        'comments' => '',
                        'migration' => [
                            'filters' => [],
                            'transformations' => [],
                        ],
                    ],
                ],
                'migratedNodeIdentifier' => '1f14f1f6-6118-5ea8-da9b-fc04ddf62e66',
                'propertyName' => 'title',
                'expectedBefore' => '<h1>Test Foo</h1>',
                'expectedAfter' => '',
            ],
            'nodeTypeWithSubTypesShouldMatchSubType' => [
                'migrationConfiguration' => [
                    'up' => [
                        'comments' => '',
                        'migration' => [
                            [
                                'filters' => [
                                    [
                                        'type' => 'NodeType',
                                        'settings' => [
                                            'nodeType' => 'Neos.ContentRepository.Testing:Document',
                                            'withSubTypes' => true,
                                        ],
                                    ],
                                ],
                                'transformations' => [
                                    [
                                        'type' => 'RemoveProperty',
                                        'settings' => [
                                            'property' => 'title',
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                    'down' => [
                        'comments' => '',
                        'migration' => [
                            'filters' => [],
                            'transformations' => [],
                        ],
                    ],
                ],
                'migratedNodeIdentifier' => 'b9a53b2b-ae94-7730-2ac7-8f7fc1df72c4',
                'propertyName' => 'title',
                'expectedBefore' => '<h1>CMS</h1>',
                'expectedAfter' => '',
            ],
            'nodeTypeWithSubTypesShouldNotMatchType' => [
                'migrationConfiguration' => [
                    'up' => [
                        'comments' => '',
                        'migration' => [
                            [
                                'filters' => [
                                    [
                                        'type' => 'NodeType',
                                        'settings' => [
                                            'nodeType' => 'Neos.ContentRepository.Testing:Document',
                                            'withSubTypes' => true,
                                        ],
                                    ],
                                ],
                                'transformations' => [
                                    [
                                        'type' => 'RemoveProperty',
                                        'settings' => [
                                            'property' => 'title',
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                    'down' => [
                        'comments' => '',
                        'migration' => [
                            'filters' => [],
                            'transformations' => [],
                        ],
                    ],
                ],
                'migratedNodeIdentifier' => 'b1e0e78d-04f3-8fc3-e3d1-e2399f831312',
                'propertyName' => 'title',
                'expectedBefore' => '<h1>Do you love Neos Flow?</h1>',
                'expectedAfter' => '<h1>Do you love Neos Flow?</h1>',
            ],
        ];
    }

    /**
     * @test
     * @dataProvider migrationDataprovider
     * @param $migrationConfiguration
     * @param $migratedNodeIdentifier
     * @param $propertyName
     * @param $expectedBefore
     * @param $expectedAfter
     * @throws \Neos\ContentRepository\Exception\NodeException
     * @throws \Neos\ContentRepository\Exception\NodeTypeNotFoundException
     * @throws \Neos\ContentRepository\Migration\Exception\MigrationException
     * @throws \Neos\Flow\Persistence\Exception\IllegalObjectTypeException
     * @throws \Neos\Flow\Persistence\Exception\UnknownObjectException
     */
    public function migration($migrationConfiguration, $migratedNodeIdentifier, $propertyName, $expectedBefore, $expectedAfter)
    {
        /** @var NodeDataRepository $nodeDataRepository */
        $nodeDataRepository = $this->objectManager->get(NodeDataRepository::class);

        /** @var Workspace $workspace */
        $workspace = $this->workspaceRepository->findByIdentifier('live');
        $documentNode = $workspace->getRootNodeData()->createNodeData('test', $this->nodeTypeManager->getNodeType('Neos.ContentRepository.Testing:Document'), '1f14f1f6-6118-5ea8-da9b-fc04ddf62e66');
        $documentNode->setProperty('title', '<h1>Test Foo</h1>');
        $this->persistenceManager->update($documentNode);
        $this->persistenceManager->persistAll();

        /** @var NodeData $nodeData */
        $nodeData = $nodeDataRepository->findByNodeIdentifier($migratedNodeIdentifier)->getFirst();
        self::assertEquals($expectedBefore, $nodeData->getProperty($propertyName));

        $migration = new Migration('20180409182707', $migrationConfiguration);
        $nodeMigration = new NodeMigration($migration->getUpConfiguration()->getMigration());
        $nodeMigration->execute();
        $this->persistenceManager->persistAll();

        $nodeData = $nodeDataRepository->findByNodeIdentifier($migratedNodeIdentifier)->getFirst();
        self::assertEquals($expectedAfter, $nodeData->getProperty($propertyName));
    }
}
