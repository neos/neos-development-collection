<?php
namespace TYPO3\TYPO3CR\Tests\Unit\Domain\Factory;

/*
 * This file is part of the TYPO3.TYPO3CR package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */
use TYPO3\Flow\Object\ObjectManagerInterface;
use TYPO3\Flow\Reflection\ReflectionService;
use TYPO3\TYPO3CR\Domain\Factory\NodeFactory;

/**
 * Testcase for the NodeFactory
 *
 */
class NodeFactoryTest extends \TYPO3\Flow\Tests\UnitTestCase
{
    /**
     * @var NodeFactory
     */
    protected $nodeFactory;

    /**
     * @var ObjectManagerInterface
     */
    protected $objectManagerMock;

    /**
     * @var ReflectionService
     */
    protected $reflectionServiceMock;


    /**
     * Prepare test objects
     */
    protected function setUp()
    {
        $this->nodeFactory = $this->getMockBuilder('TYPO3\TYPO3CR\Domain\Factory\NodeFactory')->setMethods(array('filterNodeByContext'))->getMock();

        $this->nodeFactory->expects(self::any())->method('filterNodeByContext')->willReturnArgument(0);

        $this->reflectionServiceMock = $this->createMock('TYPO3\Flow\Reflection\ReflectionService');
        $this->reflectionServiceMock->expects(self::any())->method('getAllImplementationClassNamesForInterface')->with('TYPO3\TYPO3CR\Domain\Model\NodeInterface')->willReturn(array('TYPO3\TYPO3CR\Domain\Model\Node'));

        $this->objectManagerMock = $this->createMock('TYPO3\Flow\Object\ObjectManagerInterface');
        $this->objectManagerMock->expects(self::any())->method('get')->with('TYPO3\Flow\Reflection\ReflectionService')->willReturn($this->reflectionServiceMock);
        $this->objectManagerMock->expects(self::any())->method('getClassNameByObjectName')->with('TYPO3\TYPO3CR\Domain\Model\NodeInterface')->willReturn('TYPO3\TYPO3CR\Domain\Model\Node');

        $this->inject($this->nodeFactory, 'objectManager', $this->objectManagerMock);
    }

    /**
     * @test
     */
    public function createFromNodeDataCreatesANodeWithTheGivenContextAndNodeData()
    {
        $mockNodeType = $this->getMockBuilder('TYPO3\TYPO3CR\Domain\Model\NodeType')->disableOriginalConstructor()->getMock();

        $mockNodeData = $this->getMockBuilder('TYPO3\TYPO3CR\Domain\Model\NodeData')->disableOriginalConstructor()->getMock();
        $mockNodeData->expects(self::any())->method('getIdentifier')->willReturn('0068371a-c108-99cb-3aa5-81b8852a2d12');
        $mockNodeData->expects(self::any())->method('getNodeType')->willReturn($mockNodeType);

        $mockContext = $this->getMockBuilder('TYPO3\TYPO3CR\Domain\Service\Context')->disableOriginalConstructor()->getMock();

        $node = $this->nodeFactory->createFromNodeData($mockNodeData, $mockContext);

        self::assertEquals($mockContext, $node->getContext());
        self::assertEquals($mockNodeData, $node->getNodeData());
        self::assertEquals('0068371a-c108-99cb-3aa5-81b8852a2d12', $node->getIdentifier());
    }

    /**
     * @test
     */
    public function createContextMatchingNodeDataCreatesMatchingContext()
    {
        $dimensionValues = array('language' => array('is'));
        $workspaceName = 'some-workspace';

        $mockContext = $this->getMockBuilder('TYPO3\TYPO3CR\Domain\Service\Context')->disableOriginalConstructor()->getMock();

        $mockWorkspace = $this->getMockBuilder('TYPO3\TYPO3CR\Domain\Model\Workspace')->setMockClassName('MockWorkspace')->disableOriginalConstructor()->getMock();
        $mockWorkspace->expects(self::any())->method('getName')->will(self::returnValue($workspaceName));

        $mockContextFactory = $this->createMock('TYPO3\TYPO3CR\Domain\Service\ContextFactoryInterface');
        $mockContextFactory->expects(self::once())->method('create')->with(array(
            'workspaceName' => $workspaceName,
            'invisibleContentShown' => true,
            'inaccessibleContentShown' => true,
            'removedContentShown' => true,
            'dimensions' => $dimensionValues
        ))->willReturn($mockContext);

        $this->inject($this->nodeFactory, 'contextFactory', $mockContextFactory);

        $mockNodeData = $this->getMockBuilder('TYPO3\TYPO3CR\Domain\Model\NodeData')->disableOriginalConstructor()->getMock();
        $mockNodeData->expects(self::any())->method('getWorkspace')->will($this->returnValue($mockWorkspace));
        $mockNodeData->expects(self::any())->method('getDimensionValues')->willReturn($dimensionValues);

        $context = $this->nodeFactory->createContextMatchingNodeData($mockNodeData);

        self::assertEquals($mockContext, $context);
    }
}
