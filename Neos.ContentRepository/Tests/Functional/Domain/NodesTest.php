<?php
namespace Neos\ContentRepository\Tests\Functional\Domain;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\ContentRepository\Exception\NodeExistsException;
use Neos\Flow\Configuration\ConfigurationManager;
use Neos\Flow\Tests\FunctionalTestCase;
use Neos\ContentRepository\Domain\Factory\NodeFactory;
use Neos\ContentRepository\Domain\Model\Node;
use Neos\ContentRepository\Domain\Model\NodeData;
use Neos\ContentRepository\Domain\Model\NodeInterface;
use Neos\ContentRepository\Domain\Model\Workspace;
use Neos\ContentRepository\Domain\Repository\ContentDimensionRepository;
use Neos\ContentRepository\Domain\Repository\NodeDataRepository;
use Neos\ContentRepository\Domain\Repository\WorkspaceRepository;
use Neos\ContentRepository\Domain\Service\Context;
use Neos\ContentRepository\Domain\Service\ContextFactoryInterface;
use Neos\ContentRepository\Domain\Service\NodeTypeManager;
use Neos\ContentRepository\Tests\Functional\Domain\Fixtures\HappyNode;

/**
 * Functional test case which covers all Node-related behavior of the
 * content repository as long as they reside in the live workspace.
 */
class NodesTest extends FunctionalTestCase
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
     * @var ContextFactoryInterface
     */
    protected $contextFactory;

    /**
     * @var NodeTypeManager
     */
    protected $nodeTypeManager;

    /**
     * @var ContentDimensionRepository
     */
    protected $contentDimensionRepository;

    /**
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->nodeDataRepository = new NodeDataRepository();

        if ($this->liveWorkspace === null) {
            $this->liveWorkspace = new Workspace('live');
            $this->objectManager->get(WorkspaceRepository::class);
            $this->workspaceRepository = $this->objectManager->get(WorkspaceRepository::class);
            $this->workspaceRepository->add($this->liveWorkspace);
            $this->workspaceRepository->add(new Workspace('user-admin', $this->liveWorkspace));
            $this->workspaceRepository->add(new Workspace('live2', $this->liveWorkspace));
            $this->workspaceRepository->add(new Workspace('test', $this->liveWorkspace));
            $this->persistenceManager->persistAll();
        }

        $this->contextFactory = $this->objectManager->get(ContextFactoryInterface::class);
        $this->context = $this->contextFactory->create(['workspaceName' => 'live']);
        $this->nodeTypeManager = $this->objectManager->get(NodeTypeManager::class);
        $this->contentDimensionRepository = $this->objectManager->get(ContentDimensionRepository::class);
    }

    /**
     * @return void
     */
    public function tearDown(): void
    {
        parent::tearDown();
        $this->inject($this->contextFactory, 'contextInstances', []);
        $configuredDimensions = $this->objectManager->get(ConfigurationManager::class)->getConfiguration(ConfigurationManager::CONFIGURATION_TYPE_SETTINGS, 'Neos.ContentRepository.contentDimensions');
        $this->contentDimensionRepository->setDimensionsConfiguration($configuredDimensions);
    }

    /**
     * @test
     */
    public function nodeCreationThrowsExceptionIfNodeNameContainsUppercaseCharacters()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->context->getRootNode()->createNode('fooBar');
    }

    /**
     * @test
     */
    public function setNameWorksRecursively()
    {
        $rootNode = $this->context->getRootNode();

        $fooNode = $rootNode->createNode('foo');
        $bazNode = $fooNode->createNode('bar')->createNode('baz');

        $fooNode->setName('quux');

        self::assertEquals('/quux/bar/baz', $bazNode->getPath());
    }

    /**
     * @test
     */
    public function nodesCanBeRenamed()
    {
        $rootNode = $this->context->getRootNode();

        $fooNode = $rootNode->createNode('foo');
        $barNode = $fooNode->createNode('bar');
        $bazNode = $barNode->createNode('baz');

        $fooNode->setName('quux');
        $barNode->setName('lax');

        self::assertNull($rootNode->getNode('foo'));
        self::assertEquals('/quux/lax/baz', $bazNode->getPath());
    }

    /**
     * @test
     */
    public function nodesCreatedInTheLiveWorkspacesCanBeRetrievedAgainInTheLiveContext()
    {
        $rootNode = $this->context->getRootNode();
        $fooNode = $rootNode->createNode('foo');

        self::assertSame($fooNode, $rootNode->getNode('foo'));

        $this->persistenceManager->persistAll();

        self::assertSame($fooNode, $rootNode->getNode('foo'));
    }

    /**
     * @test
     */
    public function createdNodesHaveDefaultValuesSet()
    {
        $rootNode = $this->context->getRootNode();

        $nodeTypeManager = $this->objectManager->get(NodeTypeManager::class);
        $testNodeType = $nodeTypeManager->getNodeType('Neos.ContentRepository.Testing:NodeType');
        $fooNode = $rootNode->createNode('foo', $testNodeType);

        self::assertSame('default value 1', $fooNode->getProperty('test1'));
    }

    /**
     * @test
     */
    public function postprocessorUpdatesNodeTypesProperty()
    {
        $rootNode = $this->context->getRootNode();

        $nodeTypeManager = $this->objectManager->get(NodeTypeManager::class);
        $testNodeType = $nodeTypeManager->getNodeType('Neos.ContentRepository.Testing:NodeTypeWithProcessor');
        $fooNode = $rootNode->createNode('foo', $testNodeType);

        self::assertSame('The value of "someOption" is "someOverriddenValue", the value of "someOtherOption" is "someOtherValue"', $fooNode->getProperty('test1'));
    }

    /**
     * @test
     */
    public function createdNodesHaveSubNodesCreatedIfDefinedInNodeType()
    {
        $rootNode = $this->context->getRootNode();

        $nodeTypeManager = $this->objectManager->get(NodeTypeManager::class);
        $testNodeType = $nodeTypeManager->getNodeType('Neos.ContentRepository.Testing:NodeTypeWithSubnodes');
        $fooNode = $rootNode->createNode('foo', $testNodeType);
        $firstSubnode = $fooNode->getNode('subnode1');
        self::assertInstanceOf(Node::class, $firstSubnode);
        self::assertSame('default value 1', $firstSubnode->getProperty('test1'));
    }

    /**
     * @test
     */
    public function removedNodesCannotBeRetrievedAnymore()
    {
        $rootNode = $this->context->getRootNode();

        $rootNode->createNode('quux');
        $rootNode->getNode('quux')->remove();
        self::assertNull($rootNode->getNode('quux'));

        $barNode = $rootNode->createNode('bar');
        $barNode->remove();
        $this->persistenceManager->persistAll();
        self::assertNull($rootNode->getNode('bar'));

        $rootNode->createNode('baz');
        $this->persistenceManager->persistAll();
        $rootNode->getNode('baz')->remove();
        $bazNode = $rootNode->getNode('baz');
        // workaround for PHPUnit trying to "render" the result *if* not NULL
        $bazNodeResult = $bazNode === null ? null : 'instance-of-' . get_class($bazNode);
        self::assertNull($bazNodeResult);
    }

    /**
     * @test
     */
    public function removedNodesAreNotCountedAsChildNodes()
    {
        $rootNode = $this->context->getRootNode();
        $rootNode->createNode('foo');
        $rootNode->getNode('foo')->remove();

        self::assertFalse($rootNode->hasChildNodes(), 'First check.');

        $rootNode->createNode('bar');
        $this->persistenceManager->persistAll();

        self::assertTrue($rootNode->hasChildNodes(), 'Second check.');

        $context = $this->contextFactory->create(['workspaceName' => 'user-admin']);
        $rootNode = $context->getRootNode();

        $rootNode->getNode('bar')->remove();
        $this->persistenceManager->persistAll();

        self::assertFalse($rootNode->hasChildNodes(), 'Third check.');
    }

    /**
     * @test
     */
    public function creatingAChildNodeAndRetrievingItAfterPersistAllWorks()
    {
        $rootNode = $this->context->getRootNode();

        $firstLevelNode = $rootNode->createNode('firstlevel');
        $secondLevelNode = $firstLevelNode->createNode('secondlevel');
        $secondLevelNode->createNode('thirdlevel');

        $this->persistenceManager->persistAll();

        $retrievedNode = $rootNode->getNode('/firstlevel/secondlevel/thirdlevel');

        self::assertInstanceOf(NodeInterface::class, $retrievedNode);

        self::assertEquals('/firstlevel/secondlevel/thirdlevel', $retrievedNode->getPath());
        self::assertEquals('thirdlevel', $retrievedNode->getName());
        self::assertEquals(3, $retrievedNode->getDepth());
    }

    /**
     * @test
     */
    public function threeCreatedNodesCanBeRetrievedInSameOrder()
    {
        $rootNode = $this->context->getRootNode();

        $parentNode = $rootNode->createNode('parent');
        $node1 = $parentNode->createNode('node1');
        $node2 = $parentNode->createNode('node2');
        $node3 = $parentNode->createNode('node3');

        self::assertTrue($parentNode->hasChildNodes());
        $childNodes = $parentNode->getChildNodes();
        self::assertSameOrder([$node1, $node2, $node3], $childNodes);

        $this->persistenceManager->persistAll();

        self::assertTrue($parentNode->hasChildNodes());
        $childNodes = $parentNode->getChildNodes();
        self::assertSameOrder([$node1, $node2, $node3], $childNodes);
    }

    /**
     * @test
     */
    public function threeChildNodesOfTheRootNodeCanBeRetrievedInSameOrder()
    {
        $rootNode = $this->context->getRootNode();

        $node1 = $rootNode->createNode('node1');
        $node2 = $rootNode->createNode('node2');
        $node3 = $rootNode->createNode('node3');

        self::assertTrue($rootNode->hasChildNodes(), 'child node check before persistAll()');
        $childNodes = $rootNode->getChildNodes();
        self::assertSameOrder([$node1, $node2, $node3], $childNodes);

        $this->persistenceManager->persistAll();

        self::assertTrue($rootNode->hasChildNodes(), 'child node check after persistAll()');
        $childNodes = $rootNode->getChildNodes();
        self::assertSameOrder([$node1, $node2, $node3], $childNodes);
    }

    /**
     * @test
     */
    public function getChildNodesSupportsSettingALimitAndOffset()
    {
        $rootNode = $this->context->getRootNode();

        $node1 = $rootNode->createNode('node1');
        $node2 = $rootNode->createNode('node2');
        $node3 = $rootNode->createNode('node3');
        $node4 = $rootNode->createNode('node4');
        $node5 = $rootNode->createNode('node5');
        $node6 = $rootNode->createNode('node6');

        $childNodes = $rootNode->getChildNodes();
        self::assertSameOrder([$node1, $node2, $node3, $node4, $node5, $node6], $childNodes);

        $this->persistenceManager->persistAll();

        $childNodes = $rootNode->getChildNodes(null, 3, 2);
        self::assertSameOrder([$node3, $node4, $node5], $childNodes);
    }

    /**
     * @test
     */
    public function getChildNodesWorksCaseInsensitive()
    {
        $rootNode = $this->context->getRootNode();

        $node = $rootNode->createNode('node');

        self::assertSame($node, $rootNode->getNode('nOdE'));
    }

    /**
     * @test
     */
    public function moveBeforeMovesNodesBeforeOthersWithoutPersistAll()
    {
        $rootNode = $this->context->getRootNode();

        $parentNode = $rootNode->createNode('parent-node');
        $childNodeA = $parentNode->createNode('child-node-a');
        $childNodeB = $parentNode->createNode('child-node-b');
        $childNodeB->setProperty('name', __METHOD__);
        $childNodeD = $parentNode->createNode('child-node-d');
        $childNodeE = $parentNode->createNode('child-node-e');
        $childNodeF = $parentNode->createNode('child-node-f');
        $childNodeC = $parentNode->createNode('child-node-c');
        $childNodeG = $parentNode->createNode('child-node-g');

        $childNodeC->moveBefore($childNodeD);

        $expectedChildNodes = [
            $childNodeA,
            $childNodeB,
            $childNodeC,
            $childNodeD,
            $childNodeE,
            $childNodeF,
            $childNodeG
        ];
        $actualChildNodes = $parentNode->getChildNodes();
        self::assertSameOrder($expectedChildNodes, array_values($actualChildNodes));
    }

    /**
     * @test
     */
    public function moveIntoMovesNodesIntoOthersOnDifferentLevelWithoutPersistAll()
    {
        $rootNode = $this->context->getRootNode();

        $parentNode = $rootNode->createNode('parent-node');
        $childNodeA = $parentNode->createNode('child-node-a');
        $childNodeB = $parentNode->createNode('child-node-b');
        $childNodeB1 = $childNodeB->createNode('child-node-b1');

        $childNodeB->moveInto($childNodeA);

        self::assertNull($parentNode->getNode('child-node-b'));
        self::assertSame($childNodeB, $childNodeA->getNode('child-node-b'));
        self::assertSame($childNodeB1, $childNodeA->getNode('child-node-b')->getNode('child-node-b1'));
    }

    /**
     * @test
     */
    public function moveBeforeMovesNodesBeforeOthersOnDifferentLevelWithoutPersistAll()
    {
        $rootNode = $this->context->getRootNode();

        $parentNode = $rootNode->createNode('parent-node');
        $parentNode->createNode('child-node-a');
        $childNodeB = $parentNode->createNode('child-node-b');
        $childNodeB1 = $childNodeB->createNode('child-node-b1');
        $childNodeC = $parentNode->createNode('child-node-c');
        $childNodeC1 = $childNodeC->createNode('child-node-c1');

        $childNodeB->moveBefore($childNodeC1);

        self::assertNull($parentNode->getNode('child-node-b'));
        self::assertSame($childNodeB, $childNodeC->getNode('child-node-b'));
        self::assertSame($childNodeB1, $childNodeC->getNode('child-node-b')->getNode('child-node-b1'));

        $expectedChildNodes = [$childNodeB, $childNodeC1];
        $actualChildNodes = $childNodeC->getChildNodes();
        self::assertSameOrder($expectedChildNodes, array_values($actualChildNodes));
    }

    /**
     * @test
     */
    public function moveAfterMovesNodesAfterOthersOnDifferentLevelWithoutPersistAll()
    {
        $rootNode = $this->context->getRootNode();

        $parentNode = $rootNode->createNode('parent-node');
        $parentNode->createNode('child-node-a');
        $childNodeB = $parentNode->createNode('child-node-b');
        $childNodeB1 = $childNodeB->createNode('child-node-b1');
        $childNodeC = $parentNode->createNode('child-node-c');
        $childNodeC1 = $childNodeC->createNode('child-node-c1');

        $childNodeB->moveAfter($childNodeC1);

        self::assertNull($parentNode->getNode('child-node-b'));
        self::assertSame($childNodeB, $childNodeC->getNode('child-node-b'));
        self::assertSame($childNodeB1, $childNodeC->getNode('child-node-b')->getNode('child-node-b1'));

        $expectedChildNodes = [$childNodeC1, $childNodeB];
        $actualChildNodes = $childNodeC->getChildNodes();
        self::assertSameOrder($expectedChildNodes, array_values($actualChildNodes));
    }

    /**
     * @test
     */
    public function moveBeforeNodesWithLowerIndexMovesNodesBeforeOthersWithPersistAll()
    {
        $rootNode = $this->context->getRootNode();

        $parentNode = $rootNode->createNode('parent-node');
        $childNodeA = $parentNode->createNode('child-node-a');
        $childNodeB = $parentNode->createNode('child-node-b');
        $childNodeB->setProperty('name', __METHOD__);
        $childNodeD = $parentNode->createNode('child-node-d');
        $childNodeE = $parentNode->createNode('child-node-e');
        $childNodeF = $parentNode->createNode('child-node-f');
        $childNodeC = $parentNode->createNode('child-node-c');
        $childNodeG = $parentNode->createNode('child-node-g');

        $this->persistenceManager->persistAll();

        $childNodeC->moveBefore($childNodeD);

        $this->persistenceManager->persistAll();

        $expectedChildNodes = [
            $childNodeA,
            $childNodeB,
            $childNodeC,
            $childNodeD,
            $childNodeE,
            $childNodeF,
            $childNodeG
        ];
        $actualChildNodes = $parentNode->getChildNodes();

        self::assertSameOrder($expectedChildNodes, $actualChildNodes);
    }

    /**
     * @test
     */
    public function moveBeforeNodesWithHigherIndexMovesNodesBeforeOthersWithPersistAll()
    {
        $rootNode = $this->context->getRootNode();

        $parentNode = $rootNode->createNode('parent-node');
        $childNodeA = $parentNode->createNode('child-node-a');
        $childNodeB = $parentNode->createNode('child-node-b');
        $childNodeB->setProperty('name', __METHOD__);
        $childNodeF = $parentNode->createNode('child-node-f');
        $childNodeC = $parentNode->createNode('child-node-c');
        $childNodeD = $parentNode->createNode('child-node-d');
        $childNodeE = $parentNode->createNode('child-node-e');
        $childNodeG = $parentNode->createNode('child-node-g');

        $this->persistenceManager->persistAll();

        $childNodeF->moveBefore($childNodeG);

        $this->persistenceManager->persistAll();

        $expectedChildNodes = [
            $childNodeA,
            $childNodeB,
            $childNodeC,
            $childNodeD,
            $childNodeE,
            $childNodeF,
            $childNodeG
        ];
        $actualChildNodes = $parentNode->getChildNodes();

        self::assertSameOrder($expectedChildNodes, $actualChildNodes);
    }

    /**
     * @test
     */
    public function moveBeforeNodesWithHigherIndexMovesNodesBeforeOthersWithoutPersistAll()
    {
        $rootNode = $this->context->getRootNode();

        $parentNode = $rootNode->createNode('parent-node');
        $childNodeA = $parentNode->createNode('child-node-a');
        $childNodeB = $parentNode->createNode('child-node-b');
        $childNodeB->setProperty('name', __METHOD__);
        $childNodeF = $parentNode->createNode('child-node-f');
        $childNodeC = $parentNode->createNode('child-node-c');
        $childNodeD = $parentNode->createNode('child-node-d');
        $childNodeE = $parentNode->createNode('child-node-e');
        $childNodeG = $parentNode->createNode('child-node-g');

        $childNodeF->moveBefore($childNodeG);

        $expectedChildNodes = [
            $childNodeA,
            $childNodeB,
            $childNodeC,
            $childNodeD,
            $childNodeE,
            $childNodeF,
            $childNodeG
        ];
        $actualChildNodes = $parentNode->getChildNodes();

        self::assertSameOrder($expectedChildNodes, $actualChildNodes);
    }

    /**
     * @test
     */
    public function moveAfterNodesWithLowerIndexMovesNodesAfterOthersWithoutPersistAll()
    {
        $rootNode = $this->context->getRootNode();

        $parentNode = $rootNode->createNode('parent-node');
        $childNodeA = $parentNode->createNode('child-node-a');
        $childNodeB = $parentNode->createNode('child-node-b');
        $childNodeB->setProperty('name', __METHOD__);
        $childNodeD = $parentNode->createNode('child-node-d');
        $childNodeE = $parentNode->createNode('child-node-e');
        $childNodeF = $parentNode->createNode('child-node-f');
        $childNodeC = $parentNode->createNode('child-node-c');
        $childNodeG = $parentNode->createNode('child-node-g');

        $childNodeC->moveAfter($childNodeB);

        $expectedChildNodes = [
            $childNodeA,
            $childNodeB,
            $childNodeC,
            $childNodeD,
            $childNodeE,
            $childNodeF,
            $childNodeG
        ];
        $actualChildNodes = $parentNode->getChildNodes();

        self::assertSameOrder($expectedChildNodes, $actualChildNodes);
    }

    /**
     * @test
     */
    public function moveIntoMovesNodesIntoOthersOnDifferentLevelWithPersistAll()
    {
        $rootNode = $this->context->getRootNode();

        $parentNode = $rootNode->createNode('parent-node');
        $childNodeA = $parentNode->createNode('child-node-a');
        $childNodeB = $parentNode->createNode('child-node-b');
        $childNodeB1 = $childNodeB->createNode('child-node-b1');

        $this->persistenceManager->persistAll();

        $childNodeB->moveInto($childNodeA);

        $this->persistenceManager->persistAll();

        self::assertNull($parentNode->getNode('child-node-b'));
        self::assertSame($childNodeB, $childNodeA->getNode('child-node-b'));
        self::assertSame($childNodeB1, $childNodeA->getNode('child-node-b')->getNode('child-node-b1'));
    }

    /**
     * @test
     */
    public function moveBeforeMovesNodesBeforeOthersOnDifferentLevelWithPersistAll()
    {
        $rootNode = $this->context->getRootNode();

        $parentNode = $rootNode->createNode('parent-node');
        $parentNode->createNode('child-node-a');
        $childNodeB = $parentNode->createNode('child-node-b');
        $childNodeB1 = $childNodeB->createNode('child-node-b1');
        $childNodeC = $parentNode->createNode('child-node-c');
        $childNodeC1 = $childNodeC->createNode('child-node-c1');

        $this->persistenceManager->persistAll();

        $childNodeB->moveBefore($childNodeC1);

        $this->persistenceManager->persistAll();

        self::assertNull($parentNode->getNode('child-node-b'));
        self::assertSame($childNodeB, $childNodeC->getNode('child-node-b'));
        self::assertSame($childNodeB1, $childNodeC->getNode('child-node-b')->getNode('child-node-b1'));

        $expectedChildNodes = [$childNodeB, $childNodeC1];
        $actualChildNodes = $childNodeC->getChildNodes();
        self::assertSameOrder($expectedChildNodes, array_values($actualChildNodes));
    }

    /**
     * @test
     */
    public function moveAfterMovesNodesAfterOthersOnDifferentLevelWithPersistAll()
    {
        $rootNode = $this->context->getRootNode();

        $parentNode = $rootNode->createNode('parent-node');
        $parentNode->createNode('child-node-a');
        $childNodeB = $parentNode->createNode('child-node-b');
        $childNodeB1 = $childNodeB->createNode('child-node-b1');
        $childNodeC = $parentNode->createNode('child-node-c');
        $childNodeC1 = $childNodeC->createNode('child-node-c1');

        $this->persistenceManager->persistAll();

        $childNodeB->moveAfter($childNodeC1);

        $this->persistenceManager->persistAll();

        self::assertNull($parentNode->getNode('child-node-b'));
        self::assertSame($childNodeB, $childNodeC->getNode('child-node-b'));
        self::assertSame($childNodeB1, $childNodeC->getNode('child-node-b')->getNode('child-node-b1'));

        $expectedChildNodes = [$childNodeC1, $childNodeB];
        $actualChildNodes = $childNodeC->getChildNodes();
        self::assertSameOrder($expectedChildNodes, array_values($actualChildNodes));
    }

    /**
     * @test
     */
    public function moveAfterNodesWithLowerIndexMovesNodesAfterOthersWithPersistAll()
    {
        $rootNode = $this->context->getRootNode();

        $parentNode = $rootNode->createNode('parent-node');
        $childNodeA = $parentNode->createNode('child-node-a');
        $childNodeB = $parentNode->createNode('child-node-b');
        $childNodeB->setProperty('name', __METHOD__);
        $childNodeD = $parentNode->createNode('child-node-d');
        $childNodeE = $parentNode->createNode('child-node-e');
        $childNodeF = $parentNode->createNode('child-node-f');
        $childNodeC = $parentNode->createNode('child-node-c');
        $childNodeG = $parentNode->createNode('child-node-g');

        $this->persistenceManager->persistAll();

        $childNodeC->moveAfter($childNodeB);

        $this->persistenceManager->persistAll();

        $expectedChildNodes = [
            $childNodeA,
            $childNodeB,
            $childNodeC,
            $childNodeD,
            $childNodeE,
            $childNodeF,
            $childNodeG
        ];
        $actualChildNodes = $parentNode->getChildNodes();

        self::assertSameOrder($expectedChildNodes, $actualChildNodes);
    }

    /**
     * @test
     */
    public function moveAfterNodesWithHigherIndexMovesNodesAfterOthersWithPersistAll()
    {
        $rootNode = $this->context->getRootNode();

        $parentNode = $rootNode->createNode('parent-node');
        $childNodeA = $parentNode->createNode('child-node-a');
        $childNodeB = $parentNode->createNode('child-node-b');
        $childNodeB->setProperty('name', __METHOD__);
        $childNodeF = $parentNode->createNode('child-node-f');
        $childNodeC = $parentNode->createNode('child-node-c');
        $childNodeD = $parentNode->createNode('child-node-d');
        $childNodeE = $parentNode->createNode('child-node-e');
        $childNodeG = $parentNode->createNode('child-node-g');

        $this->persistenceManager->persistAll();

        $childNodeF->moveAfter($childNodeE);

        $this->persistenceManager->persistAll();

        $expectedChildNodes = [
            $childNodeA,
            $childNodeB,
            $childNodeC,
            $childNodeD,
            $childNodeE,
            $childNodeF,
            $childNodeG
        ];
        $actualChildNodes = $parentNode->getChildNodes();

        self::assertSameOrder($expectedChildNodes, $actualChildNodes);
    }

    /**
     * @test
     */
    public function moveAfterNodesWithHigherIndexMovesNodesAfterOthersWithoutPersistAll()
    {
        $rootNode = $this->context->getNode('/');

        $parentNode = $rootNode->createNode('parent-node');
        $childNodeA = $parentNode->createNode('child-node-a');
        $childNodeB = $parentNode->createNode('child-node-b');
        $childNodeF = $parentNode->createNode('child-node-f');
        $childNodeC = $parentNode->createNode('child-node-c');
        $childNodeD = $parentNode->createNode('child-node-d');
        $childNodeE = $parentNode->createNode('child-node-e');
        $childNodeG = $parentNode->createNode('child-node-g');

        $childNodeF->moveAfter($childNodeE);

        $expectedChildNodes = [
            $childNodeA,
            $childNodeB,
            $childNodeC,
            $childNodeD,
            $childNodeE,
            $childNodeF,
            $childNodeG
        ];
        $actualChildNodes = $parentNode->getChildNodes();

        self::assertSameOrder($expectedChildNodes, $actualChildNodes);
    }

    /**
     * @test
     */
    public function moveBeforeInASeparateWorkspaceLeadsToCorrectSortingAcrossWorkspaces()
    {
        $rootNode = $this->context->getNode('/');

        $liveParentNode = $rootNode->createNode('parent-node');
        $childNodeA = $liveParentNode->createNode('child-node-a');
        $childNodeC = $liveParentNode->createNode('child-node-c');
        $childNodeD = $liveParentNode->createNode('child-node-d');
        $childNodeE = $liveParentNode->createNode('child-node-e');
        $childNodeG = $liveParentNode->createNode('child-node-g');

        $this->persistenceManager->persistAll();

        $userContext = $this->contextFactory->create(['workspaceName' => 'live2']);
        $userParentNode = $userContext->getNode('/parent-node');

        $childNodeB = $userParentNode->createNode('child-node-b');
        $childNodeB->moveBefore($childNodeC);

        $childNodeF = $userParentNode->createNode('child-node-f');
        $childNodeF->moveBefore($childNodeG);

        $this->persistenceManager->persistAll();

        $expectedChildNodes = [
            $childNodeA,
            $childNodeB,
            $childNodeC,
            $childNodeD,
            $childNodeE,
            $childNodeF,
            $childNodeG
        ];
        $actualChildNodes = $userParentNode->getChildNodes();

        self::assertSameOrder($expectedChildNodes, $actualChildNodes);
    }

    /**
     * @test
     */
    public function moveBeforeThrowsExceptionIfTargetExists()
    {
        $this->expectException(NodeExistsException::class);
        $rootNode = $this->context->getNode('/');

        $alfaNode = $rootNode->createNode('alfa');
        $alfaChildNode = $alfaNode->createNode('alfa');

        $alfaChildNode->moveBefore($alfaNode);
    }

    /**
     * @test
     */
    public function moveAfterThrowsExceptionIfTargetExists()
    {
        $this->expectException(NodeExistsException::class);
        $rootNode = $this->context->getNode('/');

        $alfaNode = $rootNode->createNode('alfa');
        $alfaChildNode = $alfaNode->createNode('alfa');

        $alfaChildNode->moveAfter($alfaNode);
    }

    /**
     * @test
     */
    public function moveIntoThrowsExceptionIfTargetExists()
    {
        $this->expectException(NodeExistsException::class);
        $rootNode = $this->context->getNode('/');

        $alfaNode = $rootNode->createNode('alfa');
        $alfaChildNode = $alfaNode->createNode('alfa');

        $alfaChildNode->moveInto($rootNode);
    }

    /**
     * @test
     */
    public function moveAndRenameAtTheSameTime()
    {
        $rootNode = $this->context->getRootNode();
        $parentNode = $rootNode->createNode('parent-node');
        $childNodeA = $parentNode->createNode('child-node-a');
        $childNodeB = $parentNode->createNode('child-node-b');
        $childNodeB1 = $childNodeB->createNode('child-node-b1');
        $childNodeB2 = $childNodeB->createNode('child-node-not-unique');
        $childNodeC = $parentNode->createNode('child-node-not-unique');
        $this->persistenceManager->persistAll();
        $childNodeB->moveInto($childNodeA, 'renamed-child-node-b');
        $childNodeC->moveInto($childNodeB, 'child-node-now-unique');
        $this->persistenceManager->persistAll();
        self::assertNull($parentNode->getNode('child-node-b'));
        self::assertSame($childNodeB, $childNodeA->getNode('renamed-child-node-b'));
        self::assertSame($childNodeB1, $childNodeA->getNode('renamed-child-node-b')->getNode('child-node-b1'));
        self::assertSame($childNodeC, $childNodeB->getNode('child-node-now-unique'));
    }

    /**
     * Testcase for bug #34291 (ContentRepository reordering does not take unpersisted
     * node order changes into account)
     *
     * The error can be reproduced in the following way:
     *
     * - First, create some nodes, and persist.
     * - Then, move a node after another one, filling the LAST free sorting index between the nodes. Do NOT persist after that.
     * - After that, try to *again* move a node to this spot. In this case, we need to *renumber*
     *   the node indices, and the system needs to take the before-moved node into account as well.
     *
     * The bug tested by this testcase led to wrong orderings on the floworg website in
     * the documentation part under some circumstances.
     *
     * @test
     */
    public function renumberingTakesUnpersistedNodeOrderChangesIntoAccount()
    {
        $rootNode = $this->context->getRootNode();

        $liveParentNode = $rootNode->createNode('parent-node');
        $nodes = [];
        $nodes[1] = $liveParentNode->createNode('node001');
        $nodes[1]->setIndex(1);
        $nodes[2] = $liveParentNode->createNode('node002');
        $nodes[2]->setIndex(2);
        $nodes[3] = $liveParentNode->createNode('node003');
        $nodes[3]->setIndex(4);
        $nodes[4] = $liveParentNode->createNode('node004');
        $nodes[4]->setIndex(5);

        $this->nodeDataRepository->persistEntities();

        $nodes[1]->moveAfter($nodes[2]);
        $nodes[3]->moveAfter($nodes[2]);

        $this->nodeDataRepository->persistEntities();

        $actualChildNodes = $liveParentNode->getChildNodes();

        $newNodeOrder = [
            $nodes[2],
            $nodes[3],
            $nodes[1],
            $nodes[4]
        ];
        self::assertSameOrder($newNodeOrder, $actualChildNodes);
    }

    /**
     * @test
     */
    public function nodeDataRepositoryRenumbersNodesIfNoFreeSortingIndexesAreAvailable()
    {
        $rootNode = $this->context->getRootNode();

        $liveParentNode = $rootNode->createNode('parent-node');
        $nodes = [];
        $nodes[0] = $liveParentNode->createNode('node000');
        $nodes[150] = $liveParentNode->createNode('node150');

        $this->persistenceManager->persistAll();

        for ($i = 1; $i < 150; $i++) {
            $nodes[$i] = $liveParentNode->createNode('node' . sprintf('%1$03d', $i));
            $nodes[$i]->moveAfter($nodes[$i - 1]);
        }
        $this->persistenceManager->persistAll();

        ksort($nodes);
        $actualChildNodes = $liveParentNode->getChildNodes();
        self::assertSameOrder($nodes, $actualChildNodes);
    }

    /**
     * @test
     */
    public function nodeDataRepositoryRenumbersNodesIfNoFreeSortingIndexesAreAvailableAcrossDimensions()
    {
        $this->contentDimensionRepository->setDimensionsConfiguration([
            'test' => [
                'default' => 'a'
            ]
        ]);

        $variantContextA = $this->contextFactory->create([
            'dimensions' => ['test' => ['a']],
            'targetDimensions' => ['test' => 'a']
        ]);
        $variantContextB = $this->contextFactory->create([
            'dimensions' => ['test' => ['b', 'a']],
            'targetDimensions' => ['test' => 'b']
        ]);

        $rootNodeA = $variantContextA->getRootNode();
        $rootNodeB = $rootNodeA->createVariantForContext($variantContextB);

        $liveParentNodeA = $rootNodeA->createNode('parent-node');
        $liveParentNodeB = $liveParentNodeA->createVariantForContext($variantContextB);

        $nodesA = [];
        $nodesA[0] = $liveParentNodeA->createNode('node000');
        $nodesA[150] = $liveParentNodeA->createNode('node150');

        $nodesB[0] = $nodesA[0]->createVariantForContext($variantContextB);
        $nodesB[150] = $nodesA[150]->createVariantForContext($variantContextB);

        $this->persistenceManager->persistAll();

        for ($i = 1; $i < 150; $i++) {
            $newNodeA = $liveParentNodeA->createNode('node' . sprintf('%1$03d', $i));
            $newNodeA->moveAfter($nodesA[$i - 1]);
            $newNodeB = $newNodeA->createVariantForContext($variantContextB);

            $nodesA[$i] = $newNodeA;
            $nodesB[$i] = $newNodeB;
        }
        $this->persistenceManager->persistAll();

        ksort($nodesA);
        ksort($nodesB);

        $actualChildNodesA = $liveParentNodeA->getChildNodes();
        $actualChildNodesB = $liveParentNodeB->getChildNodes();

        self::assertSameOrder($nodesA, $actualChildNodesA);
        self::assertSameOrder($nodesB, $actualChildNodesB);
    }

    /**
     * Asserts that the order of the given nodes is the same.
     * This doesn't check if the node objects are the same or equal but rather tests
     * if their path is identical. Therefore nodes can be in different workspaces
     * or nodes.
     *
     * @param array $expectedNodes The expected order
     * @param array $actualNodes The actual order
     * @return void
     */
    protected function assertSameOrder(array $expectedNodes, array $actualNodes)
    {
        if (count($expectedNodes) !== count($actualNodes)) {
            $this->fail(sprintf('Number of nodes did not match: got %s expected and %s actual nodes.', count($expectedNodes), count($actualNodes)));
        }

        reset($expectedNodes);
        foreach ($actualNodes as $actualNode) {
            $expectedNode = current($expectedNodes);
            if ($expectedNode->getPath() !== $actualNode->getPath()) {
                $this->fail(sprintf('Expected node %s (index %s), actual node %s (index %s)', $expectedNode->getPath(), $expectedNode->getIndex(), $actualNode->getPath(), $actualNode->getIndex()));
            }
            next($expectedNodes);
        }
        self::assertTrue(true);
    }

    /**
     * @test
     */
    public function getLabelUsesFallbackExpression()
    {
        $node = $this->context->getNode('/');

        self::assertEquals('unstructured ()', $node->getLabel());
    }

    /**
     * @test
     */
    public function nodesCanBeCopiedAfterAndBeforeAndKeepProperties()
    {
        $rootNode = $this->context->getNode('/');

        $bazNode = $rootNode->createNode('baz');
        $fluxNode = $rootNode->createNode('flux');
        $fluxNode->setProperty('someProperty', 42);

        $bachNode = $fluxNode->copyBefore($bazNode, 'bach');
        $flussNode = $fluxNode->copyAfter($bazNode, 'fluss');
        self::assertNotSame($fluxNode, $flussNode);
        self::assertNotSame($fluxNode, $bachNode);
        self::assertEquals($fluxNode->getProperties(), $bachNode->getProperties());
        self::assertEquals($fluxNode->getProperties(), $flussNode->getProperties());
        $this->persistenceManager->persistAll();

        self::assertSame($bachNode, $rootNode->getNode('bach'));
        self::assertSame($flussNode, $rootNode->getNode('fluss'));
    }

    /**
     * @test
     */
    public function nodesCanBeCopiedBefore()
    {
        $rootNode = $this->context->getNode('/');

        $bazNode = $rootNode->createNode('baz');
        $fluxNode = $rootNode->createNode('flux');

        $fluxNode->copyBefore($bazNode, 'fluss');

        $childNodes = $rootNode->getChildNodes();
        $names = new \stdClass();
        $names->names = [];
        array_walk($childNodes, function ($value, $key, &$names) {
            $names->names[] = $value->getName();
        }, $names);
        self::assertSame(['fluss', 'baz', 'flux'], $names->names);
    }

    /**
     * @test
     */
    public function nodesCanBeCopiedAfter()
    {
        $rootNode = $this->context->getNode('/');

        $bazNode = $rootNode->createNode('baz');
        $fluxNode = $rootNode->createNode('flux');

        $fluxNode->copyAfter($bazNode, 'fluss');

        $childNodes = $rootNode->getChildNodes();
        $names = new \stdClass();
        $names->names = [];
        array_walk($childNodes, function ($value, $key, &$names) {
            $names->names[] = $value->getName();
        }, $names);
        self::assertSame(['baz', 'fluss', 'flux'], $names->names);
    }

    /**
     * @test
     */
    public function nodesCanBeCopiedInto()
    {
        $rootNode = $this->context->getNode('/');

        $alfaNode = $rootNode->createNode('alfa');
        $bravoNode = $rootNode->createNode('bravo');
        $bravoNode->setProperty('test', true);

        $bravoNode->copyInto($alfaNode, 'charlie');

        self::assertSame(true, $alfaNode->getNode('charlie')->getProperty('test'));
    }

    /**
     * @test
     */
    public function nodesCanBeCopiedIntoThemselves()
    {
        $rootNode = $this->context->getNode('/');

        $alfaNode = $rootNode->createNode('alfa');
        $alfaNode->setProperty('test', true);

        $bravoNode = $alfaNode->copyInto($alfaNode, 'bravo');

        self::assertSame(true, $bravoNode->getProperty('test'));
        self::assertSame($bravoNode, $alfaNode->getNode('bravo'));
    }

    /**
     * @test
     */
    public function nodesAreCopiedBeforeRecursively()
    {
        $rootNode = $this->context->getNode('/');

        $bazNode = $rootNode->createNode('baz');
        $fluxNode = $rootNode->createNode('flux');
        $fluxNode->createNode('capacitor');
        $fluxNode->createNode('second');
        $fluxNode->createNode('third');

        $copiedChildNodes = $fluxNode->copyBefore($bazNode, 'fluss')->getChildNodes();

        $names = new \stdClass();
        $names->names = [];
        array_walk($copiedChildNodes, function ($value, $key, &$names) {
            $names->names[] = $value->getName();
        }, $names);
        self::assertSame(['capacitor', 'second', 'third'], $names->names);
    }

    /**
     * @test
     */
    public function nodesAreCopiedAfterRecursively()
    {
        $rootNode = $this->context->getNode('/');

        $bazNode = $rootNode->createNode('baz');
        $fluxNode = $rootNode->createNode('flux');
        $fluxNode->createNode('capacitor');
        $fluxNode->createNode('second');
        $fluxNode->createNode('third');

        $copiedChildNodes = $fluxNode->copyAfter($bazNode, 'fluss')->getChildNodes();

        $names = new \stdClass();
        $names->names = [];
        array_walk($copiedChildNodes, function ($value, $key, &$names) {
            $names->names[] = $value->getName();
        }, $names);
        self::assertSame(['capacitor', 'second', 'third'], $names->names);
    }

    /**
     * @test
     */
    public function nodesAreCopiedIntoRecursively()
    {
        $rootNode = $this->context->getNode('/');

        $alfaNode = $rootNode->createNode('alfa');
        $bravoNode = $rootNode->createNode('bravo');
        $bravoNode->setProperty('test', true);
        $charlieNode = $bravoNode->createNode('charlie');
        $charlieNode->setProperty('test2', true);

        $deltaNode = $bravoNode->copyInto($alfaNode, 'delta');

        self::assertSame($alfaNode->getNode('delta'), $deltaNode);
        self::assertSame($alfaNode->getNode('delta')->getProperty('test'), true);
        self::assertSame($alfaNode->getNode('delta')->getNode('charlie')->getProperty('test2'), true);
    }

    /**
     * @test
     */
    public function nodesAreCopiedIntoThemselvesRecursively()
    {
        $rootNode = $this->context->getNode('/');

        $alfaNode = $rootNode->createNode('alfa');
        $bravoNode = $alfaNode->createNode('bravo');
        $bravoNode->setProperty('test', true);

        $charlieNode = $alfaNode->copyInto($alfaNode, 'charlie');

        self::assertSame($alfaNode->getNode('charlie'), $charlieNode);
        self::assertSame($alfaNode->getNode('charlie')->getNode('bravo')->getProperty('test'), true);
    }

    /**
     * @test
     */
    public function copyBeforeThrowsExceptionIfTargetExists()
    {
        $this->expectException(NodeExistsException::class);
        $rootNode = $this->context->getNode('/');

        $rootNode->createNode('exists');
        $bazNode = $rootNode->createNode('baz');
        $fluxNode = $rootNode->createNode('flux');

        $fluxNode->copyBefore($bazNode, 'exists');
    }

    /**
     * @test
     */
    public function copyAfterThrowsExceptionIfTargetExists()
    {
        $this->expectException(NodeExistsException::class);
        $rootNode = $this->context->getNode('/');

        $rootNode->createNode('exists');
        $bazNode = $rootNode->createNode('baz');
        $fluxNode = $rootNode->createNode('flux');

        $fluxNode->copyAfter($bazNode, 'exists');
    }

    /**
     * @test
     */
    public function copyIntoThrowsExceptionIfTargetExists()
    {
        $this->expectException(NodeExistsException::class);
        $rootNode = $this->context->getNode('/');

        $rootNode->createNode('exists');
        $alfaNode = $rootNode->createNode('alfa');

        $alfaNode->copyInto($rootNode, 'exists');
    }

    /**
     * @test
     */
    public function setPropertyAcceptsAndConvertsIdentifierIfTargetTypeIsReference()
    {
        $nodeTypeManager = $this->objectManager->get(NodeTypeManager::class);
        $nodeType = $nodeTypeManager->getNodeType('Neos.ContentRepository.Testing:NodeTypeWithReferences');

        $rootNode = $this->context->getNode('/');
        $nodeA = $rootNode->createNode('node-a', $nodeType, '30e893c1-caef-0ca5-b53d-e5699bb8e506');
        $nodeB = $rootNode->createNode('node-b', $nodeType, '81c848ed-abb5-7608-a5db-7eea0331ccfa');

        $nodeA->setProperty('property2', '81c848ed-abb5-7608-a5db-7eea0331ccfa');
        self::assertSame($nodeB, $nodeA->getProperty('property2'));

        $nodeA->setProperty('property2', $nodeB);
        self::assertSame($nodeB, $nodeA->getProperty('property2'));
    }

    /**
     * @test
     */
    public function setPropertyAcceptsAndConvertsIdentifiersIfTargetTypeIsReferences()
    {
        $nodeTypeManager = $this->objectManager->get(NodeTypeManager::class);
        $nodeType = $nodeTypeManager->getNodeType('Neos.ContentRepository.Testing:NodeTypeWithReferences');

        $rootNode = $this->context->getNode('/');
        $nodeA = $rootNode->createNode('node-a', $nodeType, '30e893c1-caef-0ca5-b53d-e5699bb8e506');
        $nodeB = $rootNode->createNode('node-b', $nodeType, '81c848ed-abb5-7608-a5db-7eea0331ccfa');
        $nodeC = $rootNode->createNode('node-c', $nodeType, 'e3b99700-f632-4a4c-2f93-0ad07eaf733f');

        $expectedNodes = [$nodeB, $nodeC];

        $nodeA->setProperty('property3', [
            '81c848ed-abb5-7608-a5db-7eea0331ccfa',
            'e3b99700-f632-4a4c-2f93-0ad07eaf733f'
        ]);
        self::assertSame($expectedNodes, $nodeA->getProperty('property3'));

        $nodeA->setProperty('property3', $expectedNodes);
        self::assertSame($expectedNodes, $nodeA->getProperty('property3'));
    }

    /**
     * @test
     */
    public function getPropertiesReturnsReferencePropertiesAsNodeObjects()
    {
        $nodeTypeManager = $this->objectManager->get(NodeTypeManager::class);
        $nodeType = $nodeTypeManager->getNodeType('Neos.ContentRepository.Testing:NodeTypeWithReferences');

        $rootNode = $this->context->getNode('/');
        $nodeA = $rootNode->createNode('node-a', $nodeType, '30e893c1-caef-0ca5-b53d-e5699bb8e506');
        $nodeB = $rootNode->createNode('node-b', $nodeType, '81c848ed-abb5-7608-a5db-7eea0331ccfa');
        $nodeC = $rootNode->createNode('node-c', $nodeType, 'e3b99700-f632-4a4c-2f93-0ad07eaf733f');

        $expectedNodes = [$nodeB, $nodeC];

        $nodeA->setProperty('property2', '81c848ed-abb5-7608-a5db-7eea0331ccfa');
        $nodeA->setProperty('property3', [
            '81c848ed-abb5-7608-a5db-7eea0331ccfa',
            'e3b99700-f632-4a4c-2f93-0ad07eaf733f'
        ]);

        $actualProperties = $nodeA->getProperties();
        self::assertSame($nodeB, $actualProperties['property2']);
        self::assertSame($expectedNodes, $actualProperties['property3']);
    }

    /**
     * @test
     */
    public function getPropertyDoesNotReturnNodeReferencesIfTheyAreNotVisibleAccordingToTheContentContext()
    {
        $nodeTypeManager = $this->objectManager->get(NodeTypeManager::class);
        $nodeType = $nodeTypeManager->getNodeType('Neos.ContentRepository.Testing:NodeTypeWithReferences');

        $this->context = $this->contextFactory->create(['workspaceName' => 'live', 'invisibleContentShown' => false]);

        $rootNode = $this->context->getNode('/');
        $nodeA = $rootNode->createNode('node-a', $nodeType, '30e893c1-caef-0ca5-b53d-e5699bb8e506');
        $nodeB = $rootNode->createNode('node-b', $nodeType, '81c848ed-abb5-7608-a5db-7eea0331ccfa');
        $nodeC = $rootNode->createNode('node-c', $nodeType, 'e3b99700-f632-4a4c-2f93-0ad07eaf733f');

        $nodeA->setProperty('property2', '81c848ed-abb5-7608-a5db-7eea0331ccfa');
        $nodeA->setProperty('property3', [
            '81c848ed-abb5-7608-a5db-7eea0331ccfa',
            'e3b99700-f632-4a4c-2f93-0ad07eaf733f'
        ]);

        $nodeB->setHidden(true);

        self::assertNull($nodeA->getProperty('property2'));
        $property3 = $nodeA->getProperty('property3');
        self::assertCount(1, $property3);
        self::assertSame($nodeC, reset($property3));
    }

    /**
     * @test
     */
    public function getPropertyReturnsReferencedNodesInCorrectWorkspace()
    {
        $nodeTypeManager = $this->objectManager->get(NodeTypeManager::class);
        $nodeType = $nodeTypeManager->getNodeType('Neos.ContentRepository.Testing:NodeTypeWithReferences');

        $identifier = '81c848ed-abb5-7608-a5db-7eea0331ccfa';
        $rootNode = $this->context->getNode('/');
        $referencedNode = $rootNode->createNode('referenced-node', $nodeType, $identifier);
        $node = $rootNode->createNode('node', $nodeType, '30e893c1-caef-0ca5-b53d-e5699bb8e506');
        $node->setProperty('property2', $identifier);

        $testContext = $this->contextFactory->create(['workspaceName' => 'test']);

        $testRootNode = $testContext->getNode('/');
        $testReferencedNode = $testRootNode->createNode('test-referenced-node', $nodeType, $identifier);
        $testNode = $testRootNode->getNode('node');

        $referencedNodeProperty = $node->getProperty('property2');
        self::assertNotSame($referencedNodeProperty->getWorkspace(), $testReferencedNode->getWorkspace());
        self::assertSame($referencedNodeProperty->getWorkspace(), $referencedNode->getWorkspace());

        $testReferencedNodeProperty = $testNode->getProperty('property2');
        self::assertNotSame($testReferencedNodeProperty->getWorkspace(), $referencedNode->getWorkspace());
        self::assertSame($testReferencedNodeProperty->getWorkspace(), $testReferencedNode->getWorkspace());
    }

    /**
     * @test
     */
    public function nodeFactoryCachesCreatedNodesBasedOnIdentifierAndDimensions()
    {
        /** @var NodeFactory $nodeFactory */
        $nodeFactory = $this->objectManager->get(NodeFactory::class);

        $nodeDataA = new NodeData('/', $this->context->getWorkspace(), '30e893c1-caef-0ca5-b53d-e5699bb8e506', ['test' => [1]]);
        $variantNodeA1 = $nodeFactory->createFromNodeData($nodeDataA, $this->context);
        $variantNodeA2 = $nodeFactory->createFromNodeData($nodeDataA, $this->context);

        $nodeDataB = new NodeData('/', $this->context->getWorkspace(), '30e893c1-caef-0ca5-b53d-e5699bb8e506', ['test' => [2]]);
        $variantNodeB = $nodeFactory->createFromNodeData($nodeDataB, $this->context);

        self::assertSame($variantNodeA1, $variantNodeA2);
        self::assertSame($variantNodeA1, $variantNodeB);
    }

    /**
     * @test
     */
    public function createVariantForContextMatchesTargetContextDimensions()
    {
        $this->contentDimensionRepository->setDimensionsConfiguration([
            'test' => [
                'default' => 'a'
            ]
        ]);

        $variantContextA = $this->contextFactory->create([
            'dimensions' => ['test' => ['a']],
            'targetDimensions' => ['test' => 'a']
        ]);
        $variantContextB = $this->contextFactory->create([
            'dimensions' => ['test' => ['b', 'a']],
            'targetDimensions' => ['test' => 'b']
        ]);

        $variantNodeA = $variantContextA->getRootNode()->createNode('test');
        $variantNodeB = $variantNodeA->createVariantForContext($variantContextB);

        self::assertSame($variantNodeB->getDimensions(), array_map(function ($value) {
            return [$value];
        }, $variantContextB->getTargetDimensions()));
    }

    /**
     * @test
     */
    public function createVariantForContextAlsoWorksIfTheTargetWorkspaceDiffersFromTheSourceWorkspace()
    {
        $this->contentDimensionRepository->setDimensionsConfiguration([
            'test' => [
                'default' => 'a'
            ]
        ]);

        $variantContextA = $this->contextFactory->create([
            'dimensions' => ['test' => ['a']],
            'targetDimensions' => ['test' => 'a'],
            'workspace' => 'live'
        ]);
        $variantContextB = $this->contextFactory->create([
            'dimensions' => ['test' => ['b', 'a']],
            'targetDimensions' => ['test' => 'b'],
            'workspace' => 'test'
        ]);

        $variantNodeA = $variantContextA->getRootNode()->createNode('test');
        $variantNodeB = $variantNodeA->createVariantForContext($variantContextB);

        self::assertSame($variantNodeB->getDimensions(), array_map(function ($value) {
            return [$value];
        }, $variantContextB->getTargetDimensions()));
    }

    /**
     * @test
     */
    public function adoptNodeReturnsExistingNodeWithMatchingDimensionsIfPossible()
    {
        $this->contentDimensionRepository->setDimensionsConfiguration([
            'test' => [
                'default' => 'a'
            ]
        ]);

        $variantContextA = $this->contextFactory->create([
            'dimensions' => ['test' => ['a']],
            'targetDimensions' => ['test' => 'a']
        ]);
        $variantContextB = $this->contextFactory->create([
            'dimensions' => ['test' => ['b', 'a']],
            'targetDimensions' => ['test' => 'b']
        ]);

        $identifier = '30e893c1-caef-0ca5-b53d-e5699bb8e506';
        $variantNodeA = $variantContextA->getRootNode()->createNode('test', null, $identifier);

        // Same context
        self::assertSame($variantContextA->adoptNode($variantNodeA), $variantNodeA);

        // Different context with fallback
        self::assertNotSame($variantContextB->adoptNode($variantNodeA)->getDimensions(), $variantNodeA->getDimensions(), 'Dimensions of $variantNodeA should change when adopted in $variantContextB');
    }

    /**
     * @test
     */
    public function adoptNodeMatchesTargetContextDimensions()
    {
        $this->contentDimensionRepository->setDimensionsConfiguration([
            'test' => [
                'default' => 'a'
            ]
        ]);

        $variantContextA = $this->contextFactory->create([
            'dimensions' => ['test' => ['a']],
            'targetDimensions' => ['test' => 'a']
        ]);
        $variantContextB = $this->contextFactory->create([
            'dimensions' => ['test' => ['b', 'a']],
            'targetDimensions' => ['test' => 'b']
        ]);

        $variantNodeA = $variantContextA->getRootNode()->createNode('test');
        $variantNodeB = $variantContextB->adoptNode($variantNodeA);

        self::assertSame($variantNodeB->getDimensions(), $variantContextB->getTargetDimensionValues());
    }

    /**
     * @test
     */
    public function adoptNodeWithExistingNodeMatchingTargetDimensionValuesWillReuseNode()
    {
        $this->contentDimensionRepository->setDimensionsConfiguration([
            'test' => [
                'default' => 'a'
            ]
        ]);

        $variantContextA = $this->contextFactory->create([
            'dimensions' => ['test' => ['a']],
            'targetDimensions' => ['test' => 'a']
        ]);
        $variantContextB = $this->contextFactory->create([
            'dimensions' => ['test' => ['b', 'a']],
            'targetDimensions' => ['test' => 'b']
        ]);

        $variantContextA->getRootNode()->getNodeData()->createNodeData('test', null, null, $variantContextA->getWorkspace(), [
            'test' => [
                'a',
                'b'
            ]
        ]);
        $this->persistenceManager->persistAll();

        $variantNodeA = $variantContextA->getRootNode()->getNode('test');
        $variantNodeB = $variantContextB->adoptNode($variantNodeA);

        self::assertSame($variantNodeA->getDimensions(), $variantNodeB->getDimensions());
    }

    /**
     * @test
     */
    public function nodesCanHaveCustomImplementationClass()
    {
        $rootNode = $this->context->getRootNode();
        $testingNodeType = $this->nodeTypeManager->getNodeType('Neos.ContentRepository.Testing:NodeTypeWithReferences');
        $happyNodeType = $this->nodeTypeManager->getNodeType('Neos.ContentRepository.Testing:HappyTestingNode');
        $headlineNodeType = $this->nodeTypeManager->getNodeType('Neos.ContentRepository.Testing:Headline');

        $fooNode = $rootNode->createNode('foo', $testingNodeType);
        $happyNode = $fooNode->createNode('bar', $happyNodeType);
        $bazNode = $happyNode->createNode('baz', $headlineNodeType);

        $this->assertNotInstanceOf(HappyNode::class, $fooNode);
        self::assertInstanceOf(HappyNode::class, $happyNode);
        $this->assertNotInstanceOf(HappyNode::class, $bazNode);

        self::assertEquals('bar claps hands!', $happyNode->clapsHands());
    }


    /**
     * @test
     */
    public function getChildNodesWithNodeTypeFilterWorks()
    {
        $documentNodeType = $this->nodeTypeManager->getNodeType('Neos.ContentRepository.Testing:Document');
        $headlineNodeType = $this->nodeTypeManager->getNodeType('Neos.ContentRepository.Testing:Headline');
        $imageNodeType = $this->nodeTypeManager->getNodeType('Neos.ContentRepository.Testing:Image');

        $node = $this->context->getRootNode()->createNode('node-with-child-node', $documentNodeType);
        $node->createNode('headline', $headlineNodeType);
        $node->createNode('text', $imageNodeType);
        self::assertCount(1, $node->getChildNodes('Neos.ContentRepository.Testing:Headline'));
    }

    /**
     * @test
     */
    public function nodesInPathAreHiddenIfBetterVariantInOtherPathExists()
    {
        $this->contentDimensionRepository->setDimensionsConfiguration([
            'test' => [
                'default' => 'a'
            ]
        ]);

        $variantContextA = $this->contextFactory->create([
            'dimensions' => ['test' => ['a']],
            'targetDimensions' => ['test' => 'a']
        ]);

        $container1 = $variantContextA->getRootNode()->createNode('container1');
        $variantContextA->getRootNode()->createNode('container2');

        $container1->createNode('node-with-variant');

        $variantContextB = $this->contextFactory->create([
            'dimensions' => ['test' => ['b', 'a']],
            'targetDimensions' => ['test' => 'b']
        ]);

        $nodeWithVariantOriginal = $variantContextB->getNode('/container1/node-with-variant');
        $variantContextB->getNode('/container2')->createNode('node-with-variant', null, $nodeWithVariantOriginal->getIdentifier());

        $this->persistenceManager->persistAll();
        $this->contextFactory->reset();

        $variantContextB = $this->contextFactory->create([
            'dimensions' => ['test' => ['b', 'a']],
            'targetDimensions' => ['test' => 'b']
        ]);

        // Both containers should be available due to fallbacks
        self::assertCount(2, $variantContextB->getRootNode()->getChildNodes());

        // This should NOT find the node created in variantContextA as
        // a better matching (with "b" dimension value) variant (same identifier) exists in container two
        self::assertCount(0, $variantContextB->getNode('/container1')->getChildNodes());
        // This is the better matching variant and should be found.
        self::assertCount(1, $variantContextB->getNode('/container2')->getChildNodes());
    }
}
