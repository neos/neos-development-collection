<?php

namespace Neos\Neos\Tests\Unit\NodeTypePostprocessor;

/*
 * This file is part of the Neos.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\ContentRepository\Core\NodeType\NodeType;
use Neos\ContentRepository\Core\NodeType\NodeTypeName;
use Neos\Flow\Tests\UnitTestCase;
use Neos\Neos\NodeTypePostprocessor\DefaultPropertyEditorPostprocessor;
use Symfony\Component\Yaml\Yaml;

/**
 * Testcase for the DefaultPropertyEditorPostprocessor
 */
class DefaultPropertyEditorPostprocessorTest extends UnitTestCase
{
    public function referenceExamples(): iterable
    {
        yield 'multiple references' => [
            'nodeTypeDefinition' => <<<'YAML'
            references:
              someReferences:
                ui:
                  inspector:
                    group: 'foo'
            YAML,
            'expected' => <<<'YAML'
            references:
              someReferences:
                ui:
                  inspector:
                    group: 'foo'
                    editor: ReferencesEditor
            YAML
        ];

        yield 'singular reference' => [
            'nodeTypeDefinition' => <<<'YAML'
            references:
              someReference:
                constraints:
                  maxItems: 1
                ui:
                  inspector:
                    group: 'foo'
            YAML,
            'expected' => <<<'YAML'
            references:
              someReference:
                constraints:
                  maxItems: 1
                ui:
                  inspector:
                    editor: SingularReferenceEditor
                    group: 'foo'
            YAML
        ];
    }

    /**
     * @test
     * @dataProvider referenceExamples
     */
    public function processExamples(string $nodeTypeDefinition, string $expectedResult)
    {
        $configuration = array_merge(['references' => [], 'properties' => []], Yaml::parse($nodeTypeDefinition));

        $dataTypesDefaultConfiguration = [
            'reference' => [
                'editor' => 'SingularReferenceEditor',
            ],
            'references' => [
                'editor' => 'ReferencesEditor',
            ],
        ];

        $editorDefaultConfiguration = [];

        $actualResult = $this->processConfiguration($configuration, $dataTypesDefaultConfiguration, $editorDefaultConfiguration);
        self::assertEquals(array_merge(['references' => [], 'properties' => []], Yaml::parse($expectedResult)), $actualResult);
    }

    /**
     * @test
     */
    public function processConvertsPropertyConfiguration(): void
    {
        $configuration = [
            'references' => [],
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
            'references' => [],
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
            'references' => [],
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

    private function processConfiguration(array $configuration, array $dataTypesDefaultConfiguration, array $editorDefaultConfiguration): array
    {
        $postprocessor = new DefaultPropertyEditorPostprocessor();
        $this->inject($postprocessor, 'dataTypesDefaultConfiguration', $dataTypesDefaultConfiguration);
        $this->inject($postprocessor, 'editorDefaultConfiguration', $editorDefaultConfiguration);
        $mockNodeType = new NodeType(
            NodeTypeName::fromString('Some.NodeType:Name'),
            [],
            []
        );
        $postprocessor->process($mockNodeType, $configuration, []);
        return $configuration;
    }
}
