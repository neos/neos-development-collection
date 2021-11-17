<?php
declare(strict_types=1);
namespace Neos\ContentRepository\Tests\Unit\Configuration;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\ContentRepository\Configuration\NodeTypesLoader;
use Neos\Flow\Configuration\Source\YamlSource;
use Neos\Flow\Core\ApplicationContext;
use Neos\Flow\Package\FlowPackageInterface;
use Neos\Flow\Tests\UnitTestCase;
use Neos\Utility\Files;
use PHPUnit\Framework\MockObject\MockObject;

class NodeTypesLoaderTest extends UnitTestCase
{
    /**
     * @var NodeTypesLoader|MockObject
     */
    private $nodeTypesLoader;

    /**
     * @var ApplicationContext|MockObject
     */
    private $mockApplicationContext;

    /**
     * @var FlowPackageInterface|MockObject
     */
    private $mockPackage1;

    /**
     * @var FlowPackageInterface|MockObject
     */
    private $mockPackage2;

    public function setUp(): void
    {
        $this->nodeTypesLoader = new NodeTypesLoader(new YamlSource(), Files::concatenatePaths([__DIR__, 'Fixture', 'Configuration']) . '/');

        $this->mockApplicationContext = $this->getMockBuilder(ApplicationContext::class)->disableOriginalConstructor()->getMock();
        $this->mockApplicationContext->method('getHierarchy')->willReturn(['Testing', 'Testing/SomeSubContext']);

        $this->mockPackage1 = $this->mockPackage('Package1');
        $this->mockPackage2 = $this->mockPackage('Package2');
    }

    private function mockPackage(string $packageKey): FlowPackageInterface
    {
        $mockPackage = $this->getMockBuilder(FlowPackageInterface::class)->getMock();
        $packagePath = Files::concatenatePaths([__DIR__, 'Fixture', 'Packages', $packageKey]) . '/';
        $mockPackage->method('getPackagePath')->willReturn($packagePath);
        $mockPackage->method('getConfigurationPath')->willReturn(Files::concatenatePaths([$packagePath, 'Configuration']) . '/');
        return $mockPackage;
    }

    /**
     * @test
     */
    public function emptyPackageList(): void
    {
        $actualResult = $this->nodeTypesLoader->load([], $this->mockApplicationContext);
        $expectedResult = [
            'Some.Node:Type' => [
                'options' => [
                    'finalPath' => 'Configuration/Testing/SomeSubContext/NodeTypes.yaml',
                    'pathConfiguration.NodeTypes_yaml' => true,
                    'pathConfiguration.NodeTypes_SomeSuffix_yaml' => true,
                    'pathConfiguration.Testing.NodeTypes_yaml' => true,
                    'pathConfiguration.Testing.NodeTypes_SomeSuffix_yaml' => true,
                    'pathConfiguration.Testing.SomeSubContext.NodeTypes_yaml' => true,
                ],
            ],
            'NodeType:PathConfiguration.NodeTypes_yaml' => [
                'options' => [
                    'path' => 'Configuration/NodeTypes.yaml',
                ],
            ],
            'NodeType:PathConfiguration.NodeTypes_SomeSuffix_yaml' => [
                'options' => [
                    'path' => 'Configuration/NodeTypes.SomeSuffix.yaml',
                ],
            ],
            'NodeType:PathConfiguration.Testing.NodeTypes_yaml' => [
                'options' => [
                    'path' => 'Configuration/Testing/NodeTypes.yaml',
                ],
            ],
            'NodeType:PathConfiguration.Testing.NodeTypes_SomeSuffix_yaml' => [
                'options' => [
                    'path' => 'Configuration/Testing/NodeTypes.SomeSuffix.yaml',
                ],
            ],
            'NodeType:PathConfiguration.Testing.SomeSubContext.NodeTypes_yaml' => [
                'options' => [
                    'path' => 'Configuration/Testing/SomeSubContext/NodeTypes.yaml',
                ],
            ],
        ];
        self::assertSame($expectedResult, $actualResult);
    }

    /**
     * @test
     */
    public function firstPackage(): void
    {
        $actualResult = $this->nodeTypesLoader->load([$this->mockPackage1], $this->mockApplicationContext);
        $expectedResult = [
            'Some.Node:Type' => [
                'options' => [
                    'finalPath' => 'Configuration/Testing/SomeSubContext/NodeTypes.yaml',
                    'pathPackages.Package1.NodeTypes.Bar_yaml' => true,
                    'pathPackages.Package1.NodeTypes.Foo_yaml' => true,
                    'pathPackages.Package1.Configuration.NodeTypes_yaml' => true,
                    'pathPackages.Package1.Configuration.NodeTypes_SomeSuffix_yaml' => true,
                    'pathConfiguration.NodeTypes_yaml' => true,
                    'pathConfiguration.NodeTypes_SomeSuffix_yaml' => true,
                    'pathConfiguration.Testing.NodeTypes_yaml' => true,
                    'pathConfiguration.Testing.NodeTypes_SomeSuffix_yaml' => true,
                    'pathConfiguration.Testing.SomeSubContext.NodeTypes_yaml' => true,
                ],
            ],
            'NodeType:PathPackages.Package1.NodeTypes.Bar_yaml' => [
                'options' => [
                    'path' => 'Packages/Package1/NodeTypes/Bar.yaml',
                ],
            ],
            'NodeType:PathPackages.Package1.NodeTypes.Foo_yaml' => [
                'options' => [
                    'path' => 'Packages/Package1/NodeTypes/Foo.yaml',
                ],
            ],
            'NodeType:PathPackages.Package1.Configuration.NodeTypes_yaml' => [
                'options' => [
                    'path' => 'Packages/Package1/Configuration/NodeTypes.yaml',
                ],
            ],
            'NodeType:PathPackages.Package1.Configuration.NodeTypes_SomeSuffix_yaml' => [
                'options' => [
                    'path' => 'Packages/Package1/Configuration/NodeTypes.SomeSuffix.yaml',
                ],
            ],
            'NodeType:PathConfiguration.NodeTypes_yaml' => [
                'options' => [
                    'path' => 'Configuration/NodeTypes.yaml',
                ],
            ],
            'NodeType:PathConfiguration.NodeTypes_SomeSuffix_yaml' => [
                'options' => [
                    'path' => 'Configuration/NodeTypes.SomeSuffix.yaml',
                ],
            ],
            'NodeType:PathConfiguration.Testing.NodeTypes_yaml' => [
                'options' => [
                    'path' => 'Configuration/Testing/NodeTypes.yaml',
                ],
            ],
            'NodeType:PathConfiguration.Testing.NodeTypes_SomeSuffix_yaml' => [
                'options' => [
                    'path' => 'Configuration/Testing/NodeTypes.SomeSuffix.yaml',
                ],
            ],
            'NodeType:PathConfiguration.Testing.SomeSubContext.NodeTypes_yaml' => [
                'options' => [
                    'path' => 'Configuration/Testing/SomeSubContext/NodeTypes.yaml',
                ],
            ],
        ];
        self::assertSame($expectedResult, $actualResult);
    }

    /**
     * @test
     */
    public function secondPackage(): void
    {
        $actualResult = $this->nodeTypesLoader->load([$this->mockPackage2], $this->mockApplicationContext);
        $expectedResult = [
            'Some.Node:Type' => [
                'options' => [
                    'finalPath' => 'Configuration/Testing/SomeSubContext/NodeTypes.yaml',
                    'pathPackages.Package2.NodeTypes.Foo_yaml' => true,
                    'pathPackages.Package2.Configuration.NodeTypes_yaml' => true,
                    'pathConfiguration.NodeTypes_yaml' => true,
                    'pathConfiguration.NodeTypes_SomeSuffix_yaml' => true,
                    'pathConfiguration.Testing.NodeTypes_yaml' => true,
                    'pathConfiguration.Testing.NodeTypes_SomeSuffix_yaml' => true,
                    'pathConfiguration.Testing.SomeSubContext.NodeTypes_yaml' => true,
                ],
            ],
            'NodeType:PathPackages.Package2.NodeTypes.Foo_yaml' => [
                'options' => [
                    'path' => 'Packages/Package2/NodeTypes/Foo.yaml',
                ],
            ],
            'NodeType:PathPackages.Package2.Configuration.NodeTypes_yaml' => [
                'options' => [
                    'path' => 'Packages/Package2/Configuration/NodeTypes.yaml',
                ],
            ],
            'NodeType:PathConfiguration.NodeTypes_yaml' => [
                'options' => [
                    'path' => 'Configuration/NodeTypes.yaml',
                ],
            ],
            'NodeType:PathConfiguration.NodeTypes_SomeSuffix_yaml' => [
                'options' => [
                    'path' => 'Configuration/NodeTypes.SomeSuffix.yaml',
                ],
            ],
            'NodeType:PathConfiguration.Testing.NodeTypes_yaml' => [
                'options' => [
                    'path' => 'Configuration/Testing/NodeTypes.yaml',
                ],
            ],
            'NodeType:PathConfiguration.Testing.NodeTypes_SomeSuffix_yaml' => [
                'options' => [
                    'path' => 'Configuration/Testing/NodeTypes.SomeSuffix.yaml',
                ],
            ],
            'NodeType:PathConfiguration.Testing.SomeSubContext.NodeTypes_yaml' => [
                'options' => [
                    'path' => 'Configuration/Testing/SomeSubContext/NodeTypes.yaml',
                ],
            ],
        ];
        self::assertSame($expectedResult, $actualResult);
    }

    /**
     * @test
     */
    public function bothPackages(): void
    {
        $actualResult = $this->nodeTypesLoader->load([$this->mockPackage1, $this->mockPackage2], $this->mockApplicationContext);
        $expectedResult = [
            'Some.Node:Type' => [
                'options' => [
                    'finalPath' => 'Configuration/Testing/SomeSubContext/NodeTypes.yaml',
                    'pathPackages.Package1.NodeTypes.Bar_yaml' => true,
                    'pathPackages.Package1.NodeTypes.Foo_yaml' => true,
                    'pathPackages.Package1.Configuration.NodeTypes_yaml' => true,
                    'pathPackages.Package1.Configuration.NodeTypes_SomeSuffix_yaml' => true,
                    'pathPackages.Package2.NodeTypes.Foo_yaml' => true,
                    'pathPackages.Package2.Configuration.NodeTypes_yaml' => true,
                    'pathConfiguration.NodeTypes_yaml' => true,
                    'pathConfiguration.NodeTypes_SomeSuffix_yaml' => true,
                    'pathConfiguration.Testing.NodeTypes_yaml' => true,
                    'pathConfiguration.Testing.NodeTypes_SomeSuffix_yaml' => true,
                    'pathConfiguration.Testing.SomeSubContext.NodeTypes_yaml' => true,
                ],
            ],
            'NodeType:PathPackages.Package1.NodeTypes.Bar_yaml' => [
                'options' => [
                    'path' => 'Packages/Package1/NodeTypes/Bar.yaml',
                ],
            ],
            'NodeType:PathPackages.Package1.NodeTypes.Foo_yaml' => [
                'options' => [
                    'path' => 'Packages/Package1/NodeTypes/Foo.yaml',
                ],
            ],
            'NodeType:PathPackages.Package1.Configuration.NodeTypes_yaml' => [
                'options' => [
                    'path' => 'Packages/Package1/Configuration/NodeTypes.yaml',
                ],
            ],
            'NodeType:PathPackages.Package1.Configuration.NodeTypes_SomeSuffix_yaml' => [
                'options' => [
                    'path' => 'Packages/Package1/Configuration/NodeTypes.SomeSuffix.yaml',
                ],
            ],
            'NodeType:PathPackages.Package2.NodeTypes.Foo_yaml' => [
                'options' => [
                    'path' => 'Packages/Package2/NodeTypes/Foo.yaml',
                ],
            ],
            'NodeType:PathPackages.Package2.Configuration.NodeTypes_yaml' => [
                'options' => [
                    'path' => 'Packages/Package2/Configuration/NodeTypes.yaml',
                ],
            ],
            'NodeType:PathConfiguration.NodeTypes_yaml' => [
                'options' => [
                    'path' => 'Configuration/NodeTypes.yaml',
                ],
            ],
            'NodeType:PathConfiguration.NodeTypes_SomeSuffix_yaml' => [
                'options' => [
                    'path' => 'Configuration/NodeTypes.SomeSuffix.yaml',
                ],
            ],
            'NodeType:PathConfiguration.Testing.NodeTypes_yaml' => [
                'options' => [
                    'path' => 'Configuration/Testing/NodeTypes.yaml',
                ],
            ],
            'NodeType:PathConfiguration.Testing.NodeTypes_SomeSuffix_yaml' => [
                'options' => [
                    'path' => 'Configuration/Testing/NodeTypes.SomeSuffix.yaml',
                ],
            ],
            'NodeType:PathConfiguration.Testing.SomeSubContext.NodeTypes_yaml' => [
                'options' => [
                    'path' => 'Configuration/Testing/SomeSubContext/NodeTypes.yaml',
                ],
            ],
        ];
        self::assertSame($expectedResult, $actualResult);
    }

    /**
     * @test
     */
    public function bothPackagesReversedOrder(): void
    {
        $actualResult = $this->nodeTypesLoader->load([$this->mockPackage2, $this->mockPackage1], $this->mockApplicationContext);
        $expectedResult = [
            'Some.Node:Type' => [
                'options' => [
                    'finalPath' => 'Configuration/Testing/SomeSubContext/NodeTypes.yaml',
                    'pathPackages.Package2.NodeTypes.Foo_yaml' => true,
                    'pathPackages.Package2.Configuration.NodeTypes_yaml' => true,
                    'pathPackages.Package1.NodeTypes.Bar_yaml' => true,
                    'pathPackages.Package1.NodeTypes.Foo_yaml' => true,
                    'pathPackages.Package1.Configuration.NodeTypes_yaml' => true,
                    'pathPackages.Package1.Configuration.NodeTypes_SomeSuffix_yaml' => true,
                    'pathConfiguration.NodeTypes_yaml' => true,
                    'pathConfiguration.NodeTypes_SomeSuffix_yaml' => true,
                    'pathConfiguration.Testing.NodeTypes_yaml' => true,
                    'pathConfiguration.Testing.NodeTypes_SomeSuffix_yaml' => true,
                    'pathConfiguration.Testing.SomeSubContext.NodeTypes_yaml' => true,
                ],
            ],
            'NodeType:PathPackages.Package2.NodeTypes.Foo_yaml' => [
                'options' => [
                    'path' => 'Packages/Package2/NodeTypes/Foo.yaml',
                ],
            ],
            'NodeType:PathPackages.Package2.Configuration.NodeTypes_yaml' => [
                'options' => [
                    'path' => 'Packages/Package2/Configuration/NodeTypes.yaml',
                ],
            ],
            'NodeType:PathPackages.Package1.NodeTypes.Bar_yaml' => [
                'options' => [
                    'path' => 'Packages/Package1/NodeTypes/Bar.yaml',
                ],
            ],
            'NodeType:PathPackages.Package1.NodeTypes.Foo_yaml' => [
                'options' => [
                    'path' => 'Packages/Package1/NodeTypes/Foo.yaml',
                ],
            ],
            'NodeType:PathPackages.Package1.Configuration.NodeTypes_yaml' => [
                'options' => [
                    'path' => 'Packages/Package1/Configuration/NodeTypes.yaml',
                ],
            ],
            'NodeType:PathPackages.Package1.Configuration.NodeTypes_SomeSuffix_yaml' => [
                'options' => [
                    'path' => 'Packages/Package1/Configuration/NodeTypes.SomeSuffix.yaml',
                ],
            ],
            'NodeType:PathConfiguration.NodeTypes_yaml' => [
                'options' => [
                    'path' => 'Configuration/NodeTypes.yaml',
                ],
            ],
            'NodeType:PathConfiguration.NodeTypes_SomeSuffix_yaml' => [
                'options' => [
                    'path' => 'Configuration/NodeTypes.SomeSuffix.yaml',
                ],
            ],
            'NodeType:PathConfiguration.Testing.NodeTypes_yaml' => [
                'options' => [
                    'path' => 'Configuration/Testing/NodeTypes.yaml',
                ],
            ],
            'NodeType:PathConfiguration.Testing.NodeTypes_SomeSuffix_yaml' => [
                'options' => [
                    'path' => 'Configuration/Testing/NodeTypes.SomeSuffix.yaml',
                ],
            ],
            'NodeType:PathConfiguration.Testing.SomeSubContext.NodeTypes_yaml' => [
                'options' => [
                    'path' => 'Configuration/Testing/SomeSubContext/NodeTypes.yaml',
                ],
            ],
        ];
        self::assertSame($expectedResult, $actualResult);
    }

    /**
     * This is a test case for the issue https://github.com/neos/neos-development-collection/issues/3449
     *
     * @test
     */
    public function nodeTypeCanBeOverriddenFromNodeTypesFolder(): void
    {
        $mockSeoPackage = $this->mockPackage('Neos.Seo');
        $mockCustomPackage = $this->mockPackage('Some.Package');

        $nodeTypesLoader = new NodeTypesLoader(new YamlSource(), __DIR__);
        $actualResult = $nodeTypesLoader->load([$mockSeoPackage, $mockCustomPackage], $this->mockApplicationContext);
        $expectedResult = [
            'Neos.Seo:TitleTagMixin' => [
                'abstract' => true,
                'properties' => [
                    'titleOverride' => [
                        'type' => 'string',
                        'ui' => [
                            'label' => 'i18n',
                            'reloadIfChanged' => true,
                            'inspector' => [
                                'group' => 'document',
                                'position' => 10000,
                                'editor' => 'Neos.Neos/Inspector/Editors/TextAreaEditor',
                                'editorOptions' => [
                                    'placeholder' => 'i18n',
                                ],
                            ],
                        ],
                        'validation' => null,
                    ],
                ],
            ],
        ];
        self::assertSame($expectedResult, $actualResult);
    }
}
