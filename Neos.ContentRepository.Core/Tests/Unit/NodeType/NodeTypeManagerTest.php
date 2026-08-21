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
use PHPUnit\Framework\Attributes\Test;
use Neos\ContentRepository\Core\NodeType\NodeType;
use Neos\ContentRepository\Core\NodeType\NodeTypeManager;
use Neos\ContentRepository\Core\NodeType\NodeTypeName;
use Neos\ContentRepository\Core\SharedModel\Exception\NodeConfigurationException;
use Neos\ContentRepository\Core\SharedModel\Exception\NodeTypeIsFinalException;
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
    protected function prepareNodeTypeManager(array $nodeTypesFixtureData)
    {
        $this->nodeTypeManager = NodeTypeManager::createFromArrayConfiguration(
            $nodeTypesFixtureData
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
        ],
        'Neos.ContentRepository.Testing:Page' => [
            'superTypes' => ['Neos.ContentRepository.Testing:Document' => true],
        ],
        'Neos.ContentRepository.Testing:Page2' => [
            'superTypes' => ['Neos.ContentRepository.Testing:Document' => true],
            'childNodes' => [
                'nodeName' => [
                    'type' => 'Neos.ContentRepository.Testing:Document'
                ]
            ]
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
        ]
    ];

    #[Test]
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

    #[Test]
    public function getNodeTypeReturnsNullForUnknownNodeType()
    {
        self::assertNull($this->nodeTypeManager->getNodeType('Neos.ContentRepository.Testing:TextFooBarNotHere'));
    }

    #[Test]
    public function hasNodeTypeReturnsTrueIfTheGivenNodeTypeIsFound()
    {
        self::assertTrue($this->nodeTypeManager->hasNodeType('Neos.ContentRepository.Testing:TextWithImage'));
    }

    #[Test]
    public function hasNodeTypeReturnsFalseIfTheGivenNodeTypeIsNotFound()
    {
        self::assertFalse($this->nodeTypeManager->hasNodeType('Neos.ContentRepository.Testing:TextFooBarNotHere'));
    }

    #[Test]
    public function hasNodeTypeReturnsTrueForAbstractNodeTypes()
    {
        self::assertTrue($this->nodeTypeManager->hasNodeType('Neos.ContentRepository.Testing:ContentObject'));
    }

    #[Test]
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
            'Neos.ContentRepository:Root' // is always present
        ];
        self::assertEquals($expectedNodeTypes, array_keys($this->nodeTypeManager->getNodeTypes()));
    }

    #[Test]
    public function getNodeTypesContainsAbstractNodeTypes()
    {
        $nodeTypes = $this->nodeTypeManager->getNodeTypes();
        self::assertArrayHasKey('Neos.ContentRepository.Testing:ContentObject', $nodeTypes);
    }

    #[Test]
    public function getNodeTypesWithoutIncludeAbstractContainsNoAbstractNodeTypes()
    {
        $nodeTypes = $this->nodeTypeManager->getNodeTypes(false);
        self::assertArrayNotHasKey('Neos.ContentRepository.Testing:ContentObject', $nodeTypes);
    }

    #[Test]
    public function getSubNodeTypesReturnsInheritedNodeTypes()
    {
        $nodeTypes = $this->nodeTypeManager->getSubNodeTypes('Neos.ContentRepository.Testing:ContentObject');
        self::assertArrayHasKey('Neos.ContentRepository.Testing:TextWithImage', $nodeTypes);
    }

    #[Test]
    public function getSubNodeTypesContainsAbstractNodeTypes()
    {
        $nodeTypes = $this->nodeTypeManager->getSubNodeTypes('Neos.ContentRepository.Testing:ContentObject');
        self::assertArrayHasKey('Neos.ContentRepository.Testing:AbstractType', $nodeTypes);
    }

    #[Test]
    public function getSubNodeTypesWithoutIncludeAbstractContainsNoAbstractNodeTypes()
    {
        $nodeTypes = $this->nodeTypeManager->getSubNodeTypes('Neos.ContentRepository.Testing:ContentObject', false);
        self::assertArrayNotHasKey('Neos.ContentRepository.Testing:AbstractType', $nodeTypes);
    }

    #[Test]
    public function getNodeTypeAllowsToRetrieveFinalNodeTypes()
    {
        self::assertTrue($this->nodeTypeManager->getNodeType('Neos.ContentRepository.Testing:MyFinalType')->isFinal());
    }

    #[Test]
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

    #[Test]
    public function arraySupertypesFormatThrowsException()
    {
        $this->expectException(NodeConfigurationException::class);
        $nodeTypesFixture = [
            'Neos.ContentRepository.Testing:Base' => [
            ],
            'Neos.ContentRepository.Testing:Sub' => [
                'superTypes' => [0 => 'Neos.ContentRepository.Testing:Base']
            ]
        ];

        $this->prepareNodeTypeManager($nodeTypesFixture);
        $this->nodeTypeManager->getNodeType('Neos.ContentRepository.Testing:Sub');
    }


    #[Test]
    public function getSubNodeTypesWithDifferentIncludeFlagValuesReturnsCorrectValues()
    {
        $subNodeTypes = $this->nodeTypeManager->getSubNodeTypes('Neos.ContentRepository.Testing:ContentObject', true);
        self::assertArrayHasKey('Neos.ContentRepository.Testing:AbstractType', $subNodeTypes);

        $subNodeTypes = $this->nodeTypeManager->getSubNodeTypes('Neos.ContentRepository.Testing:ContentObject', false);
        self::assertArrayNotHasKey('Neos.ContentRepository.Testing:AbstractType', $subNodeTypes);
    }

    #[Test]
    public function anInheritedNodeTypePropertyCannotBeUnset(): void
    {
        $nodeTypesFixture = [
            'Neos.ContentRepository.Testing:Base' => [
                'properties' => [
                    'foo' => [
                        'type' => 'boolean',
                    ]
                ]
            ],
            'Neos.ContentRepository.Testing:Sub' => [
                'superTypes' => ['Neos.ContentRepository.Testing:Base' => true],
                'properties' => [
                    'foo' => null
                ]
            ]
        ];

        $this->prepareNodeTypeManager($nodeTypesFixture);
        $nodeType = $this->nodeTypeManager->getNodeType('Neos.ContentRepository.Testing:Sub');

        self::assertSame(['foo' => ['type' => 'boolean']], $nodeType->getProperties());
    }

    #[Test]
    public function allInheritedNodeTypePropertiesCannotBeUnset(): void
    {
        $nodeTypesFixture = [
            'Neos.ContentRepository.Testing:Base' => [
                'properties' => [
                    'foo' => [
                        'type' => 'boolean',
                    ]
                ]
            ],
            'Neos.ContentRepository.Testing:Sub' => [
                'superTypes' => ['Neos.ContentRepository.Testing:Base' => true],
                'properties' => null
            ]
        ];

        $this->prepareNodeTypeManager($nodeTypesFixture);
        $nodeType = $this->nodeTypeManager->getNodeType('Neos.ContentRepository.Testing:Sub');

        self::assertSame(['foo' => ['type' => 'boolean']], $nodeType->getProperties());
    }

    #[Test]
    public function anInheritedNodeTypePropertyCanBeOverruledWithEmptyArray(): void
    {
        $nodeTypesFixture = [
            'Neos.ContentRepository.Testing:Base' => [
                'properties' => [
                    'foo' => [
                        'type' => 'boolean',
                        'ui' => [
                            'inspector' => [
                                'group' => 'things'
                            ]
                        ]
                    ]
                ]
            ],
            'Neos.ContentRepository.Testing:Sub' => [
                'superTypes' => ['Neos.ContentRepository.Testing:Base' => true],
                'properties' => [
                    // Pseudo unset.
                    // The property will still be existent but looses its type information (falls back to string).
                    // Also, the property will not show up anymore in the ui as the inspector configuration is gone as well.
                    'foo' => []
                ]
            ]
        ];

        $this->prepareNodeTypeManager($nodeTypesFixture);
        $nodeType = $this->nodeTypeManager->getNodeType('Neos.ContentRepository.Testing:Sub');

        self::assertSame(['foo' => []], $nodeType->getProperties());
        self::assertSame('string', $nodeType->getPropertyType('foo'));
    }

    #[Test]
    public function getAutoCreatedChildNodesReturnsLowercaseNames()
    {
        $parentNodeType = $this->nodeTypeManager->getNodeType(NodeTypeName::fromString('Neos.ContentRepository.Testing:Page2'));
        // This is configured as "nodeName" above, but should be normalized to "nodename"
        self::assertNotNull($parentNodeType->tetheredNodeTypeDefinitions->contain('nodename'));
    }

    #[Test]
    public function rootNodeTypeIsAlwaysPresent()
    {
        $nodeTypeManager = NodeTypeManager::createFromArrayConfiguration(
            []
        );
        self::assertTrue($nodeTypeManager->hasNodeType(NodeTypeName::ROOT_NODE_TYPE_NAME));
        self::assertInstanceOf(NodeType::class, $nodeTypeManager->getNodeType(NodeTypeName::ROOT_NODE_TYPE_NAME));
    }
}
