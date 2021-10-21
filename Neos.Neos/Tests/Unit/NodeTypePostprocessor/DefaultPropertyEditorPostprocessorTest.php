<?php

namespace Neos\Neos\Tests\Unit\Fusion;

/*
 * This file is part of the Neos.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\ContentRepository\Domain\Model\NodeType;
use Neos\Flow\Tests\UnitTestCase;
use Neos\Neos\NodeTypePostprocessor\DefaultPropertyEditorPostprocessor;

/**
 * Testcase for the DefaultPropertyEditorPostprocessor
 */
class DefaultPropertyEditorPostprocessorTest extends UnitTestCase
{

    private function processConfiguration(array $configuration, array $dataTypesDefaultConfiguration, array $editorDefaultConfiguration): array
    {
        $postprocessor = new DefaultPropertyEditorPostprocessor();
        $this->inject($postprocessor, 'dataTypesDefaultConfiguration', $dataTypesDefaultConfiguration);
        $this->inject($postprocessor, 'editorDefaultConfiguration', $editorDefaultConfiguration);
        $mockNodeType = $this->getMockBuilder(NodeType::class)->disableOriginalConstructor()->getMock();
        $mockNodeType->method('getName')->willReturn('Some.NodeType:Name');
        $postprocessor->process($mockNodeType, $configuration, []);
        return $configuration;
    }

    /**
     * @test
     */
    public function processConvertsPropertyConfiguration(): void
    {
        $configuration = [
            'properties' => [
                'propertyWithoutType' => [
                    'ui' => [
                        'label' => 'Some Label'
                    ]
                ],
                'propertyWithUnknownType' => [
                    'type' => 'TypeWithoutDataTypeConfig',
                    'ui' => [
                        'label' => 'Some Label'
                    ]
                ],
                'propertyWithoutInspectorConfig' => [
                    'type' => 'TypeWithDataTypeConfig',
                    'ui' => [
                        'label' => 'Some Label',
                    ]
                ],
                'propertyWithUnknownTypeAndEditorConfig' => [
                    'type' => 'TypeWithoutDataTypeConfig',
                    'ui' => [
                        'label' => 'Some Label',
                        'inspector' => [
                            'editor' => 'EditorWithDefaultConfig',
                            'propertyValue' => 'fromPropertyConfig',
                        ],
                    ]
                ],
                'propertyWithEditorFromDataTypeConfig' => [
                    'type' => 'TypeWithDataTypeConfig',
                    'ui' => [
                        'inspector' => [
                            'value' => 'fromPropertyConfig',
                            'propertyValue' => 'fromPropertyConfig',
                        ],
                    ]
                ],
                'propertyWithOverriddenEditorConfig' => [
                    'type' => 'TypeWithDataTypeConfig',
                    'ui' => [
                        'inspector' => [
                            'editor' => 'EditorFromPropertyConfig',
                            'value' => 'fromPropertyConfig',
                            'propertyValue' => 'fromPropertyConfig',
                        ],
                    ]
                ],
                'propertyWithOverriddenEditorConfigAndEditorDefaultConfig' => [
                    'type' => 'TypeWithDataTypeConfig',
                    'ui' => [
                        'inspector' => [
                            'editor' => 'EditorWithDefaultConfig',
                            'value' => 'fromPropertyConfig',
                            'propertyValue' => 'fromPropertyConfig',
                        ],
                    ]
                ],
                'propertyWithEditorDefaultConfig' => [
                    'type' => 'TypeWithDefaultEditorConfig',
                    'ui' => [
                        'inspector' => [
                            'value' => 'fromPropertyConfig',
                            'propertyValue' => 'fromPropertyConfig',
                        ],
                    ]
                ],
                'propertyWithOverriddenEditorConfigAndEditorDefaultConfig2' => [
                    'type' => 'TypeWithDefaultEditorConfig',
                    'ui' => [
                        'inspector' => [
                            'editor' => 'EditorWithoutDefaultConfig',
                            'propertyValue' => 'fromPropertyConfig',
                        ],
                    ]
                ],
                'propertyWithOverriddenEditorConfigAndEditorDefaultConfig3' => [
                    'type' => 'TypeWithDefaultEditorConfig2',
                    'ui' => [
                        'inspector' => [
                            'editor' => 'EditorWithDefaultConfig',
                            'propertyValue' => 'fromPropertyConfig',
                        ],
                    ]
                ],
            ],
        ];
        $dataTypesDefaultConfiguration = [
            'TypeWithDataTypeConfig' => [
                'editor' => 'EditorFromDataTypeConfig',
                'value' => 'fromDataTypeConfig',
                'dataTypeValue' => 'fromDataTypeConfig',
            ],
            'TypeWithDefaultEditorConfig' => [
                'editor' => 'EditorWithDefaultConfig',
                'value' => 'fromDataTypeConfig',
                'dataTypeValue' => 'fromDataTypeConfig',
            ],
            'TypeWithDefaultEditorConfig2' => [
                'editor' => 'EditorWithDefaultConfig',
                'dataTypeValue' => 'fromDataTypeConfig',
            ],
        ];
        $editorDefaultConfiguration = [
            'EditorWithDefaultConfig' => [
                'value' => 'fromEditorDefaultConfig',
                'editorDefaultValue' => 'fromEditorDefaultConfig',
            ],
        ];

        $expectedResult = [
            'properties' => [
                'propertyWithoutType' => [
                    'ui' => [
                        'label' => 'Some Label'
                    ]
                ],
                'propertyWithUnknownType' => [
                    'type' => 'TypeWithoutDataTypeConfig',
                    'ui' => [
                        'label' => 'Some Label'
                    ]
                ],
                'propertyWithoutInspectorConfig' => [
                    'type' => 'TypeWithDataTypeConfig',
                    'ui' => [
                        'label' => 'Some Label',
                    ]
                ],
                'propertyWithUnknownTypeAndEditorConfig' => [
                    'type' => 'TypeWithoutDataTypeConfig',
                    'ui' => [
                        'label' => 'Some Label',
                        'inspector' => [
                            'value' => 'fromEditorDefaultConfig',
                            'editorDefaultValue' => 'fromEditorDefaultConfig',
                            'editor' => 'EditorWithDefaultConfig',
                            'propertyValue' => 'fromPropertyConfig',
                        ],
                    ]
                ],
                'propertyWithEditorFromDataTypeConfig' => [
                    'type' => 'TypeWithDataTypeConfig',
                    'ui' => [
                        'inspector' => [
                            'editor' => 'EditorFromDataTypeConfig',
                            'value' => 'fromPropertyConfig',
                            'dataTypeValue' => 'fromDataTypeConfig',
                            'propertyValue' => 'fromPropertyConfig',
                        ],
                    ]
                ],
                'propertyWithOverriddenEditorConfig' => [
                    'type' => 'TypeWithDataTypeConfig',
                    'ui' => [
                        'inspector' => [
                            'editor' => 'EditorFromPropertyConfig',
                            'value' => 'fromPropertyConfig',
                            'dataTypeValue' => 'fromDataTypeConfig',
                            'propertyValue' => 'fromPropertyConfig',
                        ],
                    ]
                ],
                'propertyWithOverriddenEditorConfigAndEditorDefaultConfig' => [
                    'type' => 'TypeWithDataTypeConfig',
                    'ui' => [
                        'inspector' => [
                            'value' => 'fromPropertyConfig',
                            'editorDefaultValue' => 'fromEditorDefaultConfig',
                            'editor' => 'EditorWithDefaultConfig',
                            'dataTypeValue' => 'fromDataTypeConfig',
                            'propertyValue' => 'fromPropertyConfig',
                        ],
                    ]
                ],
                'propertyWithEditorDefaultConfig' => [
                    'type' => 'TypeWithDefaultEditorConfig',
                    'ui' => [
                        'inspector' => [
                            'value' => 'fromPropertyConfig',
                            'editorDefaultValue' => 'fromEditorDefaultConfig',
                            'editor' => 'EditorWithDefaultConfig',
                            'dataTypeValue' => 'fromDataTypeConfig',
                            'propertyValue' => 'fromPropertyConfig',
                        ],
                    ]
                ],
                'propertyWithOverriddenEditorConfigAndEditorDefaultConfig2' => [
                    'type' => 'TypeWithDefaultEditorConfig',
                    'ui' => [
                        'inspector' => [
                            'editor' => 'EditorWithoutDefaultConfig',
                            'value' => 'fromDataTypeConfig',
                            'dataTypeValue' => 'fromDataTypeConfig',
                            'propertyValue' => 'fromPropertyConfig',
                        ],
                    ]
                ],
                'propertyWithOverriddenEditorConfigAndEditorDefaultConfig3' => [
                    'type' => 'TypeWithDefaultEditorConfig2',
                    'ui' => [
                        'inspector' => [
                            'value' => 'fromEditorDefaultConfig',
                            'editorDefaultValue' => 'fromEditorDefaultConfig',
                            'editor' => 'EditorWithDefaultConfig',
                            'dataTypeValue' => 'fromDataTypeConfig',
                            'propertyValue' => 'fromPropertyConfig',
                        ],
                    ]
                ],
            ],
        ];

        $actualResult = $this->processConfiguration($configuration, $dataTypesDefaultConfiguration, $editorDefaultConfiguration);
        self::assertSame($expectedResult, $actualResult);
    }

    /**
     * @test
     */
    public function processThrowsExceptionIfNoPropertyEditorCanBeResolved(): void
    {
        $this->expectException(\Neos\Neos\Exception::class);

        $configuration = [
            'properties' => [
                'someProperty' => [
                    'type' => 'string',
                    'ui' => ['inspector' => []]
                ],
            ]
        ];
        $dataTypesDefaultConfiguration = [
            'string' => [],
        ];
        $this->processConfiguration($configuration, $dataTypesDefaultConfiguration, []);
    }

    /**
     * @test
     */
    public function processConvertsCreationDialogConfiguration(): void
    {
        $configuration = [
            'ui' => [
                'creationDialog' => [
                    'elements' => [
                        'elementWithoutType' => [
                            'ui' => [
                                'label' => 'Some Label'
                            ]
                        ],
                        'elementWithUnknownType' => [
                            'type' => 'TypeWithoutDataTypeConfig',
                            'ui' => [
                                'label' => 'Some Label',
                                'editor' => 'EditorFromPropertyConfig',
                            ]
                        ],
                        'elementWithEditorFromDataTypeConfig' => [
                            'type' => 'TypeWithDataTypeConfig',
                            'ui' => [
                                'value' => 'fromPropertyConfig',
                                'elementValue' => 'fromPropertyConfig',
                            ]
                        ],
                        'elementWithOverriddenEditorConfig' => [
                            'type' => 'TypeWithDataTypeConfig',
                            'ui' => [
                                'editor' => 'EditorFromPropertyConfig',
                                'value' => 'fromPropertyConfig',
                                'elementValue' => 'fromPropertyConfig',
                            ]
                        ],
                        'elementWithOverriddenEditorConfigAndEditorDefaultConfig' => [
                            'type' => 'TypeWithDataTypeConfig',
                            'ui' => [
                                'editor' => 'EditorWithDefaultConfig',
                                'value' => 'fromPropertyConfig',
                                'elementValue' => 'fromPropertyConfig',
                            ]
                        ],
                        'elementWithEditorDefaultConfig' => [
                            'type' => 'TypeWithDefaultEditorConfig',
                            'ui' => [
                                'value' => 'fromPropertyConfig',
                                'elementValue' => 'fromPropertyConfig',
                            ]
                        ],
                        'elementWithOverriddenEditorConfigAndEditorDefaultConfig2' => [
                            'type' => 'TypeWithDefaultEditorConfig',
                            'ui' => [
                                'editor' => 'EditorWithoutDefaultConfig',
                                'elementValue' => 'fromPropertyConfig',
                            ]
                        ],
                        'elementWithOverriddenEditorConfigAndEditorDefaultConfig3' => [
                            'type' => 'TypeWithDefaultEditorConfig2',
                            'ui' => [
                                'editor' => 'EditorWithDefaultConfig',
                                'elementValue' => 'fromPropertyConfig',
                            ]
                        ],
                    ],
                ],
            ],
        ];
        $dataTypesDefaultConfiguration = [
            'TypeWithDataTypeConfig' => [
                'editor' => 'EditorFromDataTypeConfig',
                'value' => 'fromDataTypeConfig',
                'dataTypeValue' => 'fromDataTypeConfig',
            ],
            'TypeWithDefaultEditorConfig' => [
                'editor' => 'EditorWithDefaultConfig',
                'value' => 'fromDataTypeConfig',
                'dataTypeValue' => 'fromDataTypeConfig',
            ],
            'TypeWithDefaultEditorConfig2' => [
                'editor' => 'EditorWithDefaultConfig',
                'dataTypeValue' => 'fromDataTypeConfig',
            ],
        ];
        $editorDefaultConfiguration = [
            'EditorWithDefaultConfig' => [
                'value' => 'fromEditorDefaultConfig',
                'editorDefaultValue' => 'fromEditorDefaultConfig',
            ],
        ];

        $expectedResult = [
            'ui' => [
                'creationDialog' => [
                    'elements' => [
                        'elementWithoutType' => [
                            'ui' => [
                                'label' => 'Some Label'
                            ]
                        ],
                        'elementWithUnknownType' => [
                            'type' => 'TypeWithoutDataTypeConfig',
                            'ui' => [
                                'label' => 'Some Label',
                                'editor' => 'EditorFromPropertyConfig',
                            ]
                        ],
                        'elementWithEditorFromDataTypeConfig' => [
                            'type' => 'TypeWithDataTypeConfig',
                            'ui' => [
                                'editor' => 'EditorFromDataTypeConfig',
                                'value' => 'fromPropertyConfig',
                                'dataTypeValue' => 'fromDataTypeConfig',
                                'elementValue' => 'fromPropertyConfig',
                            ]
                        ],
                        'elementWithOverriddenEditorConfig' => [
                            'type' => 'TypeWithDataTypeConfig',
                            'ui' => [
                                'editor' => 'EditorFromPropertyConfig',
                                'value' => 'fromPropertyConfig',
                                'dataTypeValue' => 'fromDataTypeConfig',
                                'elementValue' => 'fromPropertyConfig',
                            ]
                        ],
                        'elementWithOverriddenEditorConfigAndEditorDefaultConfig' => [
                            'type' => 'TypeWithDataTypeConfig',
                            'ui' => [
                                'value' => 'fromPropertyConfig',
                                'editorDefaultValue' => 'fromEditorDefaultConfig',
                                'editor' => 'EditorWithDefaultConfig',
                                'dataTypeValue' => 'fromDataTypeConfig',
                                'elementValue' => 'fromPropertyConfig',
                            ]
                        ],
                        'elementWithEditorDefaultConfig' => [
                            'type' => 'TypeWithDefaultEditorConfig',
                            'ui' => [
                                'value' => 'fromPropertyConfig',
                                'editorDefaultValue' => 'fromEditorDefaultConfig',
                                'editor' => 'EditorWithDefaultConfig',
                                'dataTypeValue' => 'fromDataTypeConfig',
                                'elementValue' => 'fromPropertyConfig',
                            ]
                        ],
                        'elementWithOverriddenEditorConfigAndEditorDefaultConfig2' => [
                            'type' => 'TypeWithDefaultEditorConfig',
                            'ui' => [
                                'editor' => 'EditorWithoutDefaultConfig',
                                'value' => 'fromDataTypeConfig',
                                'dataTypeValue' => 'fromDataTypeConfig',
                                'elementValue' => 'fromPropertyConfig',
                            ]
                        ],
                        'elementWithOverriddenEditorConfigAndEditorDefaultConfig3' => [
                            'type' => 'TypeWithDefaultEditorConfig2',
                            'ui' => [
                                'value' => 'fromEditorDefaultConfig',
                                'editorDefaultValue' => 'fromEditorDefaultConfig',
                                'editor' => 'EditorWithDefaultConfig',
                                'dataTypeValue' => 'fromDataTypeConfig',
                                'elementValue' => 'fromPropertyConfig',
                            ]
                        ],
                    ],
                ],
            ],
        ];

        $actualResult = $this->processConfiguration($configuration, $dataTypesDefaultConfiguration, $editorDefaultConfiguration);
        self::assertSame($expectedResult, $actualResult);
    }

    /**
     * @test
     */
    public function processDoesNotThrowExceptionIfNoCreationDialogEditorCanBeResolved(): void
    {
        $configuration = [
            'ui' => [
                'creationDialog' => [
                    'elements' => [
                        'someElement' => [
                            'type' => 'string',
                            'ui' => ['label' => 'Foo']
                        ],
                    ],
                ],
            ],
        ];
        $actualResult = $this->processConfiguration($configuration, [], []);
        self::assertSame($configuration, $actualResult);
    }

}
