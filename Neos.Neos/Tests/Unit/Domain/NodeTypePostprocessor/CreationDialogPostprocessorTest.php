<?php
namespace Neos\Neos\Tests\Unit\NodeTypePostprocessor;

use Neos\ContentRepository\Core\SharedModel\NodeType\NodeType;
use Neos\Flow\Tests\UnitTestCase;
use Neos\Neos\NodeTypePostprocessor\CreationDialogPostprocessor;
use PHPUnit\Framework\MockObject\MockObject;

class CreationDialogPostprocessorTest extends UnitTestCase
{

    /**
     * @var CreationDialogPostprocessor
     */
    private $creationDialogPostprocessor;

    /**
     * @var NodeType|MockObject
     */
    private $mockNodeType;

    public function setUp(): void
    {
        $this->creationDialogPostprocessor = new CreationDialogPostprocessor();
        $this->mockNodeType = $this->getMockBuilder(NodeType::class)->disableOriginalConstructor()->getMock();
    }

    /**
     * @test
     */
    public function processCopiesInspectorConfigurationToCreationDialogElements(): void
    {
        $configuration = [
            'properties' => [
                'somePropertyName' => [
                    'ui' => [
                        'showInCreationDialog' => true,
                        'inspector' => [
                            'editor' => 'Some\Editor',
                            'editorOptions' => ['some' => 'option'],
                        ],
                    ],
                    'validation' => [
                        'Neos.Neos/Validation/NotEmptyValidator' => [],
                        'Neos.Neos/Validation/StringLengthValidator' => [
                            'minimum' => 1,
                            'maximum' => 255,
                        ]
                    ],
                    'position' => 123,
                ],
            ],
        ];

        $this->creationDialogPostprocessor->process($this->mockNodeType, $configuration, []);

        $expectedElements = [
            'somePropertyName' => [
                'type' => 'string',
                'ui' => [
                    'label' => 'somePropertyName',
                    'editor' => 'Some\Editor',
                    'editorOptions' => ['some' => 'option'],
                ],
                'validation' => [
                    'Neos.Neos/Validation/NotEmptyValidator' => [],
                    'Neos.Neos/Validation/StringLengthValidator' => [
                        'minimum' => 1,
                        'maximum' => 255,
                    ]
                ],
                'position' => 123,
            ],
        ];

        self::assertSame($expectedElements, $configuration['ui']['creationDialog']['elements']);
    }

    /**
     * @test
     */
    public function processDoesNotCreateEmptyCreationDialogs(): void
    {
        $configuration = [
            'properties' => [
                'somePropertyName' => [
                    'ui' => [
                        'inspector' => [
                            'editor' => 'Some\Editor',
                            'editorOptions' => ['some' => 'option'],
                        ],
                    ],
                ],
            ],
        ];
        $originalConfiguration = $configuration;

        $this->creationDialogPostprocessor->process($this->mockNodeType, $configuration, []);

        self::assertSame($originalConfiguration, $configuration);
    }

    /**
     * @test
     */
    public function processRespectsDataTypeDefaultConfiguration(): void
    {
        $configuration = [
            'properties' => [
                'somePropertyName' => [
                    'type' => 'SomeType',
                    'ui' => [
                        'label' => 'Some Label',
                        'showInCreationDialog' => true,
                        'inspector' => [
                            'editorOptions' => ['some' => 'option'],
                        ],
                    ],
                ],
            ],
        ];
        $this->inject($this->creationDialogPostprocessor, 'dataTypesDefaultConfiguration', [
            'SomeType' => [
                'editor' => 'Some\Default\Editor',
                'editorOptions' => [
                    'some' => 'defaultOption',
                    'someDefault' => 'option',
                ]
            ]
        ]);

        $this->creationDialogPostprocessor->process($this->mockNodeType, $configuration, []);

        $expectedElements = [
            'somePropertyName' => [
                'type' => 'SomeType',
                'ui' => [
                    'label' => 'Some Label',
                    'editor' => 'Some\Default\Editor',
                    'editorOptions' => ['some' => 'option', 'someDefault' => 'option'],
                ],
            ],
        ];

        self::assertSame($expectedElements, $configuration['ui']['creationDialog']['elements']);
    }

    /**
     * @test
     */
    public function processRespectsEditorDefaultConfiguration(): void
    {
        $configuration = [
            'properties' => [
                'somePropertyName' => [
                    'type' => 'SomeType',
                    'ui' => [
                        'showInCreationDialog' => true,
                        'inspector' => [
                            'editorOptions' => ['some' => 'option'],
                        ],
                    ],
                ],
            ],
        ];
        $this->inject($this->creationDialogPostprocessor, 'editorDefaultConfiguration', [
            'Some\Editor' => [
                'editorOptions' => [
                    'some' => 'editorDefault',
                    'someDefault' => 'fromEditor',
                    'someEditorDefault' => 'fromEditor',
                ]
            ]
        ]);
        $this->inject($this->creationDialogPostprocessor, 'dataTypesDefaultConfiguration', [
            'SomeType' => [
                'editor' => 'Some\Editor',
                'editorOptions' => [
                    'some' => 'defaultOption',
                    'someDefault' => 'fromDataType',
                ]
            ]
        ]);


        $this->creationDialogPostprocessor->process($this->mockNodeType, $configuration, []);

        $expectedElements = [
            'somePropertyName' => [
                'type' => 'SomeType',
                'ui' => [
                    'label' => 'somePropertyName',
                    'editor' => 'Some\Editor',
                    'editorOptions' => ['some' => 'option', 'someDefault' => 'fromDataType', 'someEditorDefault' => 'fromEditor'],
                ],
            ],
        ];

        self::assertSame($expectedElements, $configuration['ui']['creationDialog']['elements']);
    }
}
