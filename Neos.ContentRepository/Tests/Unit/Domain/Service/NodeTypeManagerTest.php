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
use PHPUnit\Framework\Attributes\Test;
use Neos\ContentRepository\Exception\NodeTypeIsFinalException;
use PHPUnit\Framework\MockObject\MockObject;
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
     * @var ConfigurationManager|MockObject
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

        $this->mockConfigurationManager->expects(self::any())->method('getConfiguration')->with('NodeTypes')->willReturn($nodeTypesFixtureData);
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
    public function getNodeTypeThrowsExceptionForUnknownNodeType()
    {
        $this->expectException(NodeTypeNotFoundException::class);
        $this->nodeTypeManager->getNodeType('Neos.ContentRepository.Testing:TextFooBarNotHere');
    }

    #[Test]
    public function getNodeTypeThrowsExceptionIfNoFallbackNodeTypeIsConfigured()
    {
        $this->expectException(NodeTypeNotFoundException::class);
        $this->nodeTypeManager->getNodeType('Neos.ContentRepository.Testing:TextFooBarNotHere');
    }

    #[Test]
    public function getNodeTypeThrowsExceptionIfConfiguredFallbackNodeTypeCantBeFound()
    {
        $this->expectException(NodeTypeNotFoundException::class);
        $this->inject($this->nodeTypeManager, 'fallbackNodeTypeName', 'Neos.ContentRepository:NonExistingFallbackNode');
        $this->nodeTypeManager->getNodeType('Neos.ContentRepository.Testing:TextFooBarNotHere');
    }

    #[Test]
    public function getNodeTypeReturnsFallbackNodeTypeIfConfigured()
    {
        $this->inject($this->nodeTypeManager, 'fallbackNodeTypeName', 'Neos.ContentRepository:FallbackNode');

        $expectedNodeType = $this->nodeTypeManager->getNodeType('Neos.ContentRepository:FallbackNode');
        $fallbackNodeType = $this->nodeTypeManager->getNodeType('Neos.ContentRepository.Testing:TextFooBarNotHere');
        self::assertSame($expectedNodeType, $fallbackNodeType);
    }

    #[Test]
    public function createNodeTypeAlwaysThrowsAnException()
    {
        $this->expectException(Exception::class);
        $this->nodeTypeManager->createNodeType('Neos.ContentRepository.Testing:ContentObject');
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
            'Neos.ContentRepository:FallbackNode'
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
    public function aggregateNodeTypeFlagIsFalseByDefault()
    {
        self::assertFalse($this->nodeTypeManager->getNodeType('Neos.ContentRepository.Testing:Text')->isAggregate());
    }

    #[Test]
    public function aggregateNodeTypeFlagIsInherited()
    {
        self::assertTrue($this->nodeTypeManager->getNodeType('Neos.ContentRepository.Testing:Document')->isAggregate());
        self::assertTrue($this->nodeTypeManager->getNodeType('Neos.ContentRepository.Testing:Page')->isAggregate());
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
    public function getSubNodeTypesWithDifferentIncludeFlagValuesReturnsCorrectValues()
    {
        $subNodeTypes = $this->nodeTypeManager->getSubNodeTypes('Neos.ContentRepository.Testing:ContentObject', true);
        self::assertArrayHasKey('Neos.ContentRepository.Testing:AbstractType', $subNodeTypes);

        $subNodeTypes = $this->nodeTypeManager->getSubNodeTypes('Neos.ContentRepository.Testing:ContentObject', false);
        self::assertArrayNotHasKey('Neos.ContentRepository.Testing:AbstractType', $subNodeTypes);
    }
}
