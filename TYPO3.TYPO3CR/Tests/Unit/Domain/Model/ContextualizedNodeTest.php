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
        $propertyMapper = $this->getMockBuilder('TYPO3\Flow\Property\PropertyMapper')->disableOriginalConstructor()->getMock();
        $propertyMapper->expects($this->any())->method('convert')->willReturnArgument(0);

        $userWorkspace = $this->getMockBuilder('TYPO3\TYPO3CR\Domain\Model\Workspace')->disableOriginalConstructor()->getMock();
        $liveWorkspace = $this->getMockBuilder('TYPO3\TYPO3CR\Domain\Model\Workspace')->disableOriginalConstructor()->getMock();
        $nodeType = $this->getMockBuilder('TYPO3\TYPO3CR\Domain\Model\NodeType')->disableOriginalConstructor()->getMock();
        $nodeType->expects($this->any())->method('getPropertyType')->will($this->returnValue('string'));

        $originalNode = $this->getMockBuilder('TYPO3\TYPO3CR\Domain\Model\NodeData')->disableOriginalConstructor()->getMock();
        $originalNode->expects($this->any())->method('getWorkspace')->will($this->returnValue($liveWorkspace));
        $originalNode->expects($this->any())->method('getNodeType')->will($this->returnValue($nodeType));
        if ($argument1 === null) {
            $originalNode->expects($this->any())->method($methodName)->will($this->returnValue('originalNodeResult'));
        } else {
            $originalNode->expects($this->any())->method($methodName)->with($argument1)->will($this->returnValue('originalNodeResult'));
        }

        $newNode = $this->getMockBuilder('TYPO3\TYPO3CR\Domain\Model\NodeData')->disableOriginalConstructor()->getMock();
        $newNode->expects($this->any())->method('getWorkspace')->will($this->returnValue($userWorkspace));
        $newNode->expects($this->any())->method('getNodeType')->will($this->returnValue($nodeType));
        if ($argument1 === null) {
            $newNode->expects($this->any())->method($methodName)->will($this->returnValue('newNodeResult'));
        } else {
            $newNode->expects($this->any())->method($methodName)->with($argument1)->will($this->returnValue('newNodeResult'));
        }

        $context = $this->getMockBuilder('TYPO3\TYPO3CR\Domain\Service\Context')->disableOriginalConstructor()->getMock();
        $context->expects($this->any())->method('getWorkspace')->will($this->returnValue($userWorkspace));

        $contextualizedNode = new \TYPO3\TYPO3CR\Domain\Model\Node($originalNode, $context);
        $this->inject($contextualizedNode, 'propertyMapper', $propertyMapper);

        $this->assertEquals('originalNodeResult', $contextualizedNode->$methodName($argument1));

        $contextualizedNode = new \TYPO3\TYPO3CR\Domain\Model\Node($newNode, $context);
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
        $nodeData = $this->getMockBuilder('TYPO3\TYPO3CR\Domain\Model\NodeData')->disableOriginalConstructor()->getMock();
        $nodeData->expects($this->once())->method('getIdentifier')->will($this->returnValue('theidentifier'));

        $context = $this->getMockBuilder('TYPO3\TYPO3CR\Domain\Service\Context')->disableOriginalConstructor()->getMock();

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
        $nodeType = $this->getMockBuilder('TYPO3\TYPO3CR\Domain\Model\NodeType')->disableOriginalConstructor()->getMock();

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

        $subNode1 = $this->getMockBuilder('TYPO3\TYPO3CR\Domain\Model\Node')->setMethods(array('setRemoved'))->setConstructorArgs(array($nodeData, $context))->getMock();
        $subNode1->expects($this->once())->method('setRemoved');

        $subNode2 = $this->getMockBuilder('TYPO3\TYPO3CR\Domain\Model\Node')->setMethods(array('setRemoved'))->setConstructorArgs(array($nodeData, $context))->getMock();
        $subNode2->expects($this->once())->method('setRemoved');

        $node->expects($this->once())->method('getChildNodes')->will($this->returnValue(array($subNode1, $subNode2)));
        $node->remove();
    }

    /**
     * @test
     */
    public function getParentReturnsParentNodeInCurrentNodesContext()
    {
        $currentNodeWorkspace = $this->getMockBuilder('TYPO3\TYPO3CR\Domain\Model\Workspace')->disableOriginalConstructor()->getMock();

        $mockFirstLevelNodeCache = $this->getFirstLevelNodeCache();

        $context = $this->getMockBuilder('TYPO3\TYPO3CR\Domain\Service\Context')->disableOriginalConstructor()->getMock();
        $context->expects($this->any())->method('getWorkspace')->will($this->returnValue($currentNodeWorkspace));
        $context->expects($this->any())->method('getFirstLevelNodeCache')->will($this->returnValue($mockFirstLevelNodeCache));

        $expectedParentNodeData = new \TYPO3\TYPO3CR\Domain\Model\NodeData('/foo', $currentNodeWorkspace);
        $expectedContextualizedParentNode = new \TYPO3\TYPO3CR\Domain\Model\Node($expectedParentNodeData, $context);

        $nodeDataRepository = $this->getMockBuilder('TYPO3\TYPO3CR\Domain\Repository\NodeDataRepository')->disableOriginalConstructor()->setMethods(array('findOneByPathInContext'))->getMock();
        $nodeDataRepository->expects($this->once())->method('findOneByPathInContext')->with('/foo', $context)->will($this->returnValue($expectedContextualizedParentNode));

        $currentNodeData = $this->getMockBuilder('TYPO3\TYPO3CR\Domain\Model\NodeData')->setConstructorArgs(array('/foo/baz', $currentNodeWorkspace))->getMock();
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
        $currentNodeWorkspace = $this->getMockBuilder('TYPO3\TYPO3CR\Domain\Model\Workspace')->disableOriginalConstructor()->getMock();

        $mockFirstLevelNodeCache = $this->getFirstLevelNodeCache();

        $context = $this->getMockBuilder('TYPO3\TYPO3CR\Domain\Service\Context')->disableOriginalConstructor()->getMock();
        $context->expects($this->any())->method('getWorkspace')->will($this->returnValue($currentNodeWorkspace));
        $context->expects($this->any())->method('getFirstLevelNodeCache')->will($this->returnValue($mockFirstLevelNodeCache));

        $expectedNodeData = $this->getMockBuilder('TYPO3\TYPO3CR\Domain\Model\NodeData')->setConstructorArgs(array('/foo/bar', $currentNodeWorkspace))->getMock();
        $expectedContextualizedNode = new \TYPO3\TYPO3CR\Domain\Model\Node($expectedNodeData, $context);

        $nodeDataRepository = $this->getMockBuilder('TYPO3\TYPO3CR\Domain\Repository\NodeDataRepository')->disableOriginalConstructor()->setMethods(array('findOneByPathInContext'))->getMock();
        $nodeDataRepository->expects($this->once())->method('findOneByPathInContext')->with('/foo/bar', $context)->will($this->returnValue($expectedContextualizedNode));

        $currentNodeData = $this->getMockBuilder('TYPO3\TYPO3CR\Domain\Model\NodeData')->setMethods(array('normalizePath'))->setConstructorArgs(array('/foo/baz', $currentNodeWorkspace))->getMock();
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
        $userWorkspace = $this->getMockBuilder('TYPO3\TYPO3CR\Domain\Model\Workspace')->disableOriginalConstructor()->getMock();
        $userWorkspace->expects($this->any())->method('getName')->will($this->returnValue('user'));
        $liveWorkspace = $this->getMockBuilder('TYPO3\TYPO3CR\Domain\Model\Workspace')->disableOriginalConstructor()->getMock();
        $liveWorkspace->expects($this->any())->method('getName')->will($this->returnValue('live'));
        $liveWorkspace->expects($this->any())->method('getBaseWorkspace')->will($this->returnValue(null));

        $nodeData = $this->getMockBuilder('TYPO3\TYPO3CR\Domain\Model\NodeData')->disableOriginalConstructor()->getMock();
        $nodeData->expects($this->any())->method('getWorkspace')->will($this->returnValue($liveWorkspace));

        $mockFirstLevelNodeCache = $this->createMock('TYPO3\TYPO3CR\Domain\Service\Cache\FirstLevelNodeCache');

        $context = $this->getMockBuilder('TYPO3\TYPO3CR\Domain\Service\Context')->disableOriginalConstructor()->getMock();
        $context->expects($this->any())->method('getWorkspace')->will($this->returnValue($userWorkspace));
        $context->expects($this->any())->method('getTargetDimensions')->will($this->returnValue(array()));
        $context->expects($this->any())->method('getFirstLevelNodeCache')->will($this->returnValue($mockFirstLevelNodeCache));

        $node = $this->getMockBuilder('TYPO3\TYPO3CR\Domain\Model\Node')->setMethods(array_merge(array('materializeNodeData'), $configurableMethods))->setConstructorArgs(array($nodeData, $context))->getMock();
        $node->expects($this->once())->method('materializeNodeData');
        return $node;
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    protected function getFirstLevelNodeCache()
    {
        $mockFirstLevelNodeCache = $this->createMock('TYPO3\TYPO3CR\Domain\Service\Cache\FirstLevelNodeCache');
        $mockFirstLevelNodeCache->expects($this->any())->method('getByPath')->will($this->returnValue(false));
        $mockFirstLevelNodeCache->expects($this->any())->method('getByIdentifier')->will($this->returnValue(false));
        $mockFirstLevelNodeCache->expects($this->any())->method('getChildNodesByPathAndNodeTypeFilter')->will($this->returnValue(false));
        return $mockFirstLevelNodeCache;
    }
}
