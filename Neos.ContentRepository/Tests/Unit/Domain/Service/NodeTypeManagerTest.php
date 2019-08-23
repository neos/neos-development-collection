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
use Neos\ContentRepository\Exception;
use Neos\ContentRepository\Exception\NodeTypeNotFoundException;
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
     * @var ConfigurationManager|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $mockConfigurationManager;

    public function setUp(): void
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
        $mockCache->expects(self::any())->method('get')->willReturn(null);
        $this->inject($this->nodeTypeManager, 'fullConfigurationCache', $mockCache);

        $this->mockConfigurationManager->expects(self::any())->method('getConfiguration')->with('NodeTypes')->will(self::returnValue($nodeTypesFixtureData));
        $this->inject($this->nodeTypeManager, 'configurationManager', $this->mockConfigurationManager);
    }

    /**
     * example node types
     *
     * @var array
     */
    protected $nodeTypesFixture = [
        'Neos.ContentRepository.Testing:ContentObject' => [
            'ui' => [
                'label' => 'Abstract content object',
            ],
            'abstract' => true,
            'properties' => [
                '_hidden' => [
                    'type' => 'boolean',
                    'label' => 'Hidden',
                    'category' => 'visibility',
                    'priority' => 1
                ],
            ],
            'propertyGroups' => [
                'visibility' => [
                    'label' => 'Visibility',
                    'priority' => 1
                ]
            ]
        ],
        'Neos.ContentRepository.Testing:MyFinalType' => [
            'superTypes' => ['Neos.ContentRepository.Testing:ContentObject' => true],
            'final' => true
        ],
        'Neos.ContentRepository.Testing:AbstractType' => [
            'superTypes' => ['Neos.ContentRepository.Testing:ContentObject' => true],
            'ui' => [
                'label' => 'Abstract type',
            ],
            'abstract' => true
        ],
        'Neos.ContentRepository.Testing:Text' => [
            'superTypes' => ['Neos.ContentRepository.Testing:ContentObject' => true],
            'ui' => [
                'label' => 'Text',
            ],
            'properties' => [
                'headline' => [
                    'type' => 'string',
                    'placeholder' => 'Enter headline here'
                ],
                'text' => [
                    'type' => 'string',
                    'placeholder' => '<p>Enter text here</p>'
                ]
            ],
            'inlineEditableProperties' => ['headline', 'text']
        ],
        'Neos.ContentRepository.Testing:TextWithImage' => [
            'superTypes' => ['Neos.ContentRepository.Testing:Text' => true],
            'ui' => [
                'label' => 'Text with image',
            ],
            'properties' => [
                'image' => [
                    'type' => 'Neos\Neos\Domain\Model\Media\Image',
                    'label' => 'Image'
                ]
            ]
        ],
        'Neos.ContentRepository.Testing:Document' => [
            'abstract' => true,
            'aggregate' => true
        ],
        'Neos.ContentRepository.Testing:Page' => [
            'superTypes' => ['Neos.ContentRepository.Testing:Document' => true],
        ],
        'Neos.ContentRepository.Testing:Page2' => [
            'superTypes' => ['Neos.ContentRepository.Testing:Document' => true],
        ],
        'Neos.ContentRepository.Testing:Page3' => [
            'superTypes' => ['Neos.ContentRepository.Testing:Document' => true],
        ],
        'Neos.ContentRepository.Testing:DocumentWithSupertypes' => [
            'superTypes' => [
                0 => 'Neos.ContentRepository.Testing:Document',
                'Neos.ContentRepository.Testing:Page' => true,
                'Neos.ContentRepository.Testing:Page2' => false,
                'Neos.ContentRepository.Testing:Page3' => null
            ]
        ],
        'Neos.ContentRepository:FallbackNode' => []
    ];

    /**
     * @test
     */
    public function nodeTypeConfigurationIsMergedTogether()
    {
        $nodeType = $this->nodeTypeManager->getNodeType('Neos.ContentRepository.Testing:Text');
        self::assertSame('Text', $nodeType->getLabel());

        $expectedProperties = [
            '_hidden' => [
                'type' => 'boolean',
                'label' => 'Hidden',
                'category' => 'visibility',
                'priority' => 1
            ],
            'headline' => [
                'type' => 'string',
                'placeholder' => 'Enter headline here'
            ],
            'text' => [
                'type' => 'string',
                'placeholder' => '<p>Enter text here</p>'
            ]
        ];
        self::assertSame($expectedProperties, $nodeType->getProperties());
    }

    /**
     * @test
     */
    public function getNodeTypeThrowsExceptionForUnknownNodeType()
    {
        $this->expectException(NodeTypeNotFoundException::class);
        $this->nodeTypeManager->getNodeType('Neos.ContentRepository.Testing:TextFooBarNotHere');
    }

    /**
     * @test
     */
    public function getNodeTypeThrowsExceptionIfNoFallbackNodeTypeIsConfigured()
    {
        $this->expectException(NodeTypeNotFoundException::class);
        $this->nodeTypeManager->getNodeType('Neos.ContentRepository.Testing:TextFooBarNotHere');
    }

    /**
     * @test
     */
    public function getNodeTypeThrowsExceptionIfConfiguredFallbackNodeTypeCantBeFound()
    {
        $this->expectException(NodeTypeNotFoundException::class);
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
        self::assertSame($expectedNodeType, $fallbackNodeType);
    }

    /**
     * @test
     */
    public function createNodeTypeAlwaysThrowsAnException()
    {
        $this->expectException(Exception::class);
        $this->nodeTypeManager->createNodeType('Neos.ContentRepository.Testing:ContentObject');
    }

    /**
     * @test
     */
    public function hasNodeTypeReturnsTrueIfTheGivenNodeTypeIsFound()
    {
        self::assertTrue($this->nodeTypeManager->hasNodeType('Neos.ContentRepository.Testing:TextWithImage'));
    }

    /**
     * @test
     */
    public function hasNodeTypeReturnsFalseIfTheGivenNodeTypeIsNotFound()
    {
        self::assertFalse($this->nodeTypeManager->hasNodeType('Neos.ContentRepository.Testing:TextFooBarNotHere'));
    }

    /**
     * @test
     */
    public function hasNodeTypeReturnsTrueForAbstractNodeTypes()
    {
        self::assertTrue($this->nodeTypeManager->hasNodeType('Neos.ContentRepository.Testing:ContentObject'));
    }

    /**
     * @test
     */
    public function getNodeTypesReturnsRegisteredNodeTypes()
    {
        $expectedNodeTypes = [
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
        ];
        self::assertEquals($expectedNodeTypes, array_keys($this->nodeTypeManager->getNodeTypes()));
    }

    /**
     * @test
     */
    public function getNodeTypesContainsAbstractNodeTypes()
    {
        $nodeTypes = $this->nodeTypeManager->getNodeTypes();
        self::assertArrayHasKey('Neos.ContentRepository.Testing:ContentObject', $nodeTypes);
    }

    /**
     * @test
     */
    public function getNodeTypesWithoutIncludeAbstractContainsNoAbstractNodeTypes()
    {
        $nodeTypes = $this->nodeTypeManager->getNodeTypes(false);
        self::assertArrayNotHasKey('Neos.ContentRepository.Testing:ContentObject', $nodeTypes);
    }

    /**
     * @test
     */
    public function getSubNodeTypesReturnsInheritedNodeTypes()
    {
        $nodeTypes = $this->nodeTypeManager->getSubNodeTypes('Neos.ContentRepository.Testing:ContentObject');
        self::assertArrayHasKey('Neos.ContentRepository.Testing:TextWithImage', $nodeTypes);
    }

    /**
     * @test
     */
    public function getSubNodeTypesContainsAbstractNodeTypes()
    {
        $nodeTypes = $this->nodeTypeManager->getSubNodeTypes('Neos.ContentRepository.Testing:ContentObject');
        self::assertArrayHasKey('Neos.ContentRepository.Testing:AbstractType', $nodeTypes);
    }

    /**
     * @test
     */
    public function getSubNodeTypesWithoutIncludeAbstractContainsNoAbstractNodeTypes()
    {
        $nodeTypes = $this->nodeTypeManager->getSubNodeTypes('Neos.ContentRepository.Testing:ContentObject', false);
        self::assertArrayNotHasKey('Neos.ContentRepository.Testing:AbstractType', $nodeTypes);
    }

    /**
     * @test
     */
    public function getNodeTypeAllowsToRetrieveFinalNodeTypes()
    {
        self::assertTrue($this->nodeTypeManager->getNodeType('Neos.ContentRepository.Testing:MyFinalType')->isFinal());
    }

    /**
     * @test
     */
    public function aggregateNodeTypeFlagIsFalseByDefault()
    {
        self::assertFalse($this->nodeTypeManager->getNodeType('Neos.ContentRepository.Testing:Text')->isAggregate());
    }

    /**
     * @test
     */
    public function aggregateNodeTypeFlagIsInherited()
    {
        self::assertTrue($this->nodeTypeManager->getNodeType('Neos.ContentRepository.Testing:Document')->isAggregate());
        self::assertTrue($this->nodeTypeManager->getNodeType('Neos.ContentRepository.Testing:Page')->isAggregate());
    }

    /**
     * @test
     */
    public function getNodeTypeThrowsExceptionIfFinalNodeTypeIsSubclassed()
    {
        $this->expectException(Exception\NodeTypeIsFinalException::class);
        $nodeTypesFixture = [
            'Neos.ContentRepository.Testing:Base' => [
                'final' => true
            ],
            'Neos.ContentRepository.Testing:Sub' => [
                'superTypes' => ['Neos.ContentRepository.Testing:Base' => true]
            ]
        ];

        $this->prepareNodeTypeManager($nodeTypesFixture);
        $this->nodeTypeManager->getNodeType('Neos.ContentRepository.Testing:Sub');
    }

    /**
     * @test
     */
    public function getSubNodeTypesWithDifferentIncludeFlagValuesReturnsCorrectValues()
    {
        $subNodeTypes = $this->nodeTypeManager->getSubNodeTypes('Neos.ContentRepository.Testing:ContentObject', true);
        self::assertArrayHasKey('Neos.ContentRepository.Testing:AbstractType', $subNodeTypes);

        $subNodeTypes = $this->nodeTypeManager->getSubNodeTypes('Neos.ContentRepository.Testing:ContentObject', false);
        self::assertArrayNotHasKey('Neos.ContentRepository.Testing:AbstractType', $subNodeTypes);
    }
}
