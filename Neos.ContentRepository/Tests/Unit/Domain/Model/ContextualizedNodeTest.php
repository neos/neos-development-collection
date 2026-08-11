<?php
namespace Neos\ContentRepository\Tests\Unit\Domain\Model;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */
use PHPUnit\Framework\Attributes\Test;
use Neos\Flow\Property\PropertyMapper;
use Neos\Flow\Tests\UnitTestCase;
use Neos\ContentRepository\Domain\Model\Node;
use Neos\ContentRepository\Domain\Model\NodeData;
use Neos\ContentRepository\Domain\Model\NodeType;
use Neos\ContentRepository\Domain\Model\Workspace;
use Neos\ContentRepository\Domain\Repository\NodeDataRepository;
use Neos\ContentRepository\Domain\Service\Cache\FirstLevelNodeCache;
use Neos\ContentRepository\Domain\Service\Context;
use Neos\ContentRepository\Domain\Service\NodeService;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Test case for the "Node" domain model
 */
class ContextualizedNodeTest extends UnitTestCase
{
    /**
     * @var Node
     */
    protected $contextualizedNode;

    /**
     * @var Node
     */
    protected $newNode;

    #[Test]
    public function aContextualizedNodeIsRelatedToNodeData()
    {
        $context = $this->getMockBuilder(Context::class)->disableOriginalConstructor()->getMock();
        $nodeData = $this->getMockBuilder(NodeData::class)->disableOriginalConstructor()->getMock();
        $node = new Node($nodeData, $context);
        self::assertSame($nodeData, $node->getNodeData());
    }

    /**
     * @param $methodName
     * @param null $argument1
     */
    protected function assertThatOriginalOrNewNodeIsCalled($methodName, $argument1 = null)
    {
        $propertyMapper = $this->getMockBuilder(PropertyMapper::class)->disableOriginalConstructor()->getMock();
        $propertyMapper->expects(self::any())->method('convert')->willReturnArgument(0);

        $userWorkspace = $this->getMockBuilder(Workspace::class)->disableOriginalConstructor()->getMock();
        $liveWorkspace = $this->getMockBuilder(Workspace::class)->disableOriginalConstructor()->getMock();
        $nodeType = $this->getMockBuilder(NodeType::class)->disableOriginalConstructor()->getMock();
        $nodeType->expects(self::any())->method('getPropertyType')->willReturn('string');

        $originalNode = $this->getMockBuilder(NodeData::class)->disableOriginalConstructor()->getMock();
        $originalNode->expects(self::any())->method('getWorkspace')->willReturn($liveWorkspace);
        $originalNode->expects(self::any())->method('getNodeType')->willReturn($nodeType);
        if ($methodName === 'hasProperty') {
            if ($argument1 === null) {
                $originalNode->expects(self::any())->method($methodName)->willReturn(true);
            } else {
                $originalNode->expects(self::any())->method($methodName)->with($argument1)->willReturn(true);
            }
        } else {
            if ($argument1 === null) {
                $originalNode->expects(self::any())->method($methodName)->willReturn('originalNodeResult');
            } else {
                $originalNode->expects(self::any())->method($methodName)->with($argument1)->willReturn('originalNodeResult');
            }
        }


        $newNode = $this->getMockBuilder(NodeData::class)->disableOriginalConstructor()->getMock();
        $newNode->expects(self::any())->method('getWorkspace')->willReturn($userWorkspace);
        $newNode->expects(self::any())->method('getNodeType')->willReturn($nodeType);
        if ($methodName === 'hasProperty') {
            if ($argument1 === null) {
                $newNode->expects(self::any())->method($methodName)->willReturn(false);
            } else {
                $newNode->expects(self::any())->method($methodName)->with($argument1)->willReturn(false);
            }
        } else {
            if ($argument1 === null) {
                $newNode->expects(self::any())->method($methodName)->willReturn('newNodeResult');
            } else {
                $newNode->expects(self::any())->method($methodName)->with($argument1)->willReturn('newNodeResult');
            }
        }

        $context = $this->getMockBuilder(Context::class)->disableOriginalConstructor()->getMock();
        $context->expects(self::any())->method('getWorkspace')->willReturn($userWorkspace);

        $contextualizedNode = new Node($originalNode, $context);
        $this->inject($contextualizedNode, 'propertyMapper', $propertyMapper);

        if ($methodName === 'hasProperty') {
            self::assertEquals(true, $contextualizedNode->$methodName($argument1));
        } else {
            self::assertEquals('originalNodeResult', $contextualizedNode->$methodName($argument1));
        }

        $contextualizedNode = new Node($newNode, $context);
        $this->inject($contextualizedNode, 'propertyMapper', $propertyMapper);

        if ($methodName === 'hasProperty') {
            self::assertEquals(false, $contextualizedNode->$methodName($argument1));
        } else {
            self::assertEquals('newNodeResult', $contextualizedNode->$methodName($argument1));
        }
    }

    #[Test]
    public function getPathRetrievesThePathFromTheOriginalOrNewNode()
    {
        $this->assertThatOriginalOrNewNodeIsCalled('getPath');
    }

    #[Test]
    public function getDepthRetrievesTheDepthFromTheOriginalOrNewNode()
    {
        $this->assertThatOriginalOrNewNodeIsCalled('getDepth');
    }

    #[Test]
    public function getNameRetrievesTheNameFromTheOriginalOrNewNode()
    {
        $this->assertThatOriginalOrNewNodeIsCalled('getName');
    }

    #[Test]
    public function getIdentifierReturnsTheIdentifier()
    {
        $nodeData = $this->getMockBuilder(NodeData::class)->disableOriginalConstructor()->getMock();
        $nodeData->expects(self::once())->method('getIdentifier')->willReturn('theidentifier');

        $context = $this->getMockBuilder(Context::class)->disableOriginalConstructor()->getMock();

        $contextualizedNode = new Node($nodeData, $context);

        self::assertEquals('theidentifier', $contextualizedNode->getIdentifier());
    }

    #[Test]
    public function getIndexRetrievesTheIndexFromTheOriginalOrNewNode()
    {
        $this->assertThatOriginalOrNewNodeIsCalled('getIndex');
    }

    #[Test]
    public function getParentRetrievesTheParentNodeFromTheOriginalOrNewNode()
    {
        $this->markTestSkipped();
        $this->assertThatOriginalOrNewNodeIsCalled('getParent');
    }

    #[Test]
    public function setIndexOnNodeWithNonMatchingContextMaterializesNodeData()
    {
        $node = $this->setUpNodeWithNonMatchingContext();
        $node->expects(self::once())->method('materializeNodeData');

        $node->getNodeData()->expects(self::once())->method('setIndex')->with(5);

        $node->setIndex(5);
    }

    #[Test]
    public function setPropertyOnNodeWithNonMatchingContextMaterializesNodeData()
    {
        $node = $this->setUpNodeWithNonMatchingContext();
        $node->expects(self::once())->method('materializeNodeDataAsNeeded');

        $node->getNodeData()->expects(self::once())->method('setProperty')->with('propertyName', 'value');

        $node->setProperty('propertyName', 'value');
    }

    #[Test]
    public function hasPropertyCallsHasPropertyOnTheParentNodeFromTheOriginalOrNewNode()
    {
        $this->assertThatOriginalOrNewNodeIsCalled('hasProperty', 'myProperty');
    }

    #[Test]
    public function getPropertyCallsGetPropertyOnTheParentNodeFromTheOriginalOrNewNode()
    {
        $this->assertThatOriginalOrNewNodeIsCalled('getProperty', 'myProperty');
    }

    #[Test]
    public function getPropertyNamesCallsGetPropertyNamesOnTheParentNodeFromTheOriginalOrNewNode()
    {
        $this->assertThatOriginalOrNewNodeIsCalled('getPropertyNames');
    }

    #[Test]
    public function setContentObjectOnNodeWithNonMatchingContextMaterializesNodeData()
    {
        $contentObject = new \stdClass();

        $node = $this->setUpNodeWithNonMatchingContext();
        $node->expects(self::once())->method('materializeNodeDataAsNeeded');

        $node->getNodeData()->expects(self::once())->method('setContentObject')->with($contentObject);

        $node->setContentObject($contentObject);
    }

    #[Test]
    public function getContentObjectCallsGetContentObjectOnTheParentNodeFromTheOriginalOrNewNode()
    {
        $this->assertThatOriginalOrNewNodeIsCalled('getContentObject');
    }

    #[Test]
    public function unsetContentObjectOnNodeWithNonMatchingContextMaterializesNodeData()
    {
        $node = $this->setUpNodeWithNonMatchingContext();
        $node->expects(self::once())->method('materializeNodeDataAsNeeded');

        $node->getNodeData()->expects(self::once())->method('getContentObject')->willReturn(new \stdClass());
        $node->getNodeData()->expects(self::once())->method('unsetContentObject');

        $node->unsetContentObject();
    }

    #[Test]
    public function setNodeTypeOnNodeWithNonMatchingContextMaterializesNodeData()
    {
        $nodeType = $this->getMockBuilder(NodeType::class)->disableOriginalConstructor()->getMock();

        $node = $this->setUpNodeWithNonMatchingContext();
        $node->expects(self::once())->method('materializeNodeData');

        $node->getNodeData()->expects(self::once())->method('setNodeType')->with($nodeType);

        $node->setNodeType($nodeType);
    }

    #[Test]
    public function removeCallsOnNodeWithNonMatchingContextMaterializesNodeData()
    {
        $node = $this->setUpNodeWithNonMatchingContext(['getChildNodes']);

        $node->expects(self::once())->method('getChildNodes')->willReturn([]);
        $node->getNodeData()->expects(self::once())->method('setRemoved');

        $node->remove();
    }

    #[Test]
    public function removeRemovesAllChildNodesAndTheNodeItself()
    {
        $node = $this->setUpNodeWithNonMatchingContext(['getChildNodes']);

        $nodeData = $node->getNodeData();
        $context = $node->getContext();

        $subNode1 = $this->getMockBuilder(Node::class)->onlyMethods(['setRemoved'])->setConstructorArgs([$nodeData, $context])->getMock();
        $subNode1->expects(self::once())->method('setRemoved');

        $subNode2 = $this->getMockBuilder(Node::class)->onlyMethods(['setRemoved'])->setConstructorArgs([$nodeData, $context])->getMock();
        $subNode2->expects(self::once())->method('setRemoved');

        $node->expects(self::once())->method('getChildNodes')->willReturn([$subNode1, $subNode2]);
        $node->remove();
    }

    #[Test]
    public function getParentReturnsParentNodeInCurrentNodesContext()
    {
        $currentNodeWorkspace = $this->getMockBuilder(Workspace::class)->disableOriginalConstructor()->getMock();

        $mockFirstLevelNodeCache = $this->getFirstLevelNodeCache();

        $context = $this->getMockBuilder(Context::class)->disableOriginalConstructor()->getMock();
        $context->expects(self::any())->method('getWorkspace')->willReturn($currentNodeWorkspace);
        $context->expects(self::any())->method('getFirstLevelNodeCache')->willReturn($mockFirstLevelNodeCache);

        $expectedParentNodeData = new NodeData('/foo', $currentNodeWorkspace);
        $expectedContextualizedParentNode = new Node($expectedParentNodeData, $context);

        $nodeDataRepository = $this->getMockBuilder(NodeDataRepository::class)->disableOriginalConstructor()->onlyMethods(['findOneByPathInContext'])->getMock();
        $nodeDataRepository->expects(self::once())->method('findOneByPathInContext')->with('/foo', $context)->willReturn($expectedContextualizedParentNode);

        $currentNodeData = $this->getMockBuilder(NodeData::class)->setConstructorArgs(['/foo/baz', $currentNodeWorkspace])->getMock();
        $currentContextualizedNode = $this->getAccessibleMock(Node::class, ['getParentPath'], [$currentNodeData, $context]);
        $currentContextualizedNode->expects(self::once())->method('getParentPath')->willReturn('/foo');
        $currentContextualizedNode->_set('nodeDataRepository', $nodeDataRepository);

        $actualParentNode = $currentContextualizedNode->getParent();
        self::assertSame($expectedContextualizedParentNode, $actualParentNode);
    }

    #[Test]
    public function getNodeReturnsTheSpecifiedNodeInTheCurrentNodesContext()
    {
        $currentNodeWorkspace = $this->getMockBuilder(Workspace::class)->disableOriginalConstructor()->getMock();

        $mockFirstLevelNodeCache = $this->getFirstLevelNodeCache();

        $context = $this->getMockBuilder(Context::class)->disableOriginalConstructor()->getMock();
        $context->expects(self::any())->method('getWorkspace')->willReturn($currentNodeWorkspace);
        $context->expects(self::any())->method('getFirstLevelNodeCache')->willReturn($mockFirstLevelNodeCache);

        $expectedNodeData = $this->getMockBuilder(NodeData::class)->setConstructorArgs(['/foo/bar', $currentNodeWorkspace])->getMock();
        $expectedContextualizedNode = new Node($expectedNodeData, $context);

        $nodeDataRepository = $this->getMockBuilder(NodeDataRepository::class)->disableOriginalConstructor()->onlyMethods(['findOneByPathInContext'])->getMock();
        $nodeDataRepository->expects(self::once())->method('findOneByPathInContext')->with('/foo/bar', $context)->willReturn($expectedContextualizedNode);

        $currentNodeData = $this->getMockBuilder(NodeData::class)->addMethods(['dummy'])->setConstructorArgs(['/foo/baz', $currentNodeWorkspace])->getMock();
        $nodeService = $this->getMockBuilder(NodeService::class)->disableOriginalConstructor()->getMock();
        $nodeService->expects(self::once())->method('normalizePath')->with('../bar', '/foo/baz')->willReturn('/foo/bar');
        $currentContextualizedNode = $this->getAccessibleMock(Node::class, ['dummy'], [$currentNodeData, $context]);
        $currentContextualizedNode->_set('nodeDataRepository', $nodeDataRepository);
        $currentContextualizedNode->_set('nodeService', $nodeService);

        $actualNode = $currentContextualizedNode->getNode('../bar');
        self::assertSame($expectedContextualizedNode, $actualNode);
    }

    /**
     * @param array $configurableMethods
     * @return Node
     */
    protected function setUpNodeWithNonMatchingContext(array $configurableMethods = [])
    {
        $userWorkspace = $this->getMockBuilder(Workspace::class)->disableOriginalConstructor()->getMock();
        $userWorkspace->expects(self::any())->method('getName')->willReturn('user');
        $liveWorkspace = $this->getMockBuilder(Workspace::class)->disableOriginalConstructor()->getMock();
        $liveWorkspace->expects(self::any())->method('getName')->willReturn('live');
        $liveWorkspace->expects(self::any())->method('getBaseWorkspace')->willReturn(null);

        $nodeData = $this->getMockBuilder(NodeData::class)->disableOriginalConstructor()->getMock();
        $nodeData->expects(self::any())->method('getWorkspace')->willReturn($liveWorkspace);
        $nodeData->expects(self::any())->method('hasProperty')->willReturn(true);

        $mockFirstLevelNodeCache = $this->createMock(FirstLevelNodeCache::class);

        $context = $this->getMockBuilder(Context::class)->disableOriginalConstructor()->getMock();
        $context->expects(self::any())->method('getWorkspace')->willReturn($userWorkspace);
        $context->expects(self::any())->method('getTargetDimensions')->willReturn([]);
        $context->expects(self::any())->method('getFirstLevelNodeCache')->willReturn($mockFirstLevelNodeCache);

        /** @var Node|MockObject $node */
        $node = $this->getMockBuilder(Node::class)->onlyMethods(array_merge(['materializeNodeData', 'materializeNodeDataAsNeeded', 'getNodeType'], $configurableMethods))->setConstructorArgs([$nodeData, $context])->getMock();
        return $node;
    }

    /**
     * @return MockObject
     */
    protected function getFirstLevelNodeCache()
    {
        $mockFirstLevelNodeCache = $this->createMock(FirstLevelNodeCache::class);
        $mockFirstLevelNodeCache->expects(self::any())->method('getByPath')->willReturn(false);
        $mockFirstLevelNodeCache->expects(self::any())->method('getByIdentifier')->willReturn(false);
        $mockFirstLevelNodeCache->expects(self::any())->method('getChildNodesByPathAndNodeTypeFilter')->willReturn(false);
        return $mockFirstLevelNodeCache;
    }
}
