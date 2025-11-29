<?php

declare(strict_types=1);

namespace Neos\Fusion\Tests\Unit\Migrations;

use Neos\Fusion\Migrations\FusionMigrationTrait;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\visitor\vfsStreamStructureVisitor;
use PHPUnit\Framework\TestCase;

class FusionMigrationTest extends TestCase
{
    use FusionMigrationTrait;

    /**
     * @var array
     */
    protected $targetPackageData;

    public function setUp(): void
    {
        $this->targetPackageData = [];
        $this->eelReplacementOperations = [];
    }

    public function getIdentifier(): string
    {
        return 'Neos.Fusion-XXX';
    }

    /** @test */
    public function doesNoReplacementsIfPackageDoesNotContainFusionFiles(): void
    {
        $stream = vfsStream::setup('fusion', null, $expectedStructure = [
            "Target.Package" => [
                'Configuration' => [
                    'Settings.yaml' => <<<'YAML'
                    Target.Package:
                      someEelExpression: ${someVariable}
                    YAML,
                ],
                'Resources' => [
                    'Private' => [
                        'SomeTemplate.html' => 'I contain also ${someVariable} but am not fusion'
                    ]
                ],
                'Readme.md' => 'My superb package!'
            ]
        ]);

        $this->targetPackageData = [
            'path' => 'vfs://fusion/Target.Package'
        ];

        $this->replaceEelExpression('/someVariable/', 'newVariable');

        $this->applyEelFusionOperations();

        $structureVisitor = new vfsStreamStructureVisitor();
        $structureVisitor->visit($stream->getChild('Target.Package'));
        $newStructure = $structureVisitor->getStructure();

        self::assertEquals(
            $expectedStructure,
            $newStructure
        );
    }

    /** @test */
    public function doesNoReplacementsIfNoFusionFilesNeedAdjustments(): void
    {
        $stream = vfsStream::setup('fusion', null, $expectedStructure = [
            "Target.Package" => [
                'Resources' => [
                    'Private' => [
                        'Fusion' => [
                            'SomeComponent.fusion' => <<<'Fusion'
                            prototype(Neos.Fusion.Test:Value) < prototype(Neos.Fusion:Value) {
                                value = ${anotherVariable}
                            }
                            Fusion
                        ]
                    ]
                ],
            ]
        ]);

        $this->targetPackageData = [
            'path' => 'vfs://fusion/Target.Package'
        ];

        $this->replaceEelExpression('/someVariable/', 'newVariable');

        $this->applyEelFusionOperations();

        $structureVisitor = new vfsStreamStructureVisitor();
        $structureVisitor->visit($stream->getChild('Target.Package'));
        $newStructure = $structureVisitor->getStructure();

        self::assertEquals(
            $expectedStructure,
            $newStructure
        );
    }

    /** @test */
    public function doesReplacementFusionFileOfPackage(): void
    {
        $stream = vfsStream::setup('fusion', null, [
            "Target.Package" => [
                'Resources' => [
                    'Private' => [
                        'Fusion' => [
                            'SomeComponent.fusion' => <<<'Fusion'
                            prototype(Neos.Fusion.Test:Value) < prototype(Neos.Fusion:Value) {
                                value = ${someVariable}
                            }
                            Fusion
                        ]
                    ]
                ],
            ]
        ]);

        $this->targetPackageData = [
            'path' => 'vfs://fusion/Target.Package'
        ];

        $this->replaceEelExpression('/someVariable/', 'newVariable');

        $this->applyEelFusionOperations();

        $structureVisitor = new vfsStreamStructureVisitor();
        $structureVisitor->visit($stream->getChild('Target.Package'));
        $newStructure = $structureVisitor->getStructure();

        $expectedStructure = [
            "Target.Package" => [
                'Resources' => [
                    'Private' => [
                        'Fusion' => [
                            'SomeComponent.fusion' => <<<'Fusion'
                            prototype(Neos.Fusion.Test:Value) < prototype(Neos.Fusion:Value) {
                                value = ${newVariable}
                            }
                            Fusion
                        ]
                    ]
                ],
            ]
        ];

        self::assertEquals(
            $expectedStructure,
            $newStructure
        );
    }

    /** @test */
    public function doesMultipleReplacementInAllFusionFilesOfPackage(): void
    {
        $stream = vfsStream::setup('fusion', null, [
            "Target.Package" => [
                'Resources' => [
                    'Private' => [
                        'Fusion' => [
                            'SomeComponent.fusion' => <<<'Fusion'
                            prototype(Neos.Fusion.Test:Value) < prototype(Neos.Fusion:Value) {
                                value = ${someVariable}
                            }
                            Fusion,
                            'Nested' => [
                                'OtherComponent.fusion' => <<<'Fusion'
                                foo = Neos.Fusion:Value {
                                    value = ${someVariable + My.OldHelper('abc')}
                                }
                                Fusion
                            ]
                        ]
                    ]
                ],
            ]
        ]);

        $this->targetPackageData = [
            'path' => 'vfs://fusion/Target.Package'
        ];

        $this->replaceEelExpression('/someVariable/', 'newVariable');
        $this->replaceEelExpression('/My\.OldHelper\(([^)]*)\)/', 'My.NewHelper(123, $1)');

        $this->applyEelFusionOperations();

        $structureVisitor = new vfsStreamStructureVisitor();
        $structureVisitor->visit($stream->getChild('Target.Package'));
        $newStructure = $structureVisitor->getStructure();

        $expectedStructure = [
            "Target.Package" => [
                'Resources' => [
                    'Private' => [
                        'Fusion' => [
                            'SomeComponent.fusion' => <<<'Fusion'
                            prototype(Neos.Fusion.Test:Value) < prototype(Neos.Fusion:Value) {
                                value = ${newVariable}
                            }
                            Fusion,
                            'Nested' => [
                                'OtherComponent.fusion' => <<<'Fusion'
                                foo = Neos.Fusion:Value {
                                    value = ${newVariable + My.NewHelper(123, 'abc')}
                                }
                                Fusion
                            ]
                        ]
                    ]
                ],
            ]
        ];

        self::assertEquals(
            $expectedStructure,
            $newStructure
        );
    }

    /** @test */
    public function doesFusionPrototypeNameReplacementInAllFusionFilesOfPackage(): void
    {
        $stream = vfsStream::setup('fusion', null, [
            "Target.Package" => [
                'Resources' => [
                    'Private' => [
                        'Fusion' => [
                            'SomeComponent.fusion' => <<<'Fusion'
                            prototype(Neos.Fusion.Test:Custom)  < prototype(Neos.Fusion:Array) {
                              key = ${someVariable}
                            }
                            Fusion,
                            'Nested' => [
                                'OtherComponent.fusion' => <<<'Fusion'
                                foo = Neos.Fusion:Array {
                                    key = 'value'
                                }
                                Fusion
                            ]
                        ]
                    ]
                ],
            ]
        ]);

        $this->targetPackageData = [
            'path' => 'vfs://fusion/Target.Package'
        ];

        $this->replaceEelExpression('/someVariable/', 'newVariable');
        $this->renameFusionPrototype('Neos.Fusion:Array', 'Neos.Fusion:Join');

        $this->applyEelFusionOperations();

        $structureVisitor = new vfsStreamStructureVisitor();
        $structureVisitor->visit($stream->getChild('Target.Package'));
        $newStructure = $structureVisitor->getStructure();

        $expectedStructure = [
            "Target.Package" => [
                'Resources' => [
                    'Private' => [
                        'Fusion' => [
                            'SomeComponent.fusion' => <<<'Fusion'
                            prototype(Neos.Fusion.Test:Custom)  < prototype(Neos.Fusion:Join) {
                              key = ${newVariable}
                            }
                            Fusion,
                            'Nested' => [
                                'OtherComponent.fusion' => <<<'Fusion'
                                foo = Neos.Fusion:Join {
                                    key = 'value'
                                }
                                Fusion
                            ]
                        ]
                    ]
                ],
            ]
        ];

        self::assertEquals(
            $expectedStructure,
            $newStructure
        );
    }
}
