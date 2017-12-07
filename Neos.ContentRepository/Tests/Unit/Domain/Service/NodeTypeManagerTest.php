<?php
namespace Neos\ContentRepository\Tests\Unit\Domain\Service;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Cache\Frontend\StringFrontend;
use Neos\Flow\Configuration\ConfigurationManager;
use Neos\Flow\Tests\UnitTestCase;
use Neos\ContentRepository\Domain\Service\NodeTypeManager;

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
        $this->prepareNodeTypeManager($this->nodeTypesFixture);
    }

    /**
     * Prepares $this->nodeTypeManager with a fresh instance with all mocks and makes the given fixture data available as NodeTypes configuration
     *
     * @param array $nodeTypesFixtureData
     */
    protected function prepareNodeTypeManager(array $nodeTypesFixtureData)
    {
        $this->nodeTypeManager = new NodeTypeManager();

        $this->mockConfigurationManager = $this->getMockBuilder(ConfigurationManager::class)->disableOriginalConstructor()->getMock();

        $mockCache = $this->getMockBuilder(StringFrontend::class)->disableOriginalConstructor()->getMock();
        $mockCache->expects($this->any())->method('get')->willReturn(null);
        $this->inject($this->nodeTypeManager, 'fullConfigurationCache', $mockCache);

        $this->mockConfigurationManager->expects($this->any())->method('getConfiguration')->with('NodeTypes')->will($this->returnValue($nodeTypesFixtureData));
        $this->inject($this->nodeTypeManager, 'configurationManager', $this->mockConfigurationManager);
    }

    /**
     * example node types
     *
     * @var array
     */
    protected $nodeTypesFixture = array(
        'Neos.ContentRepository.Testing:ContentObject' => array(
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
        'Neos.ContentRepository.Testing:MyFinalType' => array(
            'superTypes' => array('Neos.ContentRepository.Testing:ContentObject' => true),
            'final' => true
        ),
        'Neos.ContentRepository.Testing:AbstractType' => array(
            'superTypes' => array('Neos.ContentRepository.Testing:ContentObject' => true),
            'ui' => array(
                'label' => 'Abstract type',
            ),
            'abstract' => true
        ),
        'Neos.ContentRepository.Testing:Text' => array(
            'superTypes' => array('Neos.ContentRepository.Testing:ContentObject' => true),
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
        'Neos.ContentRepository.Testing:TextWithImage' => array(
            'superTypes' => array('Neos.ContentRepository.Testing:Text' => true),
            'ui' => array(
                'label' => 'Text with image',
            ),
            'properties' => array(
                'image' => array(
                    'type' => 'Neos\Neos\Domain\Model\Media\Image',
                    'label' => 'Image'
                )
            )
        ),
        'Neos.ContentRepository.Testing:Document' => array(
            'abstract' => true,
            'aggregate' => true
        ),
        'Neos.ContentRepository.Testing:Page' => array(
            'superTypes' => array('Neos.ContentRepository.Testing:Document' => true),
        ),
        'Neos.ContentRepository.Testing:Page2' => array(
            'superTypes' => array('Neos.ContentRepository.Testing:Document' => true),
        ),
        'Neos.ContentRepository.Testing:Page3' => array(
            'superTypes' => array('Neos.ContentRepository.Testing:Document' => true),
        ),
        'Neos.ContentRepository.Testing:DocumentWithSupertypes' => array(
            'superTypes' => array(
                0 => 'Neos.ContentRepository.Testing:Document',
                'Neos.ContentRepository.Testing:Page' => true,
                'Neos.ContentRepository.Testing:Page2' => false,
                'Neos.ContentRepository.Testing:Page3' => null
            )
        ),
        'Neos.ContentRepository:FallbackNode' => array()
    );

    /**
     * @test
     */
    public function nodeTypeConfigurationIsMergedTogether()
    {
        $nodeType = $this->nodeTypeManager->getNodeType('Neos.ContentRepository.Testing:Text');
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
     * @expectedException \Neos\ContentRepository\Exception\NodeTypeNotFoundException
     */
    public function getNodeTypeThrowsExceptionForUnknownNodeType()
    {
        $this->nodeTypeManager->getNodeType('Neos.ContentRepository.Testing:TextFooBarNotHere');
    }

    /**
     * @test
     * @expectedException \Neos\ContentRepository\Exception\NodeTypeNotFoundException
     */
    public function getNodeTypeThrowsExceptionIfNoFallbackNodeTypeIsConfigured()
    {
        $this->nodeTypeManager->getNodeType('Neos.ContentRepository.Testing:TextFooBarNotHere');
    }

    /**
     * @test
     * @expectedException \Neos\ContentRepository\Exception\NodeTypeNotFoundException
     */
    public function getNodeTypeThrowsExceptionIfConfiguredFallbackNodeTypeCantBeFound()
    {
        $this->inject($this->nodeTypeManager, 'fallbackNodeTypeName', 'Neos.ContentRepository:NonExistingFallbackNode');
        $this->nodeTypeManager->getNodeType('Neos.ContentRepository.Testing:TextFooBarNotHere');
    }

    /**
     * @test
     */
    public function getNodeTypeReturnsFallbackNodeTypeIfConfigured()
    {
        $this->inject($this->nodeTypeManager, 'fallbackNodeTypeName', 'Neos.ContentRepository:FallbackNode');

        $expectedNodeType = $this->nodeTypeManager->getNodeType('Neos.ContentRepository:FallbackNode');
        $fallbackNodeType = $this->nodeTypeManager->getNodeType('Neos.ContentRepository.Testing:TextFooBarNotHere');
        $this->assertSame($expectedNodeType, $fallbackNodeType);
    }

    /**
     * @test
     * @expectedException \Neos\ContentRepository\Exception
     */
    public function createNodeTypeAlwaysThrowsAnException()
    {
        $this->nodeTypeManager->createNodeType('Neos.ContentRepository.Testing:ContentObject');
    }

    /**
     * @test
     */
    public function hasNodeTypeReturnsTrueIfTheGivenNodeTypeIsFound()
    {
        $this->assertTrue($this->nodeTypeManager->hasNodeType('Neos.ContentRepository.Testing:TextWithImage'));
    }

    /**
     * @test
     */
    public function hasNodeTypeReturnsFalseIfTheGivenNodeTypeIsNotFound()
    {
        $this->assertFalse($this->nodeTypeManager->hasNodeType('Neos.ContentRepository.Testing:TextFooBarNotHere'));
    }

    /**
     * @test
     */
    public function hasNodeTypeReturnsTrueForAbstractNodeTypes()
    {
        $this->assertTrue($this->nodeTypeManager->hasNodeType('Neos.ContentRepository.Testing:ContentObject'));
    }

    /**
     * @test
     */
    public function getNodeTypesReturnsRegisteredNodeTypes()
    {
        $expectedNodeTypes = array(
            'Neos.ContentRepository.Testing:ContentObject',
            'Neos.ContentRepository.Testing:MyFinalType',
            'Neos.ContentRepository.Testing:AbstractType',
            'Neos.ContentRepository.Testing:Text',
            'Neos.ContentRepository.Testing:TextWithImage',
            'Neos.ContentRepository.Testing:Document',
            'Neos.ContentRepository.Testing:Page',
            'Neos.ContentRepository.Testing:Page2',
            'Neos.ContentRepository.Testing:Page3',
            'Neos.ContentRepository.Testing:DocumentWithSupertypes',
            'Neos.ContentRepository:FallbackNode'
        );
        $this->assertEquals($expectedNodeTypes, array_keys($this->nodeTypeManager->getNodeTypes()));
    }

    /**
     * @test
     */
    public function getNodeTypesContainsAbstractNodeTypes()
    {
        $nodeTypes = $this->nodeTypeManager->getNodeTypes();
        $this->assertArrayHasKey('Neos.ContentRepository.Testing:ContentObject', $nodeTypes);
    }

    /**
     * @test
     */
    public function getNodeTypesWithoutIncludeAbstractContainsNoAbstractNodeTypes()
    {
        $nodeTypes = $this->nodeTypeManager->getNodeTypes(false);
        $this->assertArrayNotHasKey('Neos.ContentRepository.Testing:ContentObject', $nodeTypes);
    }

    /**
     * @test
     */
    public function getSubNodeTypesReturnsInheritedNodeTypes()
    {
        $nodeTypes = $this->nodeTypeManager->getSubNodeTypes('Neos.ContentRepository.Testing:ContentObject');
        $this->assertArrayHasKey('Neos.ContentRepository.Testing:TextWithImage', $nodeTypes);
    }

    /**
     * @test
     */
    public function getSubNodeTypesContainsAbstractNodeTypes()
    {
        $nodeTypes = $this->nodeTypeManager->getSubNodeTypes('Neos.ContentRepository.Testing:ContentObject');
        $this->assertArrayHasKey('Neos.ContentRepository.Testing:AbstractType', $nodeTypes);
    }

    /**
     * @test
     */
    public function getSubNodeTypesWithoutIncludeAbstractContainsNoAbstractNodeTypes()
    {
        $nodeTypes = $this->nodeTypeManager->getSubNodeTypes('Neos.ContentRepository.Testing:ContentObject', false);
        $this->assertArrayNotHasKey('Neos.ContentRepository.Testing:AbstractType', $nodeTypes);
    }

    /**
     * @test
     */
    public function getNodeTypeAllowsToRetrieveFinalNodeTypes()
    {
        $this->assertTrue($this->nodeTypeManager->getNodeType('Neos.ContentRepository.Testing:MyFinalType')->isFinal());
    }

    /**
     * @test
     */
    public function aggregateNodeTypeFlagIsFalseByDefault()
    {
        $this->assertFalse($this->nodeTypeManager->getNodeType('Neos.ContentRepository.Testing:Text')->isAggregate());
    }

    /**
     * @test
     */
    public function aggregateNodeTypeFlagIsInherited()
    {
        $this->assertTrue($this->nodeTypeManager->getNodeType('Neos.ContentRepository.Testing:Document')->isAggregate());
        $this->assertTrue($this->nodeTypeManager->getNodeType('Neos.ContentRepository.Testing:Page')->isAggregate());
    }

    /**
     * @test
     * @expectedException \Neos\ContentRepository\Exception\NodeTypeIsFinalException
     */
    public function getNodeTypeThrowsExceptionIfFinalNodeTypeIsSubclassed()
    {
        $nodeTypesFixture = array(
            'Neos.ContentRepository.Testing:Base' => array(
                'final' => true
            ),
            'Neos.ContentRepository.Testing:Sub' => array(
                'superTypes' => array('Neos.ContentRepository.Testing:Base' => true)
            )
        );

        $this->prepareNodeTypeManager($nodeTypesFixture);
        $this->nodeTypeManager->getNodeType('Neos.ContentRepository.Testing:Sub');
    }
}
