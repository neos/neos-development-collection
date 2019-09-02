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

    /**
     * @test
     */
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
        $nodeType->expects(self::any())->method('getPropertyType')->will(self::returnValue('string'));

        $originalNode = $this->getMockBuilder(NodeData::class)->disableOriginalConstructor()->getMock();
        $originalNode->expects(self::any())->method('getWorkspace')->will(self::returnValue($liveWorkspace));
        $originalNode->expects(self::any())->method('getNodeType')->will(self::returnValue($nodeType));
        if ($methodName === 'hasProperty') {
            if ($argument1 === null) {
                $originalNode->expects(self::any())->method($methodName)->will(self::returnValue(true));
            } else {
                $originalNode->expects(self::any())->method($methodName)->with($argument1)->will(self::returnValue(true));
            }
        } else {
            if ($argument1 === null) {
                $originalNode->expects(self::any())->method($methodName)->will(self::returnValue('originalNodeResult'));
            } else {
                $originalNode->expects(self::any())->method($methodName)->with($argument1)->will(self::returnValue('originalNodeResult'));
            }
        }


        $newNode = $this->getMockBuilder(NodeData::class)->disableOriginalConstructor()->getMock();
        $newNode->expects(self::any())->method('getWorkspace')->will(self::returnValue($userWorkspace));
        $newNode->expects(self::any())->method('getNodeType')->will(self::returnValue($nodeType));
        if ($methodName === 'hasProperty') {
            if ($argument1 === null) {
                $newNode->expects(self::any())->method($methodName)->will(self::returnValue(false));
            } else {
                $newNode->expects(self::any())->method($methodName)->with($argument1)->will(self::returnValue(false));
            }
        } else {
            if ($argument1 === null) {
                $newNode->expects(self::any())->method($methodName)->will(self::returnValue('newNodeResult'));
            } else {
                $newNode->expects(self::any())->method($methodName)->with($argument1)->will(self::returnValue('newNodeResult'));
            }
        }

        $context = $this->getMockBuilder(Context::class)->disableOriginalConstructor()->getMock();
        $context->expects(self::any())->method('getWorkspace')->will(self::returnValue($userWorkspace));

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

    /**
     * @test
     */
    public function getPathRetrievesThePathFromTheOriginalOrNewNode()
    {
        $this->assertThatOriginalOrNewNodeIsCalled('getPath');
    }

    /**
     * @test
     */
    public function getDepthRetrievesTheDepthFromTheOriginalOrNewNode()
    {
        $this->assertThatOriginalOrNewNodeIsCalled('getDepth');
    }

    /**
     * @test
     */
    public function getNameRetrievesTheNameFromTheOriginalOrNewNode()
    {
        $this->assertThatOriginalOrNewNodeIsCalled('getName');
    }

    /**
     * @test
     */
    public function getIdentifierReturnsTheIdentifier()
    {
        $nodeData = $this->getMockBuilder(NodeData::class)->disableOriginalConstructor()->getMock();
        $nodeData->expects(self::once())->method('getIdentifier')->will(self::returnValue('theidentifier'));

        $context = $this->getMockBuilder(Context::class)->disableOriginalConstructor()->getMock();

        $contextualizedNode = new Node($nodeData, $context);

        self::assertEquals('theidentifier', $contextualizedNode->getIdentifier());
    }

    /**
     * @test
     */
    public function getIndexRetrievesTheIndexFromTheOriginalOrNewNode()
    {
        $this->assertThatOriginalOrNewNodeIsCalled('getIndex');
    }

    /**
     * @test
     */
    public function getParentRetrievesTheParentNodeFromTheOriginalOrNewNode()
    {
        $this->markTestSkipped();
        $this->assertThatOriginalOrNewNodeIsCalled('getParent');
    }

    /**
     * @test
     */
    public function setIndexOnNodeWithNonMatchingContextMaterializesNodeData()
    {
        $node = $this->setUpNodeWithNonMatchingContext();
        $node->expects(self::once())->method('materializeNodeData');

        $node->getNodeData()->expects(self::once())->method('setIndex')->with(5);

        $node->setIndex(5);
    }

    /**
     * @test
     */
    public function setPropertyOnNodeWithNonMatchingContextMaterializesNodeData()
    {
        $node = $this->setUpNodeWithNonMatchingContext();
        $node->expects(self::once())->method('materializeNodeDataAsNeeded');

        $node->getNodeData()->expects(self::once())->method('setProperty')->with('propertyName', 'value');

        $node->setProperty('propertyName', 'value');
    }

    /**
     * @test
     */
    public function hasPropertyCallsHasPropertyOnTheParentNodeFromTheOriginalOrNewNode()
    {
        $this->assertThatOriginalOrNewNodeIsCalled('hasProperty', 'myProperty');
    }

    /**
     * @test
     */
    public function getPropertyCallsGetPropertyOnTheParentNodeFromTheOriginalOrNewNode()
    {
        $this->assertThatOriginalOrNewNodeIsCalled('getProperty', 'myProperty');
    }

    /**
     * @test
     */
    public function getPropertyNamesCallsGetPropertyNamesOnTheParentNodeFromTheOriginalOrNewNode()
    {
        $this->assertThatOriginalOrNewNodeIsCalled('getPropertyNames');
    }

    /**
     * @test
     */
    public function setContentObjectOnNodeWithNonMatchingContextMaterializesNodeData()
    {
        $contentObject = new \stdClass();

        $node = $this->setUpNodeWithNonMatchingContext();
        $node->expects(self::once())->method('materializeNodeDataAsNeeded');

        $node->getNodeData()->expects(self::once())->method('setContentObject')->with($contentObject);

        $node->setContentObject($contentObject);
    }

    /**
     * @test
     */
    public function getContentObjectCallsGetContentObjectOnTheParentNodeFromTheOriginalOrNewNode()
    {
        $this->assertThatOriginalOrNewNodeIsCalled('getContentObject');
    }

    /**
     * @test
     */
    public function unsetContentObjectOnNodeWithNonMatchingContextMaterializesNodeData()
    {
        $node = $this->setUpNodeWithNonMatchingContext();
        $node->expects(self::once())->method('materializeNodeDataAsNeeded');

        $node->getNodeData()->expects(self::once())->method('getContentObject')->will(self::returnValue(new \stdClass()));
        $node->getNodeData()->expects(self::once())->method('unsetContentObject');

        $node->unsetContentObject();
    }

    /**
     * @test
     */
    public function setNodeTypeOnNodeWithNonMatchingContextMaterializesNodeData()
    {
        $nodeType = $this->getMockBuilder(NodeType::class)->disableOriginalConstructor()->getMock();

        $node = $this->setUpNodeWithNonMatchingContext();
        $node->expects(self::once())->method('materializeNodeData');

        $node->getNodeData()->expects(self::once())->method('setNodeType')->with($nodeType);

        $node->setNodeType($nodeType);
    }

    /**
     * @test
     */
    public function removeCallsOnNodeWithNonMatchingContextMaterializesNodeData()
    {
        $node = $this->setUpNodeWithNonMatchingContext(['getChildNodes']);

        $node->expects(self::once())->method('getChildNodes')->will(self::returnValue([]));
        $node->getNodeData()->expects(self::once())->method('setRemoved');

        $node->remove();
    }

    /**
     * @test
     */
    public function removeRemovesAllChildNodesAndTheNodeItself()
    {
        $node = $this->setUpNodeWithNonMatchingContext(['getChildNodes']);

        $nodeData = $node->getNodeData();
        $context = $node->getContext();

        $subNode1 = $this->getMockBuilder(Node::class)->setMethods(['setRemoved'])->setConstructorArgs([$nodeData, $context])->getMock();
        $subNode1->expects(self::once())->method('setRemoved');

        $subNode2 = $this->getMockBuilder(Node::class)->setMethods(['setRemoved'])->setConstructorArgs([$nodeData, $context])->getMock();
        $subNode2->expects(self::once())->method('setRemoved');

        $node->expects(self::once())->method('getChildNodes')->will(self::returnValue([$subNode1, $subNode2]));
        $node->remove();
    }

    /**
     * @test
     */
    public function getParentReturnsParentNodeInCurrentNodesContext()
    {
        $currentNodeWorkspace = $this->getMockBuilder(Workspace::class)->disableOriginalConstructor()->getMock();

        $mockFirstLevelNodeCache = $this->getFirstLevelNodeCache();

        $context = $this->getMockBuilder(Context::class)->disableOriginalConstructor()->getMock();
        $context->expects(self::any())->method('getWorkspace')->will(self::returnValue($currentNodeWorkspace));
        $context->expects(self::any())->method('getFirstLevelNodeCache')->will(self::returnValue($mockFirstLevelNodeCache));

        $expectedParentNodeData = new NodeData('/foo', $currentNodeWorkspace);
        $expectedContextualizedParentNode = new Node($expectedParentNodeData, $context);

        $nodeDataRepository = $this->getMockBuilder(NodeDataRepository::class)->disableOriginalConstructor()->setMethods(['findOneByPathInContext'])->getMock();
        $nodeDataRepository->expects(self::once())->method('findOneByPathInContext')->with('/foo', $context)->will(self::returnValue($expectedContextualizedParentNode));

        $currentNodeData = $this->getMockBuilder(NodeData::class)->setConstructorArgs(['/foo/baz', $currentNodeWorkspace])->getMock();
        $currentContextualizedNode = $this->getAccessibleMock(Node::class, ['getParentPath'], [$currentNodeData, $context]);
        $currentContextualizedNode->expects(self::once())->method('getParentPath')->will(self::returnValue('/foo'));
        $currentContextualizedNode->_set('nodeDataRepository', $nodeDataRepository);

        $actualParentNode = $currentContextualizedNode->getParent();
        self::assertSame($expectedContextualizedParentNode, $actualParentNode);
    }

    /**
     * @test
     */
    public function getNodeReturnsTheSpecifiedNodeInTheCurrentNodesContext()
    {
        $currentNodeWorkspace = $this->getMockBuilder(Workspace::class)->disableOriginalConstructor()->getMock();

        $mockFirstLevelNodeCache = $this->getFirstLevelNodeCache();

        $context = $this->getMockBuilder(Context::class)->disableOriginalConstructor()->getMock();
        $context->expects(self::any())->method('getWorkspace')->will(self::returnValue($currentNodeWorkspace));
        $context->expects(self::any())->method('getFirstLevelNodeCache')->will(self::returnValue($mockFirstLevelNodeCache));

        $expectedNodeData = $this->getMockBuilder(NodeData::class)->setConstructorArgs(['/foo/bar', $currentNodeWorkspace])->getMock();
        $expectedContextualizedNode = new Node($expectedNodeData, $context);

        $nodeDataRepository = $this->getMockBuilder(NodeDataRepository::class)->disableOriginalConstructor()->setMethods(['findOneByPathInContext'])->getMock();
        $nodeDataRepository->expects(self::once())->method('findOneByPathInContext')->with('/foo/bar', $context)->will(self::returnValue($expectedContextualizedNode));

        $currentNodeData = $this->getMockBuilder(NodeData::class)->setMethods(['dummy'])->setConstructorArgs(['/foo/baz', $currentNodeWorkspace])->getMock();
        $nodeService = $this->getMockBuilder(NodeService::class)->disableOriginalConstructor()->getMock();
        $nodeService->expects(self::once())->method('normalizePath')->with('../bar', '/foo/baz')->will(self::returnValue('/foo/bar'));
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
        $userWorkspace->expects(self::any())->method('getName')->will(self::returnValue('user'));
        $liveWorkspace = $this->getMockBuilder(Workspace::class)->disableOriginalConstructor()->getMock();
        $liveWorkspace->expects(self::any())->method('getName')->will(self::returnValue('live'));
        $liveWorkspace->expects(self::any())->method('getBaseWorkspace')->will(self::returnValue(null));

        $nodeData = $this->getMockBuilder(NodeData::class)->disableOriginalConstructor()->getMock();
        $nodeData->expects(self::any())->method('getWorkspace')->will(self::returnValue($liveWorkspace));
        $nodeData->expects(self::any())->method('hasProperty')->will(self::returnValue(true));

        $mockFirstLevelNodeCache = $this->createMock(FirstLevelNodeCache::class);

        $context = $this->getMockBuilder(Context::class)->disableOriginalConstructor()->getMock();
        $context->expects(self::any())->method('getWorkspace')->will(self::returnValue($userWorkspace));
        $context->expects(self::any())->method('getTargetDimensions')->will(self::returnValue([]));
        $context->expects(self::any())->method('getFirstLevelNodeCache')->will(self::returnValue($mockFirstLevelNodeCache));

        /** @var Node|MockObject $node */
        $node = $this->getMockBuilder(Node::class)->setMethods(array_merge(['materializeNodeData', 'materializeNodeDataAsNeeded', 'getNodeType'], $configurableMethods))->setConstructorArgs([$nodeData, $context])->getMock();
        return $node;
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject
     */
    protected function getFirstLevelNodeCache()
    {
        $mockFirstLevelNodeCache = $this->createMock(FirstLevelNodeCache::class);
        $mockFirstLevelNodeCache->expects(self::any())->method('getByPath')->will(self::returnValue(false));
        $mockFirstLevelNodeCache->expects(self::any())->method('getByIdentifier')->will(self::returnValue(false));
        $mockFirstLevelNodeCache->expects(self::any())->method('getChildNodesByPathAndNodeTypeFilter')->will(self::returnValue(false));
        return $mockFirstLevelNodeCache;
    }
}
