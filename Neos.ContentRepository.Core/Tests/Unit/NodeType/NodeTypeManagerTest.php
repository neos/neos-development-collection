<?php
namespace Neos\ContentRepository\Core\Tests\Unit\NodeType;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\ContentRepository\Core\NodeType\DefaultNodeLabelGeneratorFactory;
use Neos\ContentRepository\Core\SharedModel\Exception\NodeConfigurationException;
use Neos\ContentRepository\Core\SharedModel\Exception\NodeTypeIsFinalException;
use Neos\ContentRepository\Core\SharedModel\Exception\NodeTypeNotFoundException;
use Neos\ContentRepository\Core\NodeType\NodeTypeManager;
use PHPUnit\Framework\TestCase;

/**
 * Testcase for the "NodeTypeManager"
 */
class NodeTypeManagerTest extends TestCase
{
    /**
     * @var NodeTypeManager
     */
    protected $nodeTypeManager;

    public function setUp(): void
    {
        $this->prepareNodeTypeManager($this->nodeTypesFixture);
    }

    /**
     * Prepares $this->nodeTypeManager with a fresh instance with all mocks and makes the given fixture data available as NodeTypes configuration
     *
     * @param array $nodeTypesFixtureData
     */
    protected function prepareNodeTypeManager(array $nodeTypesFixtureData, string $fallbackNodeTypeName = '')
    {
        $this->nodeTypeManager = new NodeTypeManager(
            fn() => $nodeTypesFixtureData,
            new DefaultNodeLabelGeneratorFactory(),
            $fallbackNodeTypeName
        );
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
                'Neos.ContentRepository.Testing:Document' => true,
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
        $this->prepareNodeTypeManager($this->nodeTypesFixture, 'Neos.ContentRepository:NonExistingFallbackNode');
        $this->nodeTypeManager->getNodeType('Neos.ContentRepository.Testing:TextFooBarNotHere');
    }

    /**
     * @test
     */
    public function getNodeTypeReturnsFallbackNodeTypeIfConfigured()
    {
        $this->prepareNodeTypeManager($this->nodeTypesFixture, 'Neos.ContentRepository:FallbackNode');

        $expectedNodeType = $this->nodeTypeManager->getNodeType('Neos.ContentRepository:FallbackNode');
        $fallbackNodeType = $this->nodeTypeManager->getNodeType('Neos.ContentRepository.Testing:TextFooBarNotHere');
        self::assertSame($expectedNodeType, $fallbackNodeType);
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
        $this->expectException(NodeTypeIsFinalException::class);
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
    public function arraySupertypesFormatThrowsException()
    {
        $this->expectException(NodeConfigurationException::class);
        $nodeTypesFixture = [
            'Neos.ContentRepository.Testing:Base' => [
                'final' => true
            ],
            'Neos.ContentRepository.Testing:Sub' => [
                'superTypes' => [0 => 'Neos.ContentRepository.Testing:Base']
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
