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
use Neos\ContentRepository\Core\NodeType\NodeTypeName;
use Neos\ContentRepository\Core\NodeType\NodeType;
use Neos\ContentRepository\Core\NodeType\NodeTypeManager;
use PHPUnit\Framework\TestCase;

/**
 * Testcase for the "NodeType" domain model
 */
class NodeTypeTest extends TestCase
{
    /**
     * example node types
     *
     * @var array<string,mixed>
     */
    protected array $nodeTypesFixture = [
        'Neos.ContentRepository.Testing:ContentObject' => [
            'ui' => [
                'label' => 'Abstract content object'
            ],
            'abstract' => true,
            'properties' => [
                '_hidden' => [
                    'type' => 'boolean',
                    'label' => 'Hidden',
                    'category' => 'visibility',
                    'priority' => 1
                ]
            ],
            'propertyGroups' => [
                'visibility' => [
                    'label' => 'Visibility',
                    'priority' => 1
                ]
            ]
        ],
        'Neos.ContentRepository.Testing:Text' => [
            'superTypes' => ['Neos.ContentRepository.Testing:ContentObject' => true],
            'ui' => [
                'label' => 'Text'
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
        'Neos.ContentRepository.Testing:Document' => [
            'superTypes' => ['Neos.ContentRepository.Testing:SomeMixin' => true],
            'abstract' => true,
            'aggregate' => true
        ],
        'Neos.ContentRepository.Testing:SomeMixin' => [
            'ui' => [
                'label' => 'could contain an inspector tab'
            ],
            'properties' => [
                'someMixinProperty' => [
                    'type' => 'string',
                    'label' => 'Important hint'
                ]
            ]
        ],
        'Neos.ContentRepository.Testing:Shortcut' => [
            'superTypes' => [
                'Neos.ContentRepository.Testing:Document' => true,
                'Neos.ContentRepository.Testing:SomeMixin' => false
            ],
            'ui' => [
                'label' => 'Shortcut'
            ],
            'properties' => [
                'target' => [
                    'type' => 'string'
                ]
            ]
        ],
        'Neos.ContentRepository.Testing:SubShortcut' => [
            'superTypes' => [
                'Neos.ContentRepository.Testing:Shortcut' => true
            ],
            'ui' => [
                'label' => 'Sub-Shortcut'
            ]
        ],
        'Neos.ContentRepository.Testing:SubSubShortcut' => [
            'superTypes' => [
                // SomeMixin placed explicitly before SubShortcut
                'Neos.ContentRepository.Testing:SomeMixin' => true,
                'Neos.ContentRepository.Testing:SubShortcut' => true,
            ],
            'ui' => [
                'label' => 'Sub-Sub-Shortcut'
            ]
        ],
        'Neos.ContentRepository.Testing:SubSubSubShortcut' => [
            'superTypes' => [
                'Neos.ContentRepository.Testing:SubSubShortcut' => true
            ],
            'ui' => [
                'label' => 'Sub-Sub-Sub-Shortcut'
            ]
        ]
    ];

    /**
     * @test
     */
    public function aNodeTypeHasAName()
    {
        $nodeType = new NodeType(NodeTypeName::fromString('Neos.ContentRepository.Testing:Text'), [], [], new DefaultNodeLabelGeneratorFactory());
        self::assertSame('Neos.ContentRepository.Testing:Text', $nodeType->name->value);
    }

    /**
     * @test
     */
    public function setDeclaredSuperTypesExpectsAnArrayOfNodeTypesAsKeys()
    {
        $this->expectException(\InvalidArgumentException::class);
        new NodeType(NodeTypeName::fromString('ContentRepository:Folder'), ['foo' => true], [], new DefaultNodeLabelGeneratorFactory()
        );
    }

    /**
     * @test
     */
    public function setDeclaredSuperTypesAcceptsAnArrayOfNodeTypes()
    {
        $this->expectException(\InvalidArgumentException::class);
        new NodeType(NodeTypeName::fromString('ContentRepository:Folder'), ['foo'], [], new DefaultNodeLabelGeneratorFactory());
    }

    /**
     * @test
     */
    public function nodeTypesCanHaveAnyNumberOfSuperTypes()
    {
        $baseType = new NodeType(NodeTypeName::fromString('Neos.ContentRepository:Base'), [], [], new DefaultNodeLabelGeneratorFactory());

        $timeableNodeType = new NodeType(
            NodeTypeName::fromString('Neos.ContentRepository.Testing:TimeableContent'),
            [], [], new DefaultNodeLabelGeneratorFactory()
        );
        $documentType = new NodeType(
            NodeTypeName::fromString('Neos.ContentRepository.Testing:Document'),
            [
                'Neos.ContentRepository:Base' => $baseType,
                'Neos.ContentRepository.Testing:TimeableContent' => $timeableNodeType,
            ],
            [], new DefaultNodeLabelGeneratorFactory()
        );

        $hideableNodeType = new NodeType(
            NodeTypeName::fromString('Neos.ContentRepository.Testing:HideableContent'),
            [], [], new DefaultNodeLabelGeneratorFactory()
        );
        $pageType = new NodeType(
            NodeTypeName::fromString('Neos.ContentRepository.Testing:Page'),
            [
                'Neos.ContentRepository.Testing:Document' => $documentType,
                'Neos.ContentRepository.Testing:HideableContent' => $hideableNodeType,
                'Neos.ContentRepository.Testing:TimeableContent' => null,
            ],
            [],
            new DefaultNodeLabelGeneratorFactory()
        );

        self::assertEquals(
            [
                'Neos.ContentRepository.Testing:Document' => $documentType,
                'Neos.ContentRepository.Testing:HideableContent' => $hideableNodeType,
            ],
            $pageType->getDeclaredSuperTypes()
        );

        self::assertTrue($pageType->isOfType('Neos.ContentRepository.Testing:Page'));
        self::assertTrue($pageType->isOfType('Neos.ContentRepository.Testing:HideableContent'));
        self::assertTrue($pageType->isOfType('Neos.ContentRepository.Testing:Document'));
        self::assertTrue($pageType->isOfType('Neos.ContentRepository:Base'));

        self::assertFalse($pageType->isOfType('Neos.ContentRepository:Exotic'));
        self::assertFalse($pageType->isOfType('Neos.ContentRepository.Testing:TimeableContent'));
    }

    /**
     * @test
     */
    public function labelIsEmptyStringByDefault()
    {
        $baseType = new NodeType(NodeTypeName::fromString('Neos.ContentRepository:Base'), [], [], new DefaultNodeLabelGeneratorFactory());
        self::assertSame('', $baseType->getLabel());
    }

    /**
     * @test
     */
    public function propertiesAreEmptyArrayByDefault()
    {
        $baseType = new NodeType(NodeTypeName::fromString('Neos.ContentRepository:Base'), [], [], new DefaultNodeLabelGeneratorFactory());
        self::assertSame([], $baseType->getProperties());
    }

    /**
     * @test
     */
    public function hasConfigurationReturnsTrueIfSpecifiedConfigurationPathExists()
    {
        $nodeType = new NodeType(NodeTypeName::fromString('Neos.ContentRepository:Base'), [], [
            'someKey' => [
                'someSubKey' => 'someValue'
            ]
        ], new DefaultNodeLabelGeneratorFactory());
        self::assertTrue($nodeType->hasConfiguration('someKey.someSubKey'));
    }

    /**
     * @test
     */
    public function hasConfigurationReturnsFalseIfSpecifiedConfigurationPathDoesNotExist()
    {
        $nodeType = new NodeType(NodeTypeName::fromString('Neos.ContentRepository:Base'), [], [], new DefaultNodeLabelGeneratorFactory());
        self::assertFalse($nodeType->hasConfiguration('some.nonExisting.path'));
    }

    /**
     * @test
     */
    public function getConfigurationReturnsTheConfigurationWithTheSpecifiedPath()
    {
        $nodeType = new NodeType(NodeTypeName::fromString('Neos.ContentRepository:Base'), [], [
            'someKey' => [
                'someSubKey' => 'someValue'
            ]
        ], new DefaultNodeLabelGeneratorFactory());
        self::assertSame('someValue', $nodeType->getConfiguration('someKey.someSubKey'));
    }

    /**
     * @test
     */
    public function getConfigurationReturnsNullIfTheSpecifiedPathDoesNotExist()
    {
        $nodeType = new NodeType(NodeTypeName::fromString('Neos.ContentRepository:Base'), [], [], new DefaultNodeLabelGeneratorFactory());
        self::assertNull($nodeType->getConfiguration('some.nonExisting.path'));
    }

    /**
     * @test
     */
    public function nodeTypeConfigurationIsMergedTogether()
    {
        $nodeType = $this->getNodeType('Neos.ContentRepository.Testing:Text');
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
     * This test asserts that a supertype that has been inherited can be removed on a specific type again.
     * @test
     */
    public function inheritedSuperTypesCanBeRemoved()
    {
        $nodeType = $this->getNodeType('Neos.ContentRepository.Testing:Shortcut');
        self::assertSame('Shortcut', $nodeType->getLabel());

        $expectedProperties = [
            'target' => [
                'type' => 'string'
            ]
        ];
        self::assertSame($expectedProperties, $nodeType->getProperties());
    }

    /**
     * @test
     */
    public function isOfTypeReturnsFalseForDirectlyDisabledSuperTypes()
    {
        $nodeType = $this->getNodeType('Neos.ContentRepository.Testing:Shortcut');
        self::assertFalse($nodeType->isOfType('Neos.ContentRepository.Testing:SomeMixin'));
    }

    /**
     * @test
     */
    public function isOfTypeReturnsFalseForIndirectlyDisabledSuperTypes()
    {
        $nodeType = $this->getNodeType('Neos.ContentRepository.Testing:SubShortcut');
        self::assertFalse($nodeType->isOfType('Neos.ContentRepository.Testing:SomeMixin'));
    }

    /**
     * This test asserts that a supertype that has been inherited can be removed by a supertype again.
     * @test
     */
    public function inheritedSuperSuperTypesCanBeRemoved()
    {
        $nodeType = $this->getNodeType('Neos.ContentRepository.Testing:SubShortcut');
        self::assertSame('Sub-Shortcut', $nodeType->getLabel());

        $expectedProperties = [
            'target' => [
                'type' => 'string'
            ]
        ];
        self::assertSame($expectedProperties, $nodeType->getProperties());
    }

    /**
     * This test asserts that a supertype that has been inherited can be removed by a supertype again.
     * @test
     */
    public function superTypesRemovedByInheritanceCanBeAddedAgain()
    {
        $nodeType = $this->getNodeType('Neos.ContentRepository.Testing:SubSubSubShortcut');
        self::assertSame('Sub-Sub-Sub-Shortcut', $nodeType->getLabel());

        $expectedProperties = [
            'someMixinProperty' => [
                'type' => 'string',
                'label' => 'Important hint'
            ],
            'target' => [
                'type' => 'string',
            ],
        ];
        self::assertSame($expectedProperties, $nodeType->getProperties());
    }

    /**
     * Return a nodetype built from the nodeTypesFixture
     */
    protected function getNodeType(string $nodeTypeName): ?NodeType
    {
        if (!isset($this->nodeTypesFixture[$nodeTypeName])) {
            return null;
        }

        $configuration = $this->nodeTypesFixture[$nodeTypeName];
        $declaredSuperTypes = [];
        if (isset($configuration['superTypes']) && is_array($configuration['superTypes'])) {
            foreach ($configuration['superTypes'] as $superTypeName => $enabled) {
                $declaredSuperTypes[$superTypeName] = $enabled === true ? $this->getNodeType($superTypeName) : null;
            }
        }

        return new NodeType(
            NodeTypeName::fromString($nodeTypeName),
            $declaredSuperTypes,
            $configuration,
            new DefaultNodeLabelGeneratorFactory()
        );
    }
}
