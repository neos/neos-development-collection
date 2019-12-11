<?php
namespace Neos\Neos\Tests\Unit\NodeTypePostprocessor;

use Neos\ContentRepository\Domain\Model\NodeType;
use Neos\Flow\Tests\UnitTestCase;
use Neos\Neos\NodeTypePostprocessor\CreationDialogPostprocessor;
use Neos\Utility\ObjectAccess;
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
                'foo' => [
                    'ui' => [
                        'showInCreationDialog' => true,
                        'inspector' => [
                            'editor' => 'Some\Editor',
                            'editorOptions' => ['some' => 'option'],
                        ],
                    ],
                ],
            ],
        ];

        $this->mockNodeType->method('getConfiguration')->willReturnCallback(static function($propertyPath) use ($configuration) {
            return ObjectAccess::getPropertyPath($configuration, $propertyPath);
        });

        $this->creationDialogPostprocessor->process($this->mockNodeType, $configuration, []);

        $expected = [
            'properties' => [
                'foo' => [
                    'ui' => [
                        'showInCreationDialog' => true,
                        'inspector' => [
                            'editor' => 'Some\Editor',
                            'editorOptions' => ['some' => 'option'],
                        ],
                    ],
                ],
            ],
            'ui' => [
                'creationDialog' => [
                    'elements' => [
                        'foo' => [
                            'ui' => [
                                'showInCreationDialog' => true,
                                'editor' => 'Some\Editor',
                                'editorOptions' => ['some' => 'option'],
                            ],
                        ],
                    ],
                ],
            ]
        ];

        self::assertSame($expected, $configuration);
    }

    /**
     * @test
     */
    public function processDoesNotCreateEmptyCreationDialogs(): void
    {
        $configuration = [
            'properties' => [
                'foo' => [
                    'ui' => [
                        'inspector' => [
                            'editor' => 'Some\Editor',
                            'editorOptions' => ['some' => 'option'],
                        ],
                    ],
                ],
            ],
        ];

        $this->mockNodeType->method('getConfiguration')->willReturnCallback(static function($propertyPath) use ($configuration) {
            return ObjectAccess::getPropertyPath($configuration, $propertyPath);
        });

        $this->creationDialogPostprocessor->process($this->mockNodeType, $configuration, []);

        $expected = [
            'properties' => [
                'foo' => [
                    'ui' => [
                        'inspector' => [
                            'editor' => 'Some\Editor',
                            'editorOptions' => ['some' => 'option'],
                        ],
                    ],
                ],
            ],
        ];

        self::assertSame($expected, $configuration);
    }

}
