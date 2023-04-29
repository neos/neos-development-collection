<?php
namespace Neos\ContentRepository\Tests\Functional\Domain\Repository;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Tests\Functional\Persistence\Fixtures;
use Neos\Flow\Tests\Functional\Persistence\Fixtures\Image;
use Neos\Flow\Tests\FunctionalTestCase;
use Neos\ContentRepository\Domain\Model\Workspace;
use Neos\ContentRepository\Domain\Repository\WorkspaceRepository;
use Neos\ContentRepository\Domain\Repository\NodeDataRepository;
use Neos\ContentRepository\Domain\Service\Context;
use Neos\ContentRepository\Domain\Service\ContextFactoryInterface;
use Neos\ContentRepository\Domain\Service\NodeTypeManager;

/**
 * Functional test case.
 */
class NodeDataRepositoryTest extends FunctionalTestCase
{
    /**
     * @var Context
     */
    protected $context;

    /**
     * @var boolean
     */
    protected static $testablePersistenceEnabled = true;

    /**
     * @var ContextFactoryInterface
     */
    protected $contextFactory;

    /**
     * @var NodeTypeManager
     */
    protected $nodeTypeManager;

    /**
     * @var NodeDataRepository
     */
    protected $nodeDataRepository;

    /**
     * @var WorkspaceRepository
     */
    protected $workspaceRepository;

    /**
     * @var Workspace
     */
    protected $liveWorkspace;

    /**
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->nodeTypeManager = $this->objectManager->get(NodeTypeManager::class);
        $this->liveWorkspace = new Workspace('live');
        $this->workspaceRepository = $this->objectManager->get(WorkspaceRepository::class);
        $this->workspaceRepository->add($this->liveWorkspace);
        $this->persistenceManager->persistAll();
        $this->contextFactory = $this->objectManager->get(ContextFactoryInterface::class);
        $this->context = $this->contextFactory->create(['workspaceName' => 'live']);
        $this->nodeDataRepository = $this->objectManager->get(NodeDataRepository::class);
    }

    /**
     * @return void
     */
    public function tearDown(): void
    {
        parent::tearDown();
        $this->inject($this->contextFactory, 'contextInstances', []);
    }

    /**
     * @test
     */
    public function findNodesByRelatedEntitiesFindsExistingNodeWithMatchingEntityProperty()
    {
        $rootNode = $this->context->getRootNode();
        $newNode = $rootNode->createNode('test', $this->nodeTypeManager->getNodeType('Neos.ContentRepository.Testing:NodeTypeWithEntities'));

        $testImage = new Image();
        $this->persistenceManager->add($testImage);

        $newNode->setProperty('image', $testImage);

        $this->persistenceManager->persistAll();

        $relationMap = [
            Fixtures\Image::class => [$this->persistenceManager->getIdentifierByObject($testImage)]
        ];

        $result = $this->nodeDataRepository->findNodesByRelatedEntities($relationMap);

        self::assertCount(1, $result);
    }

    /**
     * @test
     */
    public function findNodesByRelatedEntitiesFindsExistingNodeWithMatchingAssetLink()
    {
        $rootNode = $this->context->getRootNode();
        $newNode = $rootNode->createNode('test', $this->nodeTypeManager->getNodeType('Neos.ContentRepository.Testing:Text'));

        $testImage = new Image();
        $this->persistenceManager->add($testImage);
        $testImageIdentifier = $this->persistenceManager->getIdentifierByObject($testImage);

        $newNode->setProperty('text', sprintf('a linked <a href="asset://%s">image</a>', $testImageIdentifier));

        $this->persistenceManager->persistAll();

        $relationMap = [
            Fixtures\Image::class => [$testImageIdentifier]
        ];

        $result = $this->nodeDataRepository->findNodesByRelatedEntities($relationMap);

        self::assertCount(1, $result);
    }

    protected function setUpNodes()
    {
        $rootNode = $this->context->getRootNode();
        $rootNode->createNode('test-123')->createNode('below-123')->setProperty('testProperty', 'Vibiemme');
        $rootNode->getNode('test-123')->createNode('also-below-123')->setProperty('testProperty', 'Vibiemme');
        $rootNode->createNode('test-123456')->createNode('below-123456')->setProperty('testProperty', 'Vibiemme');
        $rootNode->getNode('test-123456')->createNode('also-below-123456')->setProperty('testProperty', 'Vibiemme');
        $this->persistenceManager->persistAll();
    }

    /**
     * Tests findByProperties, see https://jira.neos.io/browse/NEOS-1849
     *
     * @test
     */
    public function findByPropertiesLimitsToStartingPointCorrectly()
    {
        $this->setUpNodes();

        $workspace = $this->context->getWorkspace();
        $foundNodes = $this->nodeDataRepository->findByProperties('Vibiemme', 'unstructured', $workspace, [], '/test-123');

        self::assertCount(2, $foundNodes);
    }

    /**
     * Tests findByProperties, see https://jira.neos.io/browse/NEOS-1849
     *
     * @test
     */
    public function findByPropertiesLimitsToRootNodeCorrectly()
    {
        $this->setUpNodes();

        $workspace = $this->context->getWorkspace();
        $foundNodes = $this->nodeDataRepository->findByProperties('Vibiemme', 'unstructured', $workspace, [], '/');

        self::assertCount(4, $foundNodes);
    }

    /**
     * Tests addParentPathConstraintToQueryBuilder, see https://jira.neos.io/browse/NEOS-1849
     *
     * @test
     */
    public function findByParentAndNodeTypeLimitsToStartingPointCorrectly()
    {
        $this->setUpNodes();

        $workspace = $this->context->getWorkspace();
        $foundNodes = $this->nodeDataRepository->findByParentAndNodeType('/test-123', 'unstructured', $workspace, [], false, true);

        self::assertCount(2, $foundNodes);
    }

    /**
     * Tests addParentPathConstraintToQueryBuilder, see https://jira.neos.io/browse/NEOS-1849
     *
     * @test
     */
    public function findByParentAndNodeTypeLimitsToRootNodeCorrectly()
    {
        $this->setUpNodes();

        $workspace = $this->context->getWorkspace();
        $foundNodes = $this->nodeDataRepository->findByParentAndNodeType('/', 'unstructured', $workspace, [], false, true);

        self::assertCount(6, $foundNodes);
    }

    /**
     * Tests addPathConstraintToQueryBuilder, see https://jira.neos.io/browse/NEOS-1849
     *
     * @test
     */
    public function findByPathWithoutReduceLimitsToStartingPointCorrectly()
    {
        $this->setUpNodes();

        $workspace = $this->context->getWorkspace();
        $foundNodes = $this->nodeDataRepository->findByPathWithoutReduce('/test-123', $workspace, false, true);

        self::assertCount(3, $foundNodes);
    }

    /**
     * Tests addPathConstraintToQueryBuilder, see https://jira.neos.io/browse/NEOS-1849
     *
     * @test
     */
    public function findByPathWithoutReduceLimitsToRootNodeCorrectly()
    {
        $this->setUpNodes();

        $workspace = $this->context->getWorkspace();
        $foundNodes = $this->nodeDataRepository->findByPathWithoutReduce('/', $workspace, false, true);

        self::assertCount(7, $foundNodes);
    }

    /**
     * @test
     */
    public function findNodeByPropertySearch()
    {
        $this->createNodesForNodeSearchTest();

        $result = $this->nodeDataRepository->findByProperties('simpleTestValue', 'Neos.ContentRepository.Testing:NodeType', $this->liveWorkspace, $this->context->getDimensions());
        self::assertCount(2, $result);
        $this->assertResultConsistsOfNodes($result, ['test-node-1', 'test-node-2']);
    }

    /**
     * @test
     */
    public function findNodesByPropertyKeyAndValue()
    {
        $this->createNodesForNodeSearchTest();

        $result = $this->nodeDataRepository->findByProperties(['test2' => 'simpleTestValue'], 'Neos.ContentRepository.Testing:NodeType', $this->liveWorkspace, $this->context->getDimensions());
        self::assertCount(1, $result);
        self::assertEquals('test-node-2', array_shift($result)->getName());
    }

    /**
     * @throws \Neos\ContentRepository\Exception\NodeTypeNotFoundException
     */
    protected function createNodesForNodeSearchTest()
    {
        $rootNode = $this->context->getRootNode();

        $newNode1 = $rootNode->createNode('test-node-1', $this->nodeTypeManager->getNodeType('Neos.ContentRepository.Testing:NodeType'));
        $newNode1->setProperty('test1', 'simpleTestValue');

        $newNode2 = $rootNode->createNode('test-node-2', $this->nodeTypeManager->getNodeType('Neos.ContentRepository.Testing:NodeType'));
        $newNode2->setProperty('test2', 'simpleTestValue');

        $newNode2 = $rootNode->createNode('test-node-3', $this->nodeTypeManager->getNodeType('Neos.ContentRepository.Testing:NodeType'));
        $newNode2->setProperty('test1', 'otherValue');

        $this->persistenceManager->persistAll();
    }


    /**
     * @param array<\Neos\ContentRepository\Domain\Model\NodeData> $result
     * @param array $nodeNames
     */
    protected function assertResultConsistsOfNodes($result, $nodeNames)
    {
        foreach ($result as $node) {
            self::assertTrue(in_array($node->getName(), $nodeNames), sprintf('The node with name %s was not part of the result.', $node->getName()));
            unset($nodeNames[array_search($node->getName(), $nodeNames)]);
        }

        self::assertCount(0, $nodeNames, sprintf('The expected node names %s were not part of the result', implode(',', $nodeNames)));
    }
}
