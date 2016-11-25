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
        $this->assertSame($nodeData, $node->getNodeData());
    }

    /**
     */
    protected function assertThatOriginalOrNewNodeIsCalled($methodName, $argument1 = null)
    {
        $propertyMapper = $this->getMockBuilder(PropertyMapper::class)->disableOriginalConstructor()->getMock();
        $propertyMapper->expects($this->any())->method('convert')->willReturnArgument(0);

        $userWorkspace = $this->getMockBuilder(Workspace::class)->disableOriginalConstructor()->getMock();
        $liveWorkspace = $this->getMockBuilder(Workspace::class)->disableOriginalConstructor()->getMock();
        $nodeType = $this->getMockBuilder(NodeType::class)->disableOriginalConstructor()->getMock();
        $nodeType->expects($this->any())->method('getPropertyType')->will($this->returnValue('string'));

        $originalNode = $this->getMockBuilder(NodeData::class)->disableOriginalConstructor()->getMock();
        $originalNode->expects($this->any())->method('getWorkspace')->will($this->returnValue($liveWorkspace));
        $originalNode->expects($this->any())->method('getNodeType')->will($this->returnValue($nodeType));
        if ($argument1 === null) {
            $originalNode->expects($this->any())->method($methodName)->will($this->returnValue('originalNodeResult'));
        } else {
            $originalNode->expects($this->any())->method($methodName)->with($argument1)->will($this->returnValue('originalNodeResult'));
        }

        $newNode = $this->getMockBuilder(NodeData::class)->disableOriginalConstructor()->getMock();
        $newNode->expects($this->any())->method('getWorkspace')->will($this->returnValue($userWorkspace));
        $newNode->expects($this->any())->method('getNodeType')->will($this->returnValue($nodeType));
        if ($argument1 === null) {
            $newNode->expects($this->any())->method($methodName)->will($this->returnValue('newNodeResult'));
        } else {
            $newNode->expects($this->any())->method($methodName)->with($argument1)->will($this->returnValue('newNodeResult'));
        }

        $context = $this->getMockBuilder(Context::class)->disableOriginalConstructor()->getMock();
        $context->expects($this->any())->method('getWorkspace')->will($this->returnValue($userWorkspace));

        $contextualizedNode = new Node($originalNode, $context);
        $this->inject($contextualizedNode, 'propertyMapper', $propertyMapper);

        $this->assertEquals('originalNodeResult', $contextualizedNode->$methodName($argument1));

        $contextualizedNode = new Node($newNode, $context);
        $this->inject($contextualizedNode, 'propertyMapper', $propertyMapper);

        $this->assertEquals('newNodeResult', $contextualizedNode->$methodName($argument1));
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
        $nodeData->expects($this->once())->method('getIdentifier')->will($this->returnValue('theidentifier'));

        $context = $this->getMockBuilder(Context::class)->disableOriginalConstructor()->getMock();

        $contextualizedNode = new Node($nodeData, $context);

        $this->assertEquals('theidentifier', $contextualizedNode->getIdentifier());
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

        $node->getNodeData()->expects($this->once())->method('setIndex')->with(5);

        $node->setIndex(5);
    }

    /**
     * @test
     */
    public function setPropertyOnNodeWithNonMatchingContextMaterializesNodeData()
    {
        $node = $this->setUpNodeWithNonMatchingContext();

        $node->getNodeData()->expects($this->once())->method('setProperty')->with('propertyName', 'value');

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

        $node->getNodeData()->expects($this->once())->method('setContentObject')->with($contentObject);

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

        $node->getNodeData()->expects($this->once())->method('unsetContentObject');

        $node->unsetContentObject();
    }

    /**
     * @test
     */
    public function setNodeTypeOnNodeWithNonMatchingContextMaterializesNodeData()
    {
        $nodeType = $this->getMockBuilder(NodeType::class)->disableOriginalConstructor()->getMock();

        $node = $this->setUpNodeWithNonMatchingContext();

        $node->getNodeData()->expects($this->once())->method('setNodeType')->with($nodeType);

        $node->setNodeType($nodeType);
    }

    /**
     * @test
     */
    public function removeCallsOnNodeWithNonMatchingContextMaterializesNodeData()
    {
        $node = $this->setUpNodeWithNonMatchingContext(array('getChildNodes'));

        $node->expects($this->once())->method('getChildNodes')->will($this->returnValue(array()));
        $node->getNodeData()->expects($this->once())->method('setRemoved');

        $node->remove();
    }

    /**
     * @test
     */
    public function removeRemovesAllChildNodesAndTheNodeItself()
    {
        $node = $this->setUpNodeWithNonMatchingContext(array('getChildNodes'));

        $nodeData = $node->getNodeData();
        $context = $node->getContext();

        $subNode1 = $this->getMockBuilder(Node::class)->setMethods(array('setRemoved'))->setConstructorArgs(array($nodeData, $context))->getMock();
        $subNode1->expects($this->once())->method('setRemoved');

        $subNode2 = $this->getMockBuilder(Node::class)->setMethods(array('setRemoved'))->setConstructorArgs(array($nodeData, $context))->getMock();
        $subNode2->expects($this->once())->method('setRemoved');

        $node->expects($this->once())->method('getChildNodes')->will($this->returnValue(array($subNode1, $subNode2)));
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
        $context->expects($this->any())->method('getWorkspace')->will($this->returnValue($currentNodeWorkspace));
        $context->expects($this->any())->method('getFirstLevelNodeCache')->will($this->returnValue($mockFirstLevelNodeCache));

        $expectedParentNodeData = new NodeData('/foo', $currentNodeWorkspace);
        $expectedContextualizedParentNode = new Node($expectedParentNodeData, $context);

        $nodeDataRepository = $this->getMockBuilder(NodeDataRepository::class)->disableOriginalConstructor()->setMethods(array('findOneByPathInContext'))->getMock();
        $nodeDataRepository->expects($this->once())->method('findOneByPathInContext')->with('/foo', $context)->will($this->returnValue($expectedContextualizedParentNode));

        $currentNodeData = $this->getMockBuilder(NodeData::class)->setConstructorArgs(array('/foo/baz', $currentNodeWorkspace))->getMock();
        $currentContextualizedNode = $this->getAccessibleMock(Node::class, array('getParentPath'), array($currentNodeData, $context));
        $currentContextualizedNode->expects($this->once())->method('getParentPath')->will($this->returnValue('/foo'));
        $currentContextualizedNode->_set('nodeDataRepository', $nodeDataRepository);

        $actualParentNode = $currentContextualizedNode->getParent();
        $this->assertSame($expectedContextualizedParentNode, $actualParentNode);
    }

    /**
     * @test
     */
    public function getNodeReturnsTheSpecifiedNodeInTheCurrentNodesContext()
    {
        $currentNodeWorkspace = $this->getMockBuilder(Workspace::class)->disableOriginalConstructor()->getMock();

        $mockFirstLevelNodeCache = $this->getFirstLevelNodeCache();

        $context = $this->getMockBuilder(Context::class)->disableOriginalConstructor()->getMock();
        $context->expects($this->any())->method('getWorkspace')->will($this->returnValue($currentNodeWorkspace));
        $context->expects($this->any())->method('getFirstLevelNodeCache')->will($this->returnValue($mockFirstLevelNodeCache));

        $expectedNodeData = $this->getMockBuilder(NodeData::class)->setConstructorArgs(array('/foo/bar', $currentNodeWorkspace))->getMock();
        $expectedContextualizedNode = new Node($expectedNodeData, $context);

        $nodeDataRepository = $this->getMockBuilder(NodeDataRepository::class)->disableOriginalConstructor()->setMethods(array('findOneByPathInContext'))->getMock();
        $nodeDataRepository->expects($this->once())->method('findOneByPathInContext')->with('/foo/bar', $context)->will($this->returnValue($expectedContextualizedNode));

        $currentNodeData = $this->getMockBuilder(NodeData::class)->setMethods(array('dummy'))->setConstructorArgs(array('/foo/baz', $currentNodeWorkspace))->getMock();
        $nodeService = $this->getMockBuilder(NodeService::class)->disableOriginalConstructor()->getMock();
        $nodeService->expects($this->once())->method('normalizePath')->with('../bar', '/foo/baz')->will($this->returnValue('/foo/bar'));
        $currentContextualizedNode = $this->getAccessibleMock(Node::class, array('dummy'), array($currentNodeData, $context));
        $currentContextualizedNode->_set('nodeDataRepository', $nodeDataRepository);
        $currentContextualizedNode->_set('nodeService', $nodeService);

        $actualNode = $currentContextualizedNode->getNode('../bar');
        $this->assertSame($expectedContextualizedNode, $actualNode);
    }

    /**
     * @param array $configurableMethods
     * @return Node
     */
    protected function setUpNodeWithNonMatchingContext(array $configurableMethods = array())
    {
        $userWorkspace = $this->getMockBuilder(Workspace::class)->disableOriginalConstructor()->getMock();
        $userWorkspace->expects($this->any())->method('getName')->will($this->returnValue('user'));
        $liveWorkspace = $this->getMockBuilder(Workspace::class)->disableOriginalConstructor()->getMock();
        $liveWorkspace->expects($this->any())->method('getName')->will($this->returnValue('live'));
        $liveWorkspace->expects($this->any())->method('getBaseWorkspace')->will($this->returnValue(null));

        $nodeData = $this->getMockBuilder(NodeData::class)->disableOriginalConstructor()->getMock();
        $nodeData->expects($this->any())->method('getWorkspace')->will($this->returnValue($liveWorkspace));

        $mockFirstLevelNodeCache = $this->createMock(FirstLevelNodeCache::class);

        $context = $this->getMockBuilder(Context::class)->disableOriginalConstructor()->getMock();
        $context->expects($this->any())->method('getWorkspace')->will($this->returnValue($userWorkspace));
        $context->expects($this->any())->method('getTargetDimensions')->will($this->returnValue(array()));
        $context->expects($this->any())->method('getFirstLevelNodeCache')->will($this->returnValue($mockFirstLevelNodeCache));

        $node = $this->getMockBuilder(Node::class)->setMethods(array_merge(array('materializeNodeData'), $configurableMethods))->setConstructorArgs(array($nodeData, $context))->getMock();
        $node->expects($this->once())->method('materializeNodeData');
        return $node;
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    protected function getFirstLevelNodeCache()
    {
        $mockFirstLevelNodeCache = $this->createMock(FirstLevelNodeCache::class);
        $mockFirstLevelNodeCache->expects($this->any())->method('getByPath')->will($this->returnValue(false));
        $mockFirstLevelNodeCache->expects($this->any())->method('getByIdentifier')->will($this->returnValue(false));
        $mockFirstLevelNodeCache->expects($this->any())->method('getChildNodesByPathAndNodeTypeFilter')->will($this->returnValue(false));
        return $mockFirstLevelNodeCache;
    }
}
