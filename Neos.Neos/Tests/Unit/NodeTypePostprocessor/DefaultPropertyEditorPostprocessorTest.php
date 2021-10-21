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
//                            'value' => 'fromEditorDefaultConfig',
//                            'editorDefaultValue' => 'fromEditorDefaultConfig',
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

}
