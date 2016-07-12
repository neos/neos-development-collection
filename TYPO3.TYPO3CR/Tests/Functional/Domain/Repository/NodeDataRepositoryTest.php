<?php
namespace TYPO3\TYPO3CR\Tests\Functional\Domain\Repository;

/*
 * This file is part of the TYPO3.TYPO3CR package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use TYPO3\Flow\Tests\Functional\Persistence\Fixtures;
use TYPO3\Flow\Tests\Functional\Persistence\Fixtures\Image;
use TYPO3\Flow\Tests\FunctionalTestCase;
use TYPO3\TYPO3CR\Domain\Model\Workspace;
use TYPO3\TYPO3CR\Domain\Repository\WorkspaceRepository;
use TYPO3\TYPO3CR\Domain\Repository\NodeDataRepository;
use TYPO3\TYPO3CR\Domain\Service\ContextFactoryInterface;
use TYPO3\TYPO3CR\Domain\Service\NodeTypeManager;

/**
 * Functional test case.
 */
class NodeDataRepositoryTest extends FunctionalTestCase
{
    /**
     * @var \TYPO3\TYPO3CR\Domain\Service\Context
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
    public function setUp()
    {
        parent::setUp();
        $this->nodeTypeManager = $this->objectManager->get(\TYPO3\TYPO3CR\Domain\Service\NodeTypeManager::class);
        $this->liveWorkspace = new Workspace('live');
        $this->workspaceRepository = $this->objectManager->get(\TYPO3\TYPO3CR\Domain\Repository\WorkspaceRepository::class);
        $this->workspaceRepository->add($this->liveWorkspace);
        $this->persistenceManager->persistAll();
        $this->contextFactory = $this->objectManager->get(\TYPO3\TYPO3CR\Domain\Service\ContextFactoryInterface::class);
        $this->context = $this->contextFactory->create(['workspaceName' => 'live']);
        $this->nodeDataRepository = $this->objectManager->get(\TYPO3\TYPO3CR\Domain\Repository\NodeDataRepository::class);
    }

    /**
     * @return void
     */
    public function tearDown()
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
        $newNode = $rootNode->createNode('test', $this->nodeTypeManager->getNodeType('TYPO3.TYPO3CR.Testing:NodeTypeWithEntities'));

        $testImage = new Image();
        $this->persistenceManager->add($testImage);

        $newNode->setProperty('image', $testImage);

        $this->persistenceManager->persistAll();

        $relationMap = [
            Fixtures\Image::class => [$this->persistenceManager->getIdentifierByObject($testImage)]
        ];

        $result = $this->nodeDataRepository->findNodesByRelatedEntities($relationMap);

        $this->assertCount(1, $result);
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

        $this->assertCount(2, $foundNodes);
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

        $this->assertCount(4, $foundNodes);
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

        $this->assertCount(2, $foundNodes);
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

        $this->assertCount(6, $foundNodes);
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

        $this->assertCount(3, $foundNodes);
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

        $this->assertCount(7, $foundNodes);
    }

    /**
     * @test
     */
    public function findNodeByPropertySearch()
    {
        $this->createNodesForNodeSearchTest();

        $result = $this->nodeDataRepository->findByProperties('simpleTestValue', 'TYPO3.TYPO3CR.Testing:NodeType', $this->liveWorkspace, $this->context->getDimensions());
        $this->assertCount(2, $result);
        $this->assertResultConsistsOfNodes($result, ['test-node-1', 'test-node-2']);
    }

    /**
     * @test
     */
    public function findNodesByPropertyKeyAndValue()
    {
        $this->createNodesForNodeSearchTest();

        $result = $this->nodeDataRepository->findByProperties(array('test2' => 'simpleTestValue'), 'TYPO3.TYPO3CR.Testing:NodeType', $this->liveWorkspace, $this->context->getDimensions());
        $this->assertCount(1, $result);
        $this->assertEquals('test-node-2', array_shift($result)->getName());
    }

    /**
     * @throws \TYPO3\TYPO3CR\Exception\NodeTypeNotFoundException
     */
    protected function createNodesForNodeSearchTest()
    {
        $rootNode = $this->context->getRootNode();

        $newNode1 = $rootNode->createNode('test-node-1', $this->nodeTypeManager->getNodeType('TYPO3.TYPO3CR.Testing:NodeType'));
        $newNode1->setProperty('test1', 'simpleTestValue');

        $newNode2 = $rootNode->createNode('test-node-2', $this->nodeTypeManager->getNodeType('TYPO3.TYPO3CR.Testing:NodeType'));
        $newNode2->setProperty('test2', 'simpleTestValue');

        $newNode2 = $rootNode->createNode('test-node-3', $this->nodeTypeManager->getNodeType('TYPO3.TYPO3CR.Testing:NodeType'));
        $newNode2->setProperty('test1', 'otherValue');

        $this->persistenceManager->persistAll();
    }


    /**
     * @param array<\TYPO3\TYPO3CR\Domain\Model\NodeData> $result
     * @param array $nodeNames
     */
    protected function assertResultConsistsOfNodes($result, $nodeNames)
    {
        foreach ($result as $node) {
            $this->assertTrue(in_array($node->getName(), $nodeNames), sprintf('The node with name %s was not part of the result.', $node->getName()));
            unset($nodeNames[array_search($node->getName(), $nodeNames)]);
        }

        $this->assertCount(0, $nodeNames, sprintf('The expected node names %s were not part of the result', implode(',', $nodeNames)));
    }
}
