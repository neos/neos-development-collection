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
use Neos\Utility\Arrays;
use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Yaml\Yaml;

class NodeTypesLoaderTest extends UnitTestCase
{
    /**
     * @var NodeTypesLoader|MockObject
     */
    private NodeTypesLoader $nodeTypesLoader;

    /**
     * @var ApplicationContext|MockObject
     */
    private ApplicationContext $mockApplicationContext;

    /**
     * @var FlowPackageInterface|MockObject
     */
    private FlowPackageInterface $mockPackage1;

    /**
     * @var FlowPackageInterface|MockObject
     */
    private FlowPackageInterface $mockPackage2;

    public function setUp(): void
    {
        $nodeTypeFiles = $this->mockNodeTypeDefinitions([
            'Configuration/NodeTypes.yaml',
            'Configuration/NodeTypes.SomeSuffix.yaml',
            'Configuration/Testing/NodeTypes.yaml',
            'Configuration/Testing/NodeTypes.SomeSuffix.yaml',
            'Configuration/Testing/SomeSubContext/NodeTypes.yaml',
            'Configuration/Testing/SomeOtherSubContext/NodeTypes.yaml',
            'Configuration/Testing/SomeOtherSubContext/NodeTypes.SomeSuffix.yaml',
            'NodeTypes/Foo.yaml',
            'NodeTypes/Bar.yaml',
            'Packages/Package1/Configuration/NodeTypes.yaml',
            'Packages/Package1/Configuration/NodeTypes.SomeSuffix.yaml',
            'Packages/Package1/Configuration/SomeSubContext/NodeTypes.yaml',
            'Packages/Package1/Configuration/SomeOtherSubContext/NodeTypes.yaml',
            'Packages/Package1/Configuration/SomeOtherSubContext/NodeTypes.SomeSuffix.yaml',
            'Packages/Package1/NodeTypes/Foo.yaml',
            'Packages/Package1/NodeTypes/Bar.yaml',
            'Packages/Package2/Configuration/NodeTypes.yaml',
            'Packages/Package2/Configuration/SomeOtherSubContext/NodeTypes.yaml',
            'Packages/Package2/NodeTypes/Foo.yaml',
        ]);
        vfsStream::setup('root', null, $nodeTypeFiles);
        $this->nodeTypesLoader = new NodeTypesLoader(new YamlSource(), vfsStream::url('root/Configuration/'));

        $this->mockApplicationContext = $this->getMockBuilder(ApplicationContext::class)->disableOriginalConstructor()->getMock();
        $this->mockApplicationContext->method('getHierarchy')->willReturn(['Testing', 'Testing/SomeSubContext']);

        $this->mockPackage1 = $this->getMockBuilder(FlowPackageInterface::class)->getMock();
        $this->mockPackage1->method('getPackagePath')->willReturn(vfsStream::url('root/Packages/Package1'));

        $this->mockPackage2 = $this->getMockBuilder(FlowPackageInterface::class)->getMock();
        $this->mockPackage2->method('getPackagePath')->willReturn(vfsStream::url('root/Packages/Package2'));
    }

    private function mockNodeTypeDefinitions(array $paths): array
    {
        $result = [];
        foreach ($paths as $path) {
            $escapedPath = str_replace(['.', '/'], ['_', '.'], $path);
            $nodeTypeDefinitions = Yaml::dump([
                'Some.Node:Type' => [
                    'options' => [
                        'finalPath' => $path,
                        'path' . $escapedPath => true,
                    ]
                ],
                'NodeType:Path' . $escapedPath => [
                    'options' => [
                        'path' => $path,
                    ]
                ],
            ]);
            $pathSegments = explode('/', $path);
            $result = Arrays::setValueByPath($result, $pathSegments, $nodeTypeDefinitions);
        }
        return $result;
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
                    'pathConfiguration.Testing.NodeTypes_yaml' => true,
                    'pathConfiguration.Testing.SomeSubContext.NodeTypes_yaml' => true,
                ]
            ],
            'NodeType:PathConfiguration.NodeTypes_yaml' => [
                'options' => [
                    'path' => 'Configuration/NodeTypes.yaml'
                ]
            ],
            'NodeType:PathConfiguration.Testing.NodeTypes_yaml' => [
                'options' => [
                    'path' => 'Configuration/Testing/NodeTypes.yaml'
                ]
            ],
            'NodeType:PathConfiguration.Testing.SomeSubContext.NodeTypes_yaml' => [
                'options' => [
                    'path' => 'Configuration/Testing/SomeSubContext/NodeTypes.yaml'
                ]
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
                    'pathPackages.Package1.NodeTypes.Foo_yaml' => true,
                    'pathPackages.Package1.NodeTypes.Bar_yaml' => true,
                    'pathConfiguration.NodeTypes_yaml' => true,
                    'pathConfiguration.Testing.NodeTypes_yaml' => true,
                    'pathConfiguration.Testing.SomeSubContext.NodeTypes_yaml' => true,
                ]
            ],
            'NodeType:PathPackages.Package1.NodeTypes.Foo_yaml' => [
                'options' => [
                    'path' => 'Packages/Package1/NodeTypes/Foo.yaml'
                ]
            ],
            'NodeType:PathPackages.Package1.NodeTypes.Bar_yaml' => [
                'options' => [
                    'path' => 'Packages/Package1/NodeTypes/Bar.yaml'
                ]
            ],
            'NodeType:PathConfiguration.NodeTypes_yaml' => [
                'options' => [
                    'path' => 'Configuration/NodeTypes.yaml'
                ]
            ],
            'NodeType:PathConfiguration.Testing.NodeTypes_yaml' => [
                'options' => [
                    'path' => 'Configuration/Testing/NodeTypes.yaml'
                ]
            ],
            'NodeType:PathConfiguration.Testing.SomeSubContext.NodeTypes_yaml' => [
                'options' => [
                    'path' => 'Configuration/Testing/SomeSubContext/NodeTypes.yaml'
                ]
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
                    'pathConfiguration.NodeTypes_yaml' => true,
                    'pathConfiguration.Testing.NodeTypes_yaml' => true,
                    'pathConfiguration.Testing.SomeSubContext.NodeTypes_yaml' => true,
                ],
            ],
            'NodeType:PathPackages.Package2.NodeTypes.Foo_yaml' => [
                'options' => [
                    'path' => 'Packages/Package2/NodeTypes/Foo.yaml',
                ]
            ],
            'NodeType:PathConfiguration.NodeTypes_yaml' => [
                'options' => [
                    'path' => 'Configuration/NodeTypes.yaml',
                ]
            ],
            'NodeType:PathConfiguration.Testing.NodeTypes_yaml' => [
                'options' => [
                    'path' => 'Configuration/Testing/NodeTypes.yaml',
                ]
            ],
            'NodeType:PathConfiguration.Testing.SomeSubContext.NodeTypes_yaml' => [
                'options' => [
                    'path' => 'Configuration/Testing/SomeSubContext/NodeTypes.yaml',
                ]
            ]
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
                    'pathPackages.Package1.NodeTypes.Foo_yaml' => true,
                    'pathPackages.Package1.NodeTypes.Bar_yaml' => true,
                    'pathPackages.Package2.NodeTypes.Foo_yaml' => true,
                    'pathConfiguration.NodeTypes_yaml' => true,
                    'pathConfiguration.Testing.NodeTypes_yaml' => true,
                    'pathConfiguration.Testing.SomeSubContext.NodeTypes_yaml' => true,
                ],
            ],
            'NodeType:PathPackages.Package1.NodeTypes.Foo_yaml' => [
                'options' => [
                    'path' => 'Packages/Package1/NodeTypes/Foo.yaml',
                ],
            ],
            'NodeType:PathPackages.Package1.NodeTypes.Bar_yaml' => [
                'options' => [
                    'path' => 'Packages/Package1/NodeTypes/Bar.yaml',
                ],
            ],
            'NodeType:PathPackages.Package2.NodeTypes.Foo_yaml' => [
                'options' => [
                    'path' => 'Packages/Package2/NodeTypes/Foo.yaml',
                ],
            ],
            'NodeType:PathConfiguration.NodeTypes_yaml' => [
                'options' => [
                    'path' => 'Configuration/NodeTypes.yaml',
                ],
            ],
            'NodeType:PathConfiguration.Testing.NodeTypes_yaml' => [
                'options' => [
                    'path' => 'Configuration/Testing/NodeTypes.yaml',
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
                    'pathPackages.Package1.NodeTypes.Foo_yaml' => true,
                    'pathPackages.Package1.NodeTypes.Bar_yaml' => true,
                    'pathConfiguration.NodeTypes_yaml' => true,
                    'pathConfiguration.Testing.NodeTypes_yaml' => true,
                    'pathConfiguration.Testing.SomeSubContext.NodeTypes_yaml' => true,
                ],
            ],
            'NodeType:PathPackages.Package2.NodeTypes.Foo_yaml' => [
                'options' => [
                    'path' => 'Packages/Package2/NodeTypes/Foo.yaml',
                ],
            ],
            'NodeType:PathPackages.Package1.NodeTypes.Foo_yaml' => [
                'options' => [
                    'path' => 'Packages/Package1/NodeTypes/Foo.yaml',
                ],
            ],
            'NodeType:PathPackages.Package1.NodeTypes.Bar_yaml' => [
                'options' => [
                    'path' => 'Packages/Package1/NodeTypes/Bar.yaml',
                ],
            ],
            'NodeType:PathConfiguration.NodeTypes_yaml' => [
                'options' => [
                    'path' => 'Configuration/NodeTypes.yaml',
                ],
            ],
            'NodeType:PathConfiguration.Testing.NodeTypes_yaml' => [
                'options' => [
                    'path' => 'Configuration/Testing/NodeTypes.yaml',
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
}
