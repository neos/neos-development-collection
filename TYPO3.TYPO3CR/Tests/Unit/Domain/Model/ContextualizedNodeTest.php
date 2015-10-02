<?php
namespace TYPO3\TYPO3CR\Tests\Unit\Domain\Model;

/*
 * This file is part of the TYPO3.TYPO3CR package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

/**
 * Test case for the "Node" domain model
 */
class ContextualizedNodeTest extends \TYPO3\Flow\Tests\UnitTestCase
{
    /**
     * @var \TYPO3\TYPO3CR\Domain\Model\Node
     */
    protected $contextualizedNode;

    /**
     * @var \TYPO3\TYPO3CR\Domain\Model\Node
     */
    protected $newNode;

    /**
     * @test
     */
    public function aContextualizedNodeIsRelatedToNodeData()
    {
        $context = $this->getMockBuilder('TYPO3\TYPO3CR\Domain\Service\Context')->disableOriginalConstructor()->getMock();
        $nodeData = $this->getMockBuilder('TYPO3\TYPO3CR\Domain\Model\NodeData')->disableOriginalConstructor()->getMock();
        $node = new \TYPO3\TYPO3CR\Domain\Model\Node($nodeData, $context);
        $this->assertSame($nodeData, $node->getNodeData());
    }

    /**
     */
    protected function assertThatOriginalOrNewNodeIsCalled($methodName, $argument1 = null)
    {
        $userWorkspace = $this->getMock('TYPO3\TYPO3CR\Domain\Model\Workspace', array(), array(), '', false);
        $liveWorkspace = $this->getMock('TYPO3\TYPO3CR\Domain\Model\Workspace', array(), array(), '', false);
        $nodeType = $this->getMock('TYPO3\TYPO3CR\Domain\Model\NodeType', array(), array(), '', false);
        $nodeType->expects($this->any())->method('getPropertyType')->will($this->returnValue('string'));

        $originalNode = $this->getMock('TYPO3\TYPO3CR\Domain\Model\NodeData', array(), array(), '', false);
        $originalNode->expects($this->any())->method('getWorkspace')->will($this->returnValue($liveWorkspace));
        $originalNode->expects($this->any())->method('getNodeType')->will($this->returnValue($nodeType));
        if ($argument1 === null) {
            $originalNode->expects($this->any())->method($methodName)->will($this->returnValue('originalNodeResult'));
        } else {
            $originalNode->expects($this->any())->method($methodName)->with($argument1)->will($this->returnValue('originalNodeResult'));
        }

        $newNode = $this->getMock('TYPO3\TYPO3CR\Domain\Model\NodeData', array(), array(), '', false);
        $newNode->expects($this->any())->method('getWorkspace')->will($this->returnValue($userWorkspace));
        $newNode->expects($this->any())->method('getNodeType')->will($this->returnValue($nodeType));
        if ($argument1 === null) {
            $newNode->expects($this->any())->method($methodName)->will($this->returnValue('newNodeResult'));
        } else {
            $newNode->expects($this->any())->method($methodName)->with($argument1)->will($this->returnValue('newNodeResult'));
        }

        $context = $this->getMock('TYPO3\TYPO3CR\Domain\Service\Context', array(), array(), '', false);
        $context->expects($this->any())->method('getWorkspace')->will($this->returnValue($userWorkspace));

        $contextualizedNode = new \TYPO3\TYPO3CR\Domain\Model\Node($originalNode, $context);

        $this->assertEquals('originalNodeResult', $contextualizedNode->$methodName($argument1));

        $contextualizedNode = new \TYPO3\TYPO3CR\Domain\Model\Node($newNode, $context);

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
        $nodeData = $this->getMock('TYPO3\TYPO3CR\Domain\Model\NodeData', array(), array(), '', false);
        $nodeData->expects($this->once())->method('getIdentifier')->will($this->returnValue('theidentifier'));

        $context = $this->getMock('TYPO3\TYPO3CR\Domain\Service\Context', array(), array(), '', false);

        $contextualizedNode = new \TYPO3\TYPO3CR\Domain\Model\Node($nodeData, $context);

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
        $nodeType = $this->getMock('TYPO3\TYPO3CR\Domain\Model\NodeType', array(), array(), '', false);

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
        $node->getNodeData()->expects($this->once())->method('remove');

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

        $subNode1 = $this->getMock('TYPO3\TYPO3CR\Domain\Model\Node', array('remove'), array($nodeData, $context));
        $subNode1->expects($this->once())->method('remove');

        $subNode2 = $this->getMock('TYPO3\TYPO3CR\Domain\Model\Node', array('remove'), array($nodeData, $context));
        $subNode2->expects($this->once())->method('remove');

        $node->expects($this->once())->method('getChildNodes')->will($this->returnValue(array($subNode1, $subNode2)));
        $node->remove();
    }

    /**
     * @test
     */
    public function setRemovedCallsRemoveMethodIfArgumentIsTrue()
    {
        $node = $this->getAccessibleMock('TYPO3\TYPO3CR\Domain\Model\Node', array('remove', 'addOrUpdate'), array(), '', false);
        $node->expects($this->once())->method('remove');
        $node->setRemoved(true);
    }

    /**
     * @test
     */
    public function getParentReturnsParentNodeInCurrentNodesContext()
    {
        $currentNodeWorkspace = $this->getMock('TYPO3\TYPO3CR\Domain\Model\Workspace', array(), array(), '', false);

        $mockFirstLevelNodeCache = $this->getFirstLevelNodeCache();

        $context = $this->getMock('TYPO3\TYPO3CR\Domain\Service\Context', array(), array(), '', false);
        $context->expects($this->any())->method('getWorkspace')->will($this->returnValue($currentNodeWorkspace));
        $context->expects($this->any())->method('getFirstLevelNodeCache')->will($this->returnValue($mockFirstLevelNodeCache));

        $expectedParentNodeData = new \TYPO3\TYPO3CR\Domain\Model\NodeData('/foo', $currentNodeWorkspace);
        $expectedContextualizedParentNode = new \TYPO3\TYPO3CR\Domain\Model\Node($expectedParentNodeData, $context);

        $nodeDataRepository = $this->getMock('TYPO3\TYPO3CR\Domain\Repository\NodeDataRepository', array('findOneByPathInContext'), array(), '', false);
        $nodeDataRepository->expects($this->once())->method('findOneByPathInContext')->with('/foo', $context)->will($this->returnValue($expectedContextualizedParentNode));

        $currentNodeData = $this->getMock('TYPO3\TYPO3CR\Domain\Model\NodeData', array(), array('/foo/baz', $currentNodeWorkspace));
        $currentContextualizedNode = $this->getAccessibleMock('TYPO3\TYPO3CR\Domain\Model\Node', array('getParentPath'), array($currentNodeData, $context));
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
        $currentNodeWorkspace = $this->getMock('TYPO3\TYPO3CR\Domain\Model\Workspace', array(), array(), '', false);

        $mockFirstLevelNodeCache = $this->getFirstLevelNodeCache();

        $context = $this->getMock('TYPO3\TYPO3CR\Domain\Service\Context', array(), array(), '', false);
        $context->expects($this->any())->method('getWorkspace')->will($this->returnValue($currentNodeWorkspace));
        $context->expects($this->any())->method('getFirstLevelNodeCache')->will($this->returnValue($mockFirstLevelNodeCache));

        $expectedNodeData = $this->getMock('TYPO3\TYPO3CR\Domain\Model\NodeData', array(), array('/foo/bar', $currentNodeWorkspace));
        $expectedContextualizedNode = new \TYPO3\TYPO3CR\Domain\Model\Node($expectedNodeData, $context);

        $nodeDataRepository = $this->getMock('TYPO3\TYPO3CR\Domain\Repository\NodeDataRepository', array('findOneByPathInContext'), array(), '', false);
        $nodeDataRepository->expects($this->once())->method('findOneByPathInContext')->with('/foo/bar', $context)->will($this->returnValue($expectedContextualizedNode));

        $currentNodeData = $this->getMock('TYPO3\TYPO3CR\Domain\Model\NodeData', array('normalizePath'), array('/foo/baz', $currentNodeWorkspace));
        $currentNodeData->expects($this->once())->method('normalizePath')->with('../bar')->will($this->returnValue('/foo/bar'));
        $currentContextualizedNode = $this->getAccessibleMock('TYPO3\TYPO3CR\Domain\Model\Node', array('dummy'), array($currentNodeData, $context));
        $currentContextualizedNode->_set('nodeDataRepository', $nodeDataRepository);

        $actualNode = $currentContextualizedNode->getNode('../bar');
        $this->assertSame($expectedContextualizedNode, $actualNode);
    }

    /**
     * @param array $configurableMethods
     * @return \TYPO3\TYPO3CR\Domain\Model\Node
     */
    protected function setUpNodeWithNonMatchingContext(array $configurableMethods = array())
    {
        $userWorkspace = $this->getMock('TYPO3\TYPO3CR\Domain\Model\Workspace', array(), array(), '', false);
        $userWorkspace->expects($this->any())->method('getName')->will($this->returnValue('user'));
        $liveWorkspace = $this->getMock('TYPO3\TYPO3CR\Domain\Model\Workspace', array(), array(), '', false);
        $liveWorkspace->expects($this->any())->method('getName')->will($this->returnValue('live'));
        $liveWorkspace->expects($this->any())->method('getBaseWorkspace')->will($this->returnValue(null));

        $nodeData = $this->getMock('TYPO3\TYPO3CR\Domain\Model\NodeData', array(), array(), '', false);
        $nodeData->expects($this->any())->method('getWorkspace')->will($this->returnValue($liveWorkspace));

        $mockFirstLevelNodeCache = $this->getMock('TYPO3\TYPO3CR\Domain\Service\Cache\FirstLevelNodeCache');

        $context = $this->getMockBuilder('TYPO3\TYPO3CR\Domain\Service\Context')->disableOriginalConstructor()->getMock();
        $context->expects($this->any())->method('getWorkspace')->will($this->returnValue($userWorkspace));
        $context->expects($this->any())->method('getTargetDimensions')->will($this->returnValue(array()));
        $context->expects($this->any())->method('getFirstLevelNodeCache')->will($this->returnValue($mockFirstLevelNodeCache));

        $node = $this->getMock('TYPO3\TYPO3CR\Domain\Model\Node', array_merge(array('materializeNodeData'), $configurableMethods), array($nodeData, $context));
        $node->expects($this->once())->method('materializeNodeData');
        return $node;
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    protected function getFirstLevelNodeCache()
    {
        $mockFirstLevelNodeCache = $this->getMock('TYPO3\TYPO3CR\Domain\Service\Cache\FirstLevelNodeCache');
        $mockFirstLevelNodeCache->expects($this->any())->method('getByPath')->will($this->returnValue(false));
        $mockFirstLevelNodeCache->expects($this->any())->method('getByIdentifier')->will($this->returnValue(false));
        $mockFirstLevelNodeCache->expects($this->any())->method('getChildNodesByPathAndNodeTypeFilter')->will($this->returnValue(false));
        return $mockFirstLevelNodeCache;
    }
}
