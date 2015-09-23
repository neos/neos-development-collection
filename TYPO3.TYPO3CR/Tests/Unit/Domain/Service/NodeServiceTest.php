<?php
namespace TYPO3\TYPO3CR\Tests\Unit\Domain\Service;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "TYPO3CR".               *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */
use TYPO3\TYPO3CR\Domain\Service\NodeService;

/**
 * Testcase for the NodeService
 *
 */
class NodeServiceTest extends \TYPO3\Flow\Tests\UnitTestCase
{
    /**
     * example node types
     *
     * @var array
     */
    protected $subNodeTypesFixture = array(
        'TYPO3.TYPO3CR.Testing:MyFinalType' => array(
            'superTypes' => array('TYPO3.TYPO3CR.Testing:ContentObject'),
            'final' => true
        ),
        'TYPO3.TYPO3CR.Testing:Text' => array(
            'superTypes' => array('TYPO3.TYPO3CR.Testing:ContentObject'),
            'ui' => array(
                'label' => 'Text',
            ),
            'properties' => array(
                'headline' => array(
                    'type' => 'string',
                    'placeholder' => 'Enter headline here'
                ),
                'text' => array(
                    'type' => 'string',
                    'placeholder' => '<p>Enter text here</p>'
                )
            ),
            'inlineEditableProperties' => array('headline', 'text')
        )
    );

    /**
     * @return NodeService
     */
    protected function createNodeService()
    {
        $nodeService = new NodeService();
        $mockNodeTypeManager = $this->getMock('\TYPO3\TYPO3CR\Domain\Service\NodeTypeManager', array(), array(), '', false);
        $mockNodeTypeManager->expects($this->any())
            ->method('getSubNodeTypes')
            ->will($this->returnValue($this->subNodeTypesFixture));
        $mockNodeTypeManager->expects($this->any())
            ->method('getNodeType')
            ->will($this->returnCallback(function ($nodeTypeName) {
                return new \TYPO3\TYPO3CR\Domain\Model\NodeType($nodeTypeName, array(), array());
            }));

        $this->inject($nodeService, 'systemLogger', $this->getMock('TYPO3\Flow\Log\SystemLoggerInterface', array(), array(), '', false));
        $this->inject($nodeService, 'nodeTypeManager', $mockNodeTypeManager);

        return $nodeService;
    }

    /**
     * @param string $nodeTypeName
     * @return mixed
     */
    protected function mockNodeType($nodeTypeName)
    {
        $mockNodeType = $this->getMock('\TYPO3\TYPO3CR\Domain\Model\NodeType', array(), array(), '', false);
        $mockNodeType->expects($this->any())
            ->method('getName')
            ->will($this->returnValue($nodeTypeName));
        $mockNodeType->expects($this->any())
            ->method('__toString')
            ->will($this->returnValue($nodeTypeName));

        return $mockNodeType;
    }

    /**
     * @test
     */
    public function setDefaultValueOnlyIfTheCurrentPropertyIsNull()
    {
        $nodeService = $this->createNodeService();

        $mockNode = $this->getMock('\TYPO3\TYPO3CR\Domain\Model\Node', array(), array(), '', false);

        $mockNodeType = $this->mockNodeType('TYPO3.TYPO3CR.Testing:Content');

        $mockNode->expects($this->once())
            ->method('getNodeType')
            ->will($this->returnValue($mockNodeType));

        $mockNode->expects($this->once())
            ->method('getProperty')
            ->with('title')
            ->will($this->returnValue(null));

        $mockNode->expects($this->once())
            ->method('setProperty')
            ->with('title', 'hello');

        $mockNodeType->expects($this->once())
            ->method('getDefaultValuesForProperties')
            ->will($this->returnValue(array(
                'title' => 'hello'
            )));

        $nodeService->setDefaultValues($mockNode);
    }

    /**
     * @test
     */
    public function setDefaultDateValueOnlyIfTheCurrentPropertyIsNull()
    {
        $nodeService = $this->createNodeService();

        $mockNode = $this->getMock('\TYPO3\TYPO3CR\Domain\Model\Node', array(), array(), '', false);

        $mockNodeType = $this->mockNodeType('TYPO3.Neos:Content');

        $mockNode->expects($this->once())
            ->method('getNodeType')
            ->will($this->returnValue($mockNodeType));

        $mockNode->expects($this->once())
            ->method('getProperty')
            ->with('date')
            ->will($this->returnValue(null));

        $mockNode->expects($this->once())
            ->method('setProperty')
            ->with('date', new \DateTime('2014-09-03'));

        $mockNodeType->expects($this->once())
            ->method('getDefaultValuesForProperties')
            ->will($this->returnValue(array(
                'date' => new \DateTime('2014-09-03')
            )));

        $nodeService->setDefaultValues($mockNode);
    }

    /**
     * @test
     */
    public function setDefaultValueNeverReplaceExistingValue()
    {
        $nodeService = $this->createNodeService();

        $mockNode = $this->getMock('\TYPO3\TYPO3CR\Domain\Model\Node', array(), array(), '', false);

        $mockNodeType = $this->mockNodeType('TYPO3.TYPO3CR.Testing:Content');

        $mockNode->expects($this->once())
            ->method('getNodeType')
            ->will($this->returnValue($mockNodeType));

        $mockNode->expects($this->once())
            ->method('getProperty')
            ->with('title')
            ->will($this->returnValue('Existing value'));

        $mockNode->expects($this->never())
            ->method('setProperty');

        $mockNodeType->expects($this->once())
            ->method('getDefaultValuesForProperties')
            ->will($this->returnValue(array(
                'title' => 'hello'
            )));

        $nodeService->setDefaultValues($mockNode);
    }

    /**
     * @test
     */
    public function createChildNodesTryToCreateAllConfiguredChildNodes()
    {
        $nodeService = $this->createNodeService();

        $mockNode = $this->getMock('\TYPO3\TYPO3CR\Domain\Model\Node', array(), array(), '', false);

        $mockNodeType = $this->mockNodeType('TYPO3.TYPO3CR.Testing:Content');
        $firstChildNodeType = $this->mockNodeType('TYPO3.TYPO3CR.Testing:Content');
        $secondChildNodeType = $this->mockNodeType('TYPO3.TYPO3CR.Testing:Content');

        $mockNodeType->expects($this->once())
            ->method('getAutoCreatedChildNodes')
            ->will($this->returnValue(array(
                'first-child-node-name' => $firstChildNodeType,
                'second-child-node-name' => $secondChildNodeType
            )));

        $mockNode->expects($this->once())
            ->method('getNodeType')
            ->will($this->returnValue($mockNodeType));

        $mockNode->expects($this->at(1))
            ->method('createNode')
            ->with('first-child-node-name', $firstChildNodeType);

        $mockNode->expects($this->at(2))
            ->method('createNode')
            ->with('second-child-node-name', $secondChildNodeType);

        $nodeService->createChildNodes($mockNode);
    }

    /**
     * @test
     */
    public function cleanUpPropertiesRemoveAllUndeclaredProperties()
    {
        $nodeService = $this->createNodeService();

        $mockNode = $this->getMock('\TYPO3\TYPO3CR\Domain\Model\Node', array(), array(), '', false);

        $mockNodeType = $this->mockNodeType('TYPO3.TYPO3CR.Testing:Content');
        $mockNodeType->expects($this->once())
            ->method('getProperties')
            ->will($this->returnValue(array(
                'title' => array(),
                'description' => array()
            )));

        $mockNode->expects($this->once())
            ->method('getNodeType')
            ->will($this->returnValue($mockNodeType));

        $mockNode->expects($this->once())
            ->method('removeProperty')
            ->with('invalidProperty');

        $mockNode->expects($this->once())
            ->method('getProperties')
            ->will($this->returnValue(array(
                'title' => 'hello',
                'description' => 'world',
                'invalidProperty' => 'world'
            )));

        $nodeService->cleanUpProperties($mockNode);
    }

    /**
     * @test
     *
     * TODO: Adjust after the removal of child nodes is implemented again.
     */
    public function cleanUpChildNodesRemoveAllUndeclaredChildNodes()
    {
        $this->markTestSkipped('Currently this functionality is disabled. We will introduce it again at a later point and then reenable this test.');

        $nodeService = $this->createNodeService();

        $mockNode = $this->getMock('\TYPO3\TYPO3CR\Domain\Model\Node', array(), array(), '', false);

        $mockNodeType = $this->mockNodeType('TYPO3.TYPO3CR.Testing:Content');

        $mockContentNodeType = $this->mockNodeType('TYPO3.TYPO3CR.Testing:ContentCollection');

        $mockFirstChildNode = $this->getMock('\TYPO3\TYPO3CR\Domain\Model\Node', array(), array(), '', false);
        $mockFirstChildNode->expects($this->any())
            ->method('getNodeType')
            ->will($this->returnValue($mockContentNodeType));
        $mockFirstChildNode->expects($this->any())
            ->method('getName')
            ->will($this->returnValue('main'));
        $mockFirstChildNode->expects($this->never())
            ->method('remove');

        $mockSecondChildNode = $this->getMock('\TYPO3\TYPO3CR\Domain\Model\Node', array(), array(), '', false);
        $mockSecondChildNode->expects($this->any())
            ->method('getNodeType')
            ->will($this->returnValue($mockContentNodeType));
        $mockSecondChildNode->expects($this->any())
            ->method('getName')
            ->will($this->returnValue('sidebar'));
        $mockSecondChildNode->expects($this->never())
            ->method('remove');

        $mockThirdChildNode = $this->getMock('\TYPO3\TYPO3CR\Domain\Model\Node', array(), array(), '', false);
        $mockThirdChildNode->expects($this->any())
            ->method('getNodeType')
            ->will($this->returnValue($mockContentNodeType));
        $mockThirdChildNode->expects($this->any())
            ->method('getName')
            ->will($this->returnValue('footer'));
        $mockThirdChildNode->expects($this->once())
            ->method('remove');

        $mockMainChildNodeType = $this->mockNodeType('TYPO3.TYPO3CR.Testing:ContentCollection');
        $mockSidebarChildNodeType = $this->mockNodeType('TYPO3.TYPO3CR.Testing:ContentCollection');
        $mockNodeType->expects($this->once())
            ->method('getAutoCreatedChildNodes')
            ->will($this->returnValue(array(
                'main' => $mockMainChildNodeType,
                'sidebar' => $mockSidebarChildNodeType
            )));

        $mockNode->expects($this->once())
            ->method('getNodeType')
            ->will($this->returnValue($mockNodeType));

        $mockNode->expects($this->once())
            ->method('getChildNodes')
            ->will($this->returnValue(array(
                $mockFirstChildNode,
                $mockSecondChildNode,
                $mockThirdChildNode
            )));

        $nodeService->cleanUpChildNodes($mockNode);
    }

    /**
     * @test
     *
     * TODO: Adjust after the removal of child nodes is implemented again.
     */
    public function cleanUpChildNodesNeverRemoveDocumentNode()
    {
        $this->markTestSkipped('Currently this functionality is disabled. We will introduce it again at a later point and then reenable this test.');

        $nodeService = $this->createNodeService();

        $mockNode = $this->getMock('\TYPO3\TYPO3CR\Domain\Model\Node', array(), array(), '', false);

        $mockNodeType = $this->mockNodeType('TYPO3.TYPO3CR.Testing:Page');

        $mockContentNodeType = $this->mockNodeType('TYPO3.TYPO3CR.Testing:Document');

        $mockFirstChildNode = $this->getMock('\TYPO3\TYPO3CR\Domain\Model\Node', array(), array(), '', false);
        $mockFirstChildNode->expects($this->any())
            ->method('getNodeType')
            ->will($this->returnValue($mockContentNodeType));
        $mockFirstChildNode->expects($this->any())
            ->method('getName')
            ->will($this->returnValue('sidebar'));
        $mockFirstChildNode->expects($this->never())
            ->method('remove');

        $mockMainChildNodeType = $this->mockNodeType('TYPO3.TYPO3CR.Testing:ContentCollection');
        $mockNodeType->expects($this->once())
            ->method('getAutoCreatedChildNodes')
            ->will($this->returnValue(array(
                'main' => $mockMainChildNodeType
            )));

        $mockNode->expects($this->once())
            ->method('getNodeType')
            ->will($this->returnValue($mockNodeType));

        $mockNode->expects($this->once())
            ->method('getChildNodes')
            ->will($this->returnValue(array(
                $mockFirstChildNode,
            )));

        $nodeService->cleanUpChildNodes($mockNode);
    }

    /**
     * @test
     */
    public function isNodeOfTypeReturnTrueIsTheGivenNodeIsSubNodeOfTheGivenType()
    {
        $nodeService = $this->createNodeService();

        $mockNode = $this->getMock('\TYPO3\TYPO3CR\Domain\Model\Node', array(), array(), '', false);

        $mockNodeType = $this->mockNodeType('TYPO3.TYPO3CR.Testing:MyFinalType');

        $mockNode->expects($this->atLeastOnce())
            ->method('getNodeType')
            ->will($this->returnValue($mockNodeType));

        $mockNodeType = $this->mockNodeType('TYPO3.TYPO3CR.Testing:ContentObject');
        $this->assertTrue($nodeService->isNodeOfType($mockNode, $mockNodeType));
    }

    /**
     * @test
     */
    public function isNodeOfTypeReturnTrueIsTheGivenNodeHasTheSameTypeOfTheGivenType()
    {
        $nodeService = $this->createNodeService();

        $mockNode = $this->getMock('\TYPO3\TYPO3CR\Domain\Model\Node', array(), array(), '', false);

        $mockNodeType = $this->mockNodeType('TYPO3.TYPO3CR.Testing:Document');

        $mockNode->expects($this->atLeastOnce())
            ->method('getNodeType')
            ->will($this->returnValue($mockNodeType));

        $this->assertTrue($nodeService->isNodeOfType($mockNode, $mockNodeType));
    }
}
