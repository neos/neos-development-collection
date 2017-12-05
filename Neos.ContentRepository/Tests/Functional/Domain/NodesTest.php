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
    public function setUp()
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
    public function tearDown()
    {
        parent::tearDown();
        $this->inject($this->contextFactory, 'contextInstances', []);
        $this->contentDimensionRepository->setDimensionsConfiguration([]);
    }

    /**
     * @test
     * @expectedException \InvalidArgumentException
     */
    public function nodeCreationThrowsExceptionIfNodeNameContainsUppercaseCharacters()
    {
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

        $this->assertEquals('/quux/bar/baz', $bazNode->getPath());
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

        $this->assertNull($rootNode->getNode('foo'));
        $this->assertEquals('/quux/lax/baz', $bazNode->getPath());
    }

    /**
     * @test
     */
    public function nodesCreatedInTheLiveWorkspacesCanBeRetrievedAgainInTheLiveContext()
    {
        $rootNode = $this->context->getRootNode();
        $fooNode = $rootNode->createNode('foo');

        $this->assertSame($fooNode, $rootNode->getNode('foo'));

        $this->persistenceManager->persistAll();

        $this->assertSame($fooNode, $rootNode->getNode('foo'));
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

        $this->assertSame('default value 1', $fooNode->getProperty('test1'));
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

        $this->assertSame('The value of "someOption" is "someOverriddenValue", the value of "someOtherOption" is "someOtherValue"', $fooNode->getProperty('test1'));
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
        $this->assertInstanceOf(Node::class, $firstSubnode);
        $this->assertSame('default value 1', $firstSubnode->getProperty('test1'));
    }

    /**
     * @test
     */
    public function removedNodesCannotBeRetrievedAnymore()
    {
        $rootNode = $this->context->getRootNode();

        $rootNode->createNode('quux');
        $rootNode->getNode('quux')->remove();
        $this->assertNull($rootNode->getNode('quux'));

        $barNode = $rootNode->createNode('bar');
        $barNode->remove();
        $this->persistenceManager->persistAll();
        $this->assertNull($rootNode->getNode('bar'));

        $rootNode->createNode('baz');
        $this->persistenceManager->persistAll();
        $rootNode->getNode('baz')->remove();
        $bazNode = $rootNode->getNode('baz');
        // workaround for PHPUnit trying to "render" the result *if* not NULL
        $bazNodeResult = $bazNode === null ? null : 'instance-of-' . get_class($bazNode);
        $this->assertNull($bazNodeResult);
    }

    /**
     * @test
     */
    public function removedNodesAreNotCountedAsChildNodes()
    {
        $rootNode = $this->context->getRootNode();
        $rootNode->createNode('foo');
        $rootNode->getNode('foo')->remove();

        $this->assertFalse($rootNode->hasChildNodes(), 'First check.');

        $rootNode->createNode('bar');
        $this->persistenceManager->persistAll();

        $this->assertTrue($rootNode->hasChildNodes(), 'Second check.');

        $context = $this->contextFactory->create(['workspaceName' => 'user-admin']);
        $rootNode = $context->getRootNode();

        $rootNode->getNode('bar')->remove();
        $this->persistenceManager->persistAll();

        $this->assertFalse($rootNode->hasChildNodes(), 'Third check.');
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

        $this->assertInstanceOf(NodeInterface::class, $retrievedNode);

        $this->assertEquals('/firstlevel/secondlevel/thirdlevel', $retrievedNode->getPath());
        $this->assertEquals('thirdlevel', $retrievedNode->getName());
        $this->assertEquals(3, $retrievedNode->getDepth());
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

        $this->assertTrue($parentNode->hasChildNodes());
        $childNodes = $parentNode->getChildNodes();
        $this->assertSameOrder([$node1, $node2, $node3], $childNodes);

        $this->persistenceManager->persistAll();

        $this->assertTrue($parentNode->hasChildNodes());
        $childNodes = $parentNode->getChildNodes();
        $this->assertSameOrder([$node1, $node2, $node3], $childNodes);
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

        $this->assertTrue($rootNode->hasChildNodes(), 'child node check before persistAll()');
        $childNodes = $rootNode->getChildNodes();
        $this->assertSameOrder([$node1, $node2, $node3], $childNodes);

        $this->persistenceManager->persistAll();

        $this->assertTrue($rootNode->hasChildNodes(), 'child node check after persistAll()');
        $childNodes = $rootNode->getChildNodes();
        $this->assertSameOrder([$node1, $node2, $node3], $childNodes);
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
        $this->assertSameOrder([$node1, $node2, $node3, $node4, $node5, $node6], $childNodes);

        $this->persistenceManager->persistAll();

        $childNodes = $rootNode->getChildNodes(null, 3, 2);
        $this->assertSameOrder([$node3, $node4, $node5], $childNodes);
    }

    /**
     * @test
     */
    public function getChildNodesWorksCaseInsensitive()
    {
        $rootNode = $this->context->getRootNode();

        $node = $rootNode->createNode('node');

        $this->assertSame($node, $rootNode->getNode('nOdE'));
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
        $this->assertSameOrder($expectedChildNodes, array_values($actualChildNodes));
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

        $this->assertNull($parentNode->getNode('child-node-b'));
        $this->assertSame($childNodeB, $childNodeA->getNode('child-node-b'));
        $this->assertSame($childNodeB1, $childNodeA->getNode('child-node-b')->getNode('child-node-b1'));
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

        $this->assertNull($parentNode->getNode('child-node-b'));
        $this->assertSame($childNodeB, $childNodeC->getNode('child-node-b'));
        $this->assertSame($childNodeB1, $childNodeC->getNode('child-node-b')->getNode('child-node-b1'));

        $expectedChildNodes = [$childNodeB, $childNodeC1];
        $actualChildNodes = $childNodeC->getChildNodes();
        $this->assertSameOrder($expectedChildNodes, array_values($actualChildNodes));
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

        $this->assertNull($parentNode->getNode('child-node-b'));
        $this->assertSame($childNodeB, $childNodeC->getNode('child-node-b'));
        $this->assertSame($childNodeB1, $childNodeC->getNode('child-node-b')->getNode('child-node-b1'));

        $expectedChildNodes = [$childNodeC1, $childNodeB];
        $actualChildNodes = $childNodeC->getChildNodes();
        $this->assertSameOrder($expectedChildNodes, array_values($actualChildNodes));
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

        $this->assertSameOrder($expectedChildNodes, $actualChildNodes);
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

        $this->assertSameOrder($expectedChildNodes, $actualChildNodes);
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

        $this->assertSameOrder($expectedChildNodes, $actualChildNodes);
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

        $this->assertSameOrder($expectedChildNodes, $actualChildNodes);
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

        $this->assertNull($parentNode->getNode('child-node-b'));
        $this->assertSame($childNodeB, $childNodeA->getNode('child-node-b'));
        $this->assertSame($childNodeB1, $childNodeA->getNode('child-node-b')->getNode('child-node-b1'));
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

        $this->assertNull($parentNode->getNode('child-node-b'));
        $this->assertSame($childNodeB, $childNodeC->getNode('child-node-b'));
        $this->assertSame($childNodeB1, $childNodeC->getNode('child-node-b')->getNode('child-node-b1'));

        $expectedChildNodes = [$childNodeB, $childNodeC1];
        $actualChildNodes = $childNodeC->getChildNodes();
        $this->assertSameOrder($expectedChildNodes, array_values($actualChildNodes));
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

        $this->assertNull($parentNode->getNode('child-node-b'));
        $this->assertSame($childNodeB, $childNodeC->getNode('child-node-b'));
        $this->assertSame($childNodeB1, $childNodeC->getNode('child-node-b')->getNode('child-node-b1'));

        $expectedChildNodes = [$childNodeC1, $childNodeB];
        $actualChildNodes = $childNodeC->getChildNodes();
        $this->assertSameOrder($expectedChildNodes, array_values($actualChildNodes));
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

        $this->assertSameOrder($expectedChildNodes, $actualChildNodes);
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

        $this->assertSameOrder($expectedChildNodes, $actualChildNodes);
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

        $this->assertSameOrder($expectedChildNodes, $actualChildNodes);
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

        $this->assertSameOrder($expectedChildNodes, $actualChildNodes);
    }

    /**
     * @test
     * @expectedException \Neos\ContentRepository\Exception\NodeExistsException
     */
    public function moveBeforeThrowsExceptionIfTargetExists()
    {
        $rootNode = $this->context->getNode('/');

        $alfaNode = $rootNode->createNode('alfa');
        $alfaChildNode = $alfaNode->createNode('alfa');

        $alfaChildNode->moveBefore($alfaNode);
    }

    /**
     * @test
     * @expectedException \Neos\ContentRepository\Exception\NodeExistsException
     */
    public function moveAfterThrowsExceptionIfTargetExists()
    {
        $rootNode = $this->context->getNode('/');

        $alfaNode = $rootNode->createNode('alfa');
        $alfaChildNode = $alfaNode->createNode('alfa');

        $alfaChildNode->moveAfter($alfaNode);
    }

    /**
     * @test
     * @expectedException \Neos\ContentRepository\Exception\NodeExistsException
     */
    public function moveIntoThrowsExceptionIfTargetExists()
    {
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
        $this->persistenceManager->persistAll();
        $childNodeB->moveInto($childNodeA, 'renamed-child-node-b');
        $this->persistenceManager->persistAll();
        $this->assertNull($parentNode->getNode('child-node-b'));
        $this->assertSame($childNodeB, $childNodeA->getNode('renamed-child-node-b'));
        $this->assertSame($childNodeB1, $childNodeA->getNode('renamed-child-node-b')->getNode('child-node-b1'));
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
        $this->assertSameOrder($newNodeOrder, $actualChildNodes);
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
        $this->assertSameOrder($nodes, $actualChildNodes);
    }

    /**
     * @test
     */
    public function nodeDataRepositoryRenumbersNodesIfNoFreeSortingIndexesAreAvailableAcrossDimensions()
    {
        $this->contentDimensionRepository->setDimensionsConfiguration(array(
            'test' => array(
                'default' => 'a'
            )
        ));

        $variantContextA = $this->contextFactory->create(array(
            'dimensions' => array('test' => array('a')),
            'targetDimensions' => array('test' => 'a')
        ));
        $variantContextB = $this->contextFactory->create(array(
            'dimensions' => array('test' => array('b', 'a')),
            'targetDimensions' => array('test' => 'b')
        ));

        $rootNodeA = $variantContextA->getRootNode();
        $rootNodeB = $rootNodeA->createVariantForContext($variantContextB);

        $liveParentNodeA = $rootNodeA->createNode('parent-node');
        $liveParentNodeB = $liveParentNodeA->createVariantForContext($variantContextB);

        $nodesA = array();
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

        $this->assertSameOrder($nodesA, $actualChildNodesA);
        $this->assertSameOrder($nodesB, $actualChildNodesB);
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
        $this->assertTrue(true);
    }

    /**
     * @test
     */
    public function getLabelUsesFallbackExpression()
    {
        $node = $this->context->getNode('/');

        $this->assertEquals('unstructured ()', $node->getLabel());
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
        $this->assertNotSame($fluxNode, $flussNode);
        $this->assertNotSame($fluxNode, $bachNode);
        $this->assertEquals($fluxNode->getProperties(), $bachNode->getProperties());
        $this->assertEquals($fluxNode->getProperties(), $flussNode->getProperties());
        $this->persistenceManager->persistAll();

        $this->assertSame($bachNode, $rootNode->getNode('bach'));
        $this->assertSame($flussNode, $rootNode->getNode('fluss'));
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
        $this->assertSame(['fluss', 'baz', 'flux'], $names->names);
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
        $this->assertSame(['baz', 'fluss', 'flux'], $names->names);
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

        $this->assertSame(true, $alfaNode->getNode('charlie')->getProperty('test'));
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

        $this->assertSame(true, $bravoNode->getProperty('test'));
        $this->assertSame($bravoNode, $alfaNode->getNode('bravo'));
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
        $this->assertSame(['capacitor', 'second', 'third'], $names->names);
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
        $this->assertSame(['capacitor', 'second', 'third'], $names->names);
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

        $this->assertSame($alfaNode->getNode('delta'), $deltaNode);
        $this->assertSame($alfaNode->getNode('delta')->getProperty('test'), true);
        $this->assertSame($alfaNode->getNode('delta')->getNode('charlie')->getProperty('test2'), true);
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

        $this->assertSame($alfaNode->getNode('charlie'), $charlieNode);
        $this->assertSame($alfaNode->getNode('charlie')->getNode('bravo')->getProperty('test'), true);
    }

    /**
     * @test
     * @expectedException \Neos\ContentRepository\Exception\NodeExistsException
     */
    public function copyBeforeThrowsExceptionIfTargetExists()
    {
        $rootNode = $this->context->getNode('/');

        $rootNode->createNode('exists');
        $bazNode = $rootNode->createNode('baz');
        $fluxNode = $rootNode->createNode('flux');

        $fluxNode->copyBefore($bazNode, 'exists');
    }

    /**
     * @test
     * @expectedException \Neos\ContentRepository\Exception\NodeExistsException
     */
    public function copyAfterThrowsExceptionIfTargetExists()
    {
        $rootNode = $this->context->getNode('/');

        $rootNode->createNode('exists');
        $bazNode = $rootNode->createNode('baz');
        $fluxNode = $rootNode->createNode('flux');

        $fluxNode->copyAfter($bazNode, 'exists');
    }

    /**
     * @test
     * @expectedException \Neos\ContentRepository\Exception\NodeExistsException
     */
    public function copyIntoThrowsExceptionIfTargetExists()
    {
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
        $this->assertSame($nodeB, $nodeA->getProperty('property2'));

        $nodeA->setProperty('property2', $nodeB);
        $this->assertSame($nodeB, $nodeA->getProperty('property2'));
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
        $this->assertSame($expectedNodes, $nodeA->getProperty('property3'));

        $nodeA->setProperty('property3', $expectedNodes);
        $this->assertSame($expectedNodes, $nodeA->getProperty('property3'));
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
        $this->assertSame($nodeB, $actualProperties['property2']);
        $this->assertSame($expectedNodes, $actualProperties['property3']);
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

        $this->assertNull($nodeA->getProperty('property2'));
        $property3 = $nodeA->getProperty('property3');
        $this->assertCount(1, $property3);
        $this->assertSame($nodeC, reset($property3));
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
        $this->assertNotSame($referencedNodeProperty->getWorkspace(), $testReferencedNode->getWorkspace());
        $this->assertSame($referencedNodeProperty->getWorkspace(), $referencedNode->getWorkspace());

        $testReferencedNodeProperty = $testNode->getProperty('property2');
        $this->assertNotSame($testReferencedNodeProperty->getWorkspace(), $referencedNode->getWorkspace());
        $this->assertSame($testReferencedNodeProperty->getWorkspace(), $testReferencedNode->getWorkspace());
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

        $this->assertSame($variantNodeA1, $variantNodeA2);
        $this->assertSame($variantNodeA1, $variantNodeB);
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

        $this->assertSame($variantNodeB->getDimensions(), array_map(function ($value) {
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

        $this->assertSame($variantNodeB->getDimensions(), array_map(function ($value) {
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
        $this->assertSame($variantContextA->adoptNode($variantNodeA), $variantNodeA);

        // Different context with fallback
        $this->assertNotSame($variantContextB->adoptNode($variantNodeA)->getDimensions(), $variantNodeA->getDimensions(), 'Dimensions of $variantNodeA should change when adopted in $variantContextB');
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

        $this->assertSame($variantNodeB->getDimensions(), $variantContextB->getTargetDimensionValues());
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

        $this->assertSame($variantNodeA->getDimensions(), $variantNodeB->getDimensions());
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
        $this->assertInstanceOf(HappyNode::class, $happyNode);
        $this->assertNotInstanceOf(HappyNode::class, $bazNode);

        $this->assertEquals('bar claps hands!', $happyNode->clapsHands());
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
        $this->assertCount(1, $node->getChildNodes('Neos.ContentRepository.Testing:Headline'));
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
        $this->assertCount(2, $variantContextB->getRootNode()->getChildNodes());

        // This should NOT find the node created in variantContextA as
        // a better matching (with "b" dimension value) variant (same identifier) exists in container two
        $this->assertCount(0, $variantContextB->getNode('/container1')->getChildNodes());
        // This is the better matching variant and should be found.
        $this->assertCount(1, $variantContextB->getNode('/container2')->getChildNodes());
    }
}
