<?php
namespace Neos\ContentRepository\Tests\Unit\Domain\Model;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\ObjectManagement\ObjectManagerInterface;
use Neos\Flow\Tests\UnitTestCase;
use Neos\ContentRepository\Domain\Model\NodeType;
use Neos\ContentRepository\Domain\Service\NodeTypeManager;

/**
 * Testcase for the "NodeType" domain model
 *
 */
class NodeTypeTest extends UnitTestCase
{
    /**
     * example node types
     *
     * @var array
     */
    protected $nodeTypesFixture = [
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
        $nodeType = new NodeType('Neos.ContentRepository.Testing:Text', [], []);
        self::assertSame('Neos.ContentRepository.Testing:Text', $nodeType->getName());
    }

    /**
     * @test
     */
    public function setDeclaredSuperTypesExpectsAnArrayOfNodeTypesAsKeys()
    {
        $this->expectException(\InvalidArgumentException::class);
        new NodeType('ContentRepository:Folder', ['foo' => true], []);
    }

    /**
     * @test
     */
    public function setDeclaredSuperTypesAcceptsAnArrayOfNodeTypes()
    {
        $this->expectException(\InvalidArgumentException::class);
        new NodeType('ContentRepository:Folder', ['foo'], []);
    }

    /**
     * @test
     */
    public function nodeTypesCanHaveAnyNumberOfSuperTypes()
    {
        $baseType = new NodeType('Neos.ContentRepository:Base', [], []);

        $timeableNodeType = new NodeType('Neos.ContentRepository.Testing:TimeableContent', [], []);
        $documentType = new NodeType(
            'Neos.ContentRepository.Testing:Document',
            [
                'Neos.ContentRepository:Base' => $baseType,
                'Neos.ContentRepository.Testing:TimeableContent' => $timeableNodeType,
            ],
            []
        );

        $hideableNodeType = new NodeType('Neos.ContentRepository.Testing:HideableContent', [], []);
        $pageType = new NodeType(
            'Neos.ContentRepository.Testing:Page',
            [
                'Neos.ContentRepository.Testing:Document' => $documentType,
                'Neos.ContentRepository.Testing:HideableContent' => $hideableNodeType,
                'Neos.ContentRepository.Testing:TimeableContent' => null,
            ],
            []
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
        $baseType = new NodeType('Neos.ContentRepository:Base', [], []);
        self::assertSame('', $baseType->getLabel());
    }

    /**
     * @test
     */
    public function propertiesAreEmptyArrayByDefault()
    {
        $baseType = new NodeType('Neos.ContentRepository:Base', [], []);
        self::assertSame([], $baseType->getProperties());
    }

    /**
     * @test
     */
    public function hasConfigurationInitializesTheNodeType()
    {
        $nodeType = $this->getMockBuilder(NodeType::class)->disableOriginalConstructor()->setMethods(['initialize'])->getMock();
        $nodeType->expects(self::once())->method('initialize');
        $nodeType->hasConfiguration('foo');
    }

    /**
     * @test
     */
    public function hasConfigurationReturnsTrueIfSpecifiedConfigurationPathExists()
    {
        $nodeType = new NodeType('Neos.ContentRepository:Base', [], [
            'someKey' => [
                'someSubKey' => 'someValue'
            ]
        ]);
        self::assertTrue($nodeType->hasConfiguration('someKey.someSubKey'));
    }

    /**
     * @test
     */
    public function hasConfigurationReturnsFalseIfSpecifiedConfigurationPathDoesNotExist()
    {
        $nodeType = new NodeType('Neos.ContentRepository:Base', [], []);
        self::assertFalse($nodeType->hasConfiguration('some.nonExisting.path'));
    }

    /**
     * @test
     */
    public function getConfigurationInitializesTheNodeType()
    {
        $nodeType = $this->getMockBuilder(NodeType::class)->disableOriginalConstructor()->setMethods(['initialize'])->getMock();
        $nodeType->expects(self::once())->method('initialize');
        $nodeType->getConfiguration('foo');
    }

    /**
     * @test
     */
    public function getConfigurationReturnsTheConfigurationWithTheSpecifiedPath()
    {
        $nodeType = new NodeType('Neos.ContentRepository:Base', [], [
            'someKey' => [
                'someSubKey' => 'someValue'
            ]
        ]);
        self::assertSame('someValue', $nodeType->getConfiguration('someKey.someSubKey'));
    }

    /**
     * @test
     */
    public function getConfigurationReturnsNullIfTheSpecifiedPathDoesNotExist()
    {
        $nodeType = new NodeType('Neos.ContentRepository:Base', [], []);
        self::assertNull($nodeType->getConfiguration('some.nonExisting.path'));
    }

    /**
     * data source for accessingConfigurationOptionsInitializesTheNodeType()
     */
    public function gettersThatRequiresInitialization()
    {
        return [
            ['getFullConfiguration'],
            ['getLabel'],
            ['getNodeLabelGenerator'],
            ['getProperties'],
            ['getDefaultValuesForProperties'],
            ['getAutoCreatedChildNodes'],
        ];
    }

    /**
     * @param string  $getter
     * @test
     * @dataProvider gettersThatRequiresInitialization
     */
    public function accessingConfigurationOptionsInitializesTheNodeType($getter)
    {
        $mockObjectManager = $this->createMock(ObjectManagerInterface::class);
        $nodeType = $this->getAccessibleMock(NodeType::class, ['initialize'], [], '', false);
        $nodeType->_set('objectManager', $mockObjectManager);
        $nodeType->expects(self::atLeastOnce())->method('initialize');
        $nodeType->$getter();
    }

    /**
     * @test
     */
    public function defaultValuesForPropertiesHandlesDateTypes()
    {
        $nodeType = new NodeType('Neos.ContentRepository:Base', [], [
            'properties' => [
                'date' => [
                    'type' => 'DateTime',
                    'defaultValue' => '2014-09-23'
                ]
            ]
        ]);

        self::assertEquals($nodeType->getDefaultValuesForProperties(), ['date' => new \DateTime('2014-09-23')]);
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
     *
     * @param string $nodeTypeName
     * @return null|NodeType
     */
    protected function getNodeType($nodeTypeName)
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

        return new NodeType($nodeTypeName, $declaredSuperTypes, $configuration);
    }

    /**
     * @test
     */
    public function getAutoCreatedChildNodesReturnsLowercasePaths()
    {
        $childNodeConfiguration = ['type' => 'Neos.ContentRepository:Base'];
        $baseType = new NodeType('Neos.ContentRepository:Base', [], [
            'childNodes' => ['nodeName' => $childNodeConfiguration]
        ]);
        $mockNodeTypeManager = $this->getMockBuilder(NodeTypeManager::class)->disableOriginalConstructor()->getMock();
        $mockNodeTypeManager->expects(self::any())->method('getNodeType')->will(self::returnValue($baseType));
        $this->inject($baseType, 'nodeTypeManager', $mockNodeTypeManager);

        $autoCreatedChildNodes = $mockNodeTypeManager->getNodeType('Neos.ContentRepository:Base')->getAutoCreatedChildNodes();

        self::assertArrayHasKey('nodename', $autoCreatedChildNodes);
    }
}
