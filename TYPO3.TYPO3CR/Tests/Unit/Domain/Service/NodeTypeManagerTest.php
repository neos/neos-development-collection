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

use TYPO3\Flow\Tests\UnitTestCase;
use TYPO3\TYPO3CR\Domain\Service\NodeTypeManager;

/**
 * Testcase for the "NodeTypeManager"
 */
class NodeTypeManagerTest extends UnitTestCase
{
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
            'superTypes' => array('TYPO3.TYPO3CR.Testing:ContentObject'),
            'final' => true
        ),
        'TYPO3.TYPO3CR.Testing:AbstractType' => array(
            'superTypes' => array('TYPO3.TYPO3CR.Testing:ContentObject'),
            'ui' => array(
                'label' => 'Abstract type',
            ),
            'abstract' => true
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
        ),
        'TYPO3.TYPO3CR.Testing:TextWithImage' => array(
            'superTypes' => array('TYPO3.TYPO3CR.Testing:Text'),
            'ui' => array(
                'label' => 'Text with image',
            ),
            'properties' => array(
                'image' => array(
                    'type' => 'TYPO3\Neos\Domain\Model\Media\Image',
                    'label' => 'Image'
                )
            )
        )
    );

    /**
     * A mock configuration manager
     *
     * @var \TYPO3\Flow\Configuration\ConfigurationManager
     */
    protected $configurationManager;

    public function setUp($nodeTypesFixture = null)
    {
        if ($nodeTypesFixture === null) {
            $nodeTypesFixture = $this->nodeTypesFixture;
        }
        $this->configurationManager = $this->getMockBuilder('TYPO3\Flow\Configuration\ConfigurationManager')
            ->disableOriginalConstructor()
            ->getMock();
        $this->configurationManager
            ->expects($this->any())
            ->method('getConfiguration')
            ->with('NodeTypes')
            ->will($this->returnValue($nodeTypesFixture));
    }

    /**
     * @test
     */
    public function nodeTypeConfigurationIsMergedTogether()
    {
        $nodeTypeManager = new NodeTypeManager();
        $this->inject($nodeTypeManager, 'configurationManager', $this->configurationManager);

        $nodeType = $nodeTypeManager->getNodeType('TYPO3.TYPO3CR.Testing:Text');
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
        $nodeTypeManager = new NodeTypeManager();
        $this->inject($nodeTypeManager, 'configurationManager', $this->configurationManager);

        $nodeTypeManager->getNodeType('TYPO3.TYPO3CR.Testing:TextFooBarNotHere');
    }

    /**
     * @test
     * @expectedException \TYPO3\TYPO3CR\Exception
     */
    public function createNodeTypeAlwaysThrowsAnException()
    {
        $nodeTypeManager = new \TYPO3\TYPO3CR\Domain\Service\NodeTypeManager();
        $this->inject($nodeTypeManager, 'configurationManager', $this->configurationManager);

        $nodeTypeManager->createNodeType('TYPO3.TYPO3CR.Testing:ContentObject');
    }

    /**
     * @test
     */
    public function hasNodeTypeReturnsTrueIfTheGivenNodeTypeIsFound()
    {
        $nodeTypeManager = new \TYPO3\TYPO3CR\Domain\Service\NodeTypeManager();
        $this->inject($nodeTypeManager, 'configurationManager', $this->configurationManager);

        $this->assertTrue($nodeTypeManager->hasNodeType('TYPO3.TYPO3CR.Testing:TextWithImage'));
    }

    /**
     * @test
     */
    public function hasNodeTypeReturnsFalseIfTheGivenNodeTypeIsNotFound()
    {
        $nodeTypeManager = new \TYPO3\TYPO3CR\Domain\Service\NodeTypeManager();
        $this->inject($nodeTypeManager, 'configurationManager', $this->configurationManager);

        $this->assertFalse($nodeTypeManager->hasNodeType('TYPO3.TYPO3CR.Testing:TextFooBarNotHere'));
    }

    /**
     * @test
     */
    public function hasNodeTypeReturnsTrueForAbstractNodeTypes()
    {
        $nodeTypeManager = new \TYPO3\TYPO3CR\Domain\Service\NodeTypeManager();
        $this->inject($nodeTypeManager, 'configurationManager', $this->configurationManager);

        $this->assertTrue($nodeTypeManager->hasNodeType('TYPO3.TYPO3CR.Testing:ContentObject'));
    }

    /**
     * @test
     */
    public function getNodeTypesReturnsRegisteredNodeTypes()
    {
        $nodeTypeManager = new NodeTypeManager();
        $this->inject($nodeTypeManager, 'configurationManager', $this->configurationManager);

        $expectedNodeTypes = array(
            'TYPO3.TYPO3CR.Testing:ContentObject',
            'TYPO3.TYPO3CR.Testing:MyFinalType',
            'TYPO3.TYPO3CR.Testing:AbstractType',
            'TYPO3.TYPO3CR.Testing:Text',
            'TYPO3.TYPO3CR.Testing:TextWithImage'
        );
        $this->assertEquals($expectedNodeTypes, array_keys($nodeTypeManager->getNodeTypes()));
    }

    /**
     * @test
     */
    public function getNodeTypesContainsAbstractNodeTypes()
    {
        $nodeTypeManager = new NodeTypeManager();
        $this->inject($nodeTypeManager, 'configurationManager', $this->configurationManager);

        $nodeTypes = $nodeTypeManager->getNodeTypes();
        $this->assertArrayHasKey('TYPO3.TYPO3CR.Testing:ContentObject', $nodeTypes);
    }

    /**
     * @test
     */
    public function getNodeTypesWithoutIncludeAbstractContainsNoAbstractNodeTypes()
    {
        $nodeTypeManager = new NodeTypeManager();
        $this->inject($nodeTypeManager, 'configurationManager', $this->configurationManager);

        $nodeTypes = $nodeTypeManager->getNodeTypes(false);
        $this->assertArrayNotHasKey('TYPO3.TYPO3CR.Testing:ContentObject', $nodeTypes);
    }

    /**
     * @test
     */
    public function getSubNodeTypesReturnsInheritedNodeTypes()
    {
        $nodeTypeManager = new NodeTypeManager();
        $this->inject($nodeTypeManager, 'configurationManager', $this->configurationManager);

        $nodeTypes = $nodeTypeManager->getSubNodeTypes('TYPO3.TYPO3CR.Testing:ContentObject');
        $this->assertArrayHasKey('TYPO3.TYPO3CR.Testing:TextWithImage', $nodeTypes);
    }

    /**
     * @test
     */
    public function getSubNodeTypesContainsAbstractNodeTypes()
    {
        $nodeTypeManager = new NodeTypeManager();
        $this->inject($nodeTypeManager, 'configurationManager', $this->configurationManager);

        $nodeTypes = $nodeTypeManager->getSubNodeTypes('TYPO3.TYPO3CR.Testing:ContentObject');
        $this->assertArrayHasKey('TYPO3.TYPO3CR.Testing:AbstractType', $nodeTypes);
    }

    /**
     * @test
     */
    public function getSubNodeTypesWithoutIncludeAbstractContainsNoAbstractNodeTypes()
    {
        $nodeTypeManager = new NodeTypeManager();
        $this->inject($nodeTypeManager, 'configurationManager', $this->configurationManager);

        $nodeTypes = $nodeTypeManager->getSubNodeTypes('TYPO3.TYPO3CR.Testing:ContentObject', false);
        $this->assertArrayNotHasKey('TYPO3.TYPO3CR.Testing:AbstractType', $nodeTypes);
    }

    /**
     * @test
     */
    public function getNodeTypeAllowsToRetrieveFinalNodeTypes()
    {
        $nodeTypeManager = new NodeTypeManager();
        $this->inject($nodeTypeManager, 'configurationManager', $this->configurationManager);
        $this->assertTrue($nodeTypeManager->getNodeType('TYPO3.TYPO3CR.Testing:MyFinalType')->isFinal());
    }

    /**
     * @test
     * @expectedException \TYPO3\TYPO3CR\Exception\NodeTypeIsFinalException
     */
    public function getNodeTypeThrowsExceptionIfFinalNodeTypeIsSubclassed()
    {
        $nodeTypeManager = new NodeTypeManager();
        $this->setUp(array(
            'TYPO3.TYPO3CR.Testing:Base' => array(
                'final' => true
            ),
            'TYPO3.TYPO3CR.Testing:Sub' => array(
                'superTypes' => array('TYPO3.TYPO3CR.Testing:Base')
            )
        ));
        $this->inject($nodeTypeManager, 'configurationManager', $this->configurationManager);

        $nodeTypeManager->getNodeType('TYPO3.TYPO3CR.Testing:Sub');
    }
}
