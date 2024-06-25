<?php
declare(strict_types=1);
namespace Neos\ContentRepositoryRegistry\Tests\Unit\Configuration;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\ContentRepositoryRegistry\Configuration\NodeTypeEnrichmentService;
use Neos\ContentRepositoryRegistry\Configuration\NodeTypesLoader;
use Neos\Flow\Configuration\Source\YamlSource;
use Neos\Flow\Core\ApplicationContext;
use Neos\Flow\Package\FlowPackageInterface;
use Neos\Flow\Tests\UnitTestCase;
use Neos\Utility\Files;

class NodeTypeEnrichmentServiceTest extends UnitTestCase
{
    private ?NodeTypeEnrichmentService $nodeTypeEnrichmentService;

    private ?ApplicationContext $applicationContext;


    public function setUp(): void
    {
        $this->nodeTypeEnrichmentService = new NodeTypeEnrichmentService();
        $this->applicationContext = new ApplicationContext('Testing/SubContext');    }

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
    public function EnrichNodeTypeLabelsConfig(): void
    {
        $mockEnrichmentPackage = $this->mockPackage('Neos.Enrichment');

        $nodeTypesLoader = new NodeTypesLoader(new YamlSource(), __DIR__);
        $nodeConfiguration = $nodeTypesLoader->load([$mockEnrichmentPackage], $this->applicationContext);

        $expectedResult = [
            'Neos.Enrichment:Translation' => [
                'properties' => [
                    'title' => [
                        'type' => 'string',
                        'ui' => [
                            'label' => 'Neos.Enrichment:NodeTypes.Translation:properties.title'
                        ]
                    ]
                ],
                'references' => [
                    'docReference' => [
                        'ui' => [
                            'label' => 'Neos.Enrichment:NodeTypes.Translation:references.docReference'
                        ],
                        'properties' => [
                            'referenceProperty' => [
                                'type' => 'text',
                                'ui' => [
                                    'label' => 'Neos.Enrichment:NodeTypes.Translation:docReference.properties.referenceProperty'
                                ]
                            ]
                        ],
                        'type' => 'reference'
                    ]
                ]
            ]
        ];
        $actualResult = $this->nodeTypeEnrichmentService->enrichNodeTypeLabelsConfiguration($nodeConfiguration);

        self::assertEquals($expectedResult, $actualResult);
    }


}
