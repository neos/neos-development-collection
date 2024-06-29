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
use Neos\Flow\Core\ApplicationContext;
use Neos\Flow\Package\FlowPackageInterface;
use Neos\Flow\Tests\UnitTestCase;
use Neos\Utility\Files;
use Symfony\Component\Yaml\Yaml;

class NodeTypeEnrichmentServiceTest extends UnitTestCase
{
    private ?NodeTypeEnrichmentService $nodeTypeEnrichmentService;

    public function setUp(): void
    {
        $this->nodeTypeEnrichmentService = new NodeTypeEnrichmentService();
    }


    /**
     * @test
     */
    public function EnrichNodeTypeLabelsConfig(): void
    {
        $nodeConfiguration = YAML::parse(<<<'YAML'
        'Neos.Enrichment:Translation':
          properties:
            title:
              type: string
              ui:
                label: i18n
          references:
            docReference:
              type: reference
              ui:
                label: i18n
              properties:
                referenceProperty:
                  type: text
                  ui:
                    label: i18n
        YAML);

        $expectedResult = YAML::parse(<<<'YAML'
        'Neos.Enrichment:Translation':
          properties:
            title:
              type: string
              ui:
                label: Neos.Enrichment:NodeTypes.Translation:properties.title
          references:
            docReference:
              type: reference
              ui:
                label: Neos.Enrichment:NodeTypes.Translation:references.docReference
              properties:
                referenceProperty:
                  type: text
                  ui:
                    label: Neos.Enrichment:NodeTypes.Translation:docReference.properties.referenceProperty
        YAML);

        $actualResult = $this->nodeTypeEnrichmentService->enrichNodeTypeLabelsConfiguration($nodeConfiguration);

        self::assertEquals($expectedResult, $actualResult);
    }


}
