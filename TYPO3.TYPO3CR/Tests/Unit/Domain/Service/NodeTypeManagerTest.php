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

use TYPO3\Flow\Configuration\ConfigurationManager;
use TYPO3\Flow\Tests\UnitTestCase;
use TYPO3\TYPO3CR\Domain\Service\NodeTypeManager;

/**
 * Testcase for the "NodeTypeManager"
 */
class NodeTypeManagerTest extends UnitTestCase
{
    /**
     * @var NodeTypeManager
     */
    protected $nodeTypeManager;

    /**
     * @var ConfigurationManager|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $mockConfigurationManager;

    public function setUp()
    {
        $this->nodeTypeManager = new NodeTypeManager();

        $this->mockConfigurationManager = $this->getMockBuilder(ConfigurationManager::class)->disableOriginalConstructor()->getMock();
        $this->mockConfigurationManager->expects($this->any())->method('getConfiguration')->with('NodeTypes')->will($this->returnValue($this->nodeTypesFixture));
        $this->inject($this->nodeTypeManager, 'configurationManager', $this->mockConfigurationManager);
    }

    /**
     * example node types
     *
     * @var array
     */
    protected $nodeTypesFixture = array(
        'TYPO3.TYPO3CR.Testing:ContentObject' => array(
            'ui' => array(
                'label' => 'Abstract content object',
            ),
            'abstract' => true,
            'properties' => array(
                '_hidden' => array(
                    'type' => 'boolean',
                    'label' => 'Hidden',
                    'category' => 'visibility',
                    'priority' => 1
                ),
            ),
            'propertyGroups' => array(
                'visibility' => array(
                    'label' => 'Visibility',
                    'priority' => 1
                )
            )
        ),
        'TYPO3.TYPO3CR.Testing:MyFinalType' => array(
            'superTypes' => array('TYPO3.TYPO3CR.Testing:ContentObject' => true),
            'final' => true
        ),
        'TYPO3.TYPO3CR.Testing:AbstractType' => array(
            'superTypes' => array('TYPO3.TYPO3CR.Testing:ContentObject' => true),
            'ui' => array(
                'label' => 'Abstract type',
            ),
            'abstract' => true
        ),
        'TYPO3.TYPO3CR.Testing:Text' => array(
            'superTypes' => array('TYPO3.TYPO3CR.Testing:ContentObject' => true),
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
        ),
        'TYPO3.TYPO3CR.Testing:TextWithImage' => array(
            'superTypes' => array('TYPO3.TYPO3CR.Testing:Text' => true),
            'ui' => array(
                'label' => 'Text with image',
            ),
            'properties' => array(
                'image' => array(
                    'type' => 'TYPO3\Neos\Domain\Model\Media\Image',
                    'label' => 'Image'
                )
            )
        ),
        'TYPO3.TYPO3CR.Testing:Document' => array(
            'abstract' => true,
            'aggregate' => true
        ),
        'TYPO3.TYPO3CR.Testing:Page' => array(
            'superTypes' => array('TYPO3.TYPO3CR.Testing:Document' => true),
        ),
        'TYPO3.TYPO3CR.Testing:Page2' => array(
            'superTypes' => array('TYPO3.TYPO3CR.Testing:Document' => true),
        ),
        'TYPO3.TYPO3CR.Testing:Page3' => array(
            'superTypes' => array('TYPO3.TYPO3CR.Testing:Document' => true),
        ),
        'TYPO3.TYPO3CR.Testing:DocumentWithSupertypes' => array(
            'superTypes' => array(
                0 => 'TYPO3.TYPO3CR.Testing:Document',
                'TYPO3.TYPO3CR.Testing:Page' => true,
                'TYPO3.TYPO3CR.Testing:Page2' => false,
                'TYPO3.TYPO3CR.Testing:Page3' => null
            )
        ),
        'TYPO3.TYPO3CR:FallbackNode' => array()
    );

    /**
     * @test
     */
    public function nodeTypeConfigurationIsMergedTogether()
    {
        $nodeType = $this->nodeTypeManager->getNodeType('TYPO3.TYPO3CR.Testing:Text');
        $this->assertSame('Text', $nodeType->getLabel());

        $expectedProperties = array(
            '_hidden' => array(
                'type' => 'boolean',
                'label' => 'Hidden',
                'category' => 'visibility',
                'priority' => 1
            ),
            'headline' => array(
                'type' => 'string',
                'placeholder' => 'Enter headline here'
            ),
            'text' => array(
                'type' => 'string',
                'placeholder' => '<p>Enter text here</p>'
            )
        );
        $this->assertSame($expectedProperties, $nodeType->getProperties());
    }

    /**
     * @test
     * @expectedException \TYPO3\TYPO3CR\Exception\NodeTypeNotFoundException
     */
    public function getNodeTypeThrowsExceptionForUnknownNodeType()
    {
        $this->nodeTypeManager->getNodeType('TYPO3.TYPO3CR.Testing:TextFooBarNotHere');
    }

    /**
     * @test
     * @expectedException \TYPO3\TYPO3CR\Exception\NodeTypeNotFoundException
     */
    public function getNodeTypeThrowsExceptionIfNoFallbackNodeTypeIsConfigured()
    {
        $this->nodeTypeManager->getNodeType('TYPO3.TYPO3CR.Testing:TextFooBarNotHere');
    }

    /**
     * @test
     * @expectedException \TYPO3\TYPO3CR\Exception\NodeTypeNotFoundException
     */
    public function getNodeTypeThrowsExceptionIfConfiguredFallbackNodeTypeCantBeFound()
    {
        $this->inject($this->nodeTypeManager, 'fallbackNodeTypeName', 'TYPO3.TYPO3CR:NonExistingFallbackNode');
        $this->nodeTypeManager->getNodeType('TYPO3.TYPO3CR.Testing:TextFooBarNotHere');
    }

    /**
     * @test
     */
    public function getNodeTypeReturnsFallbackNodeTypeIfConfigured()
    {
        $this->inject($this->nodeTypeManager, 'fallbackNodeTypeName', 'TYPO3.TYPO3CR:FallbackNode');

        $expectedNodeType = $this->nodeTypeManager->getNodeType('TYPO3.TYPO3CR:FallbackNode');
        $fallbackNodeType = $this->nodeTypeManager->getNodeType('TYPO3.TYPO3CR.Testing:TextFooBarNotHere');
        $this->assertSame($expectedNodeType, $fallbackNodeType);
    }

    /**
     * @test
     * @expectedException \TYPO3\TYPO3CR\Exception
     */
    public function createNodeTypeAlwaysThrowsAnException()
    {
        $this->nodeTypeManager->createNodeType('TYPO3.TYPO3CR.Testing:ContentObject');
    }

    /**
     * @test
     */
    public function hasNodeTypeReturnsTrueIfTheGivenNodeTypeIsFound()
    {
        $this->assertTrue($this->nodeTypeManager->hasNodeType('TYPO3.TYPO3CR.Testing:TextWithImage'));
    }

    /**
     * @test
     */
    public function hasNodeTypeReturnsFalseIfTheGivenNodeTypeIsNotFound()
    {
        $this->assertFalse($this->nodeTypeManager->hasNodeType('TYPO3.TYPO3CR.Testing:TextFooBarNotHere'));
    }

    /**
     * @test
     */
    public function hasNodeTypeReturnsTrueForAbstractNodeTypes()
    {
        $this->assertTrue($this->nodeTypeManager->hasNodeType('TYPO3.TYPO3CR.Testing:ContentObject'));
    }

    /**
     * @test
     */
    public function getNodeTypesReturnsRegisteredNodeTypes()
    {
        $expectedNodeTypes = array(
            'TYPO3.TYPO3CR.Testing:ContentObject',
            'TYPO3.TYPO3CR.Testing:MyFinalType',
            'TYPO3.TYPO3CR.Testing:AbstractType',
            'TYPO3.TYPO3CR.Testing:Text',
            'TYPO3.TYPO3CR.Testing:TextWithImage',
            'TYPO3.TYPO3CR.Testing:Document',
            'TYPO3.TYPO3CR.Testing:Page',
            'TYPO3.TYPO3CR.Testing:Page2',
            'TYPO3.TYPO3CR.Testing:Page3',
            'TYPO3.TYPO3CR.Testing:DocumentWithSupertypes',
            'TYPO3.TYPO3CR:FallbackNode'
        );
        $this->assertEquals($expectedNodeTypes, array_keys($this->nodeTypeManager->getNodeTypes()));
    }

    /**
     * @test
     */
    public function getNodeTypesContainsAbstractNodeTypes()
    {
        $nodeTypes = $this->nodeTypeManager->getNodeTypes();
        $this->assertArrayHasKey('TYPO3.TYPO3CR.Testing:ContentObject', $nodeTypes);
    }

    /**
     * @test
     */
    public function getNodeTypesWithoutIncludeAbstractContainsNoAbstractNodeTypes()
    {
        $nodeTypes = $this->nodeTypeManager->getNodeTypes(false);
        $this->assertArrayNotHasKey('TYPO3.TYPO3CR.Testing:ContentObject', $nodeTypes);
    }

    /**
     * @test
     */
    public function getSubNodeTypesReturnsInheritedNodeTypes()
    {
        $nodeTypes = $this->nodeTypeManager->getSubNodeTypes('TYPO3.TYPO3CR.Testing:ContentObject');
        $this->assertArrayHasKey('TYPO3.TYPO3CR.Testing:TextWithImage', $nodeTypes);
    }

    /**
     * @test
     */
    public function getSubNodeTypesContainsAbstractNodeTypes()
    {
        $nodeTypes = $this->nodeTypeManager->getSubNodeTypes('TYPO3.TYPO3CR.Testing:ContentObject');
        $this->assertArrayHasKey('TYPO3.TYPO3CR.Testing:AbstractType', $nodeTypes);
    }

    /**
     * @test
     */
    public function getSubNodeTypesWithoutIncludeAbstractContainsNoAbstractNodeTypes()
    {
        $nodeTypes = $this->nodeTypeManager->getSubNodeTypes('TYPO3.TYPO3CR.Testing:ContentObject', false);
        $this->assertArrayNotHasKey('TYPO3.TYPO3CR.Testing:AbstractType', $nodeTypes);
    }

    /**
     * @test
     */
    public function getNodeTypeAllowsToRetrieveFinalNodeTypes()
    {
        $this->assertTrue($this->nodeTypeManager->getNodeType('TYPO3.TYPO3CR.Testing:MyFinalType')->isFinal());
    }

    /**
     * @test
     */
    public function aggregateNodeTypeFlagIsFalseByDefault()
    {
        $this->assertFalse($this->nodeTypeManager->getNodeType('TYPO3.TYPO3CR.Testing:Text')->isAggregate());
    }

    /**
     * @test
     */
    public function aggregateNodeTypeFlagIsInherited()
    {
        $this->assertTrue($this->nodeTypeManager->getNodeType('TYPO3.TYPO3CR.Testing:Document')->isAggregate());
        $this->assertTrue($this->nodeTypeManager->getNodeType('TYPO3.TYPO3CR.Testing:Page')->isAggregate());
    }

    /**
     * @test
     * @expectedException \TYPO3\TYPO3CR\Exception\NodeTypeIsFinalException
     */
    public function getNodeTypeThrowsExceptionIfFinalNodeTypeIsSubclassed()
    {
        $this->nodeTypeManager = new NodeTypeManager();
        $nodeTypesFixture = array(
            'TYPO3.TYPO3CR.Testing:Base' => array(
                'final' => true
            ),
            'TYPO3.TYPO3CR.Testing:Sub' => array(
                'superTypes' => array('TYPO3.TYPO3CR.Testing:Base' => true)
            )
        );
        $mockConfigurationManager = $this->getMockBuilder(ConfigurationManager::class)->disableOriginalConstructor()->getMock();
        $mockConfigurationManager->expects($this->atLeastOnce())->method('getConfiguration')->with('NodeTypes')->will($this->returnValue($nodeTypesFixture));
        $this->inject($this->nodeTypeManager, 'configurationManager', $mockConfigurationManager);

        $this->nodeTypeManager->getNodeType('TYPO3.TYPO3CR.Testing:Sub');
    }
}
