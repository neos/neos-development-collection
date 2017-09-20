<?php
namespace Neos\ContentRepository\Service;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\ContentRepository\Domain\Context\Dimension;
use Neos\ContentRepository\Domain\Context\DimensionSpace;
use Neos\Flow\Annotations as Flow;

/**
 * The fallback graph application service
 *
 * To be used as a read-only source of fallback information for graph aware services like command handlers
 *
 * Never use this on the read side since its initialization time grows linearly
 * by the amount of possible combinations of content dimension values
 *
 * @Flow\Scope("singleton")
 * @api
 */
class FallbackGraphService
{
    /**
     * @Flow\Inject
     * @var Dimension\Repository\IntraDimensionalFallbackGraph
     */
    protected $intraDimensionalFallbackGraph;

    /**
     * @Flow\Inject
     * @var DimensionSpace\Repository\InterDimensionalFallbackGraph
     */
    protected $interDimensionalFallbackGraph;


    /**
     * @param string $subgraphIdentifier
     * @return array
     */
    public function determineAffectedVariantSubgraphIdentifiers(string $subgraphIdentifier): array
    {
        $affectedVariantIdentifiers = [$subgraphIdentifier];
        $subgraph = $this->interDimensionalFallbackGraph->getSubgraphByDimensionSpacePointHash($subgraphIdentifier);
        foreach ($subgraph->getVariants() as $variantSubgraph) {
            $affectedVariantIdentifiers[] = $variantSubgraph->getIdentityHash();
        }

        return $affectedVariantIdentifiers;
    }

    /**
     * @param string $subgraphIdentifier
     * @return array
     */
    public function determineConnectedSubgraphIdentifiers(string $subgraphIdentifier): array
    {
        $subgraph = $this->interDimensionalFallbackGraph->getSubgraphByDimensionSpacePointHash($subgraphIdentifier);
        while ($subgraph->getFallback()) {
            $subgraph = $subgraph->getFallback();
        }
        $connectedVariantIdentifiers = [$subgraph->getIdentityHash()];
        foreach ($subgraph->getVariants() as $variantSubgraph) {
            $connectedVariantIdentifiers[] = $variantSubgraph->getIdentityHash();
        }
        return $connectedVariantIdentifiers;
    }

    /**
     * @return Dimension\Repository\IntraDimensionalFallbackGraph
     * @api
     */
    public function getIntraDimensionalFallbackGraph(): Dimension\Repository\IntraDimensionalFallbackGraph
    {
        return $this->intraDimensionalFallbackGraph;
    }

    /**
     * @return DimensionSpace\Repository\InterDimensionalFallbackGraph
     * @api
     */
    public function getInterDimensionalFallbackGraph(): DimensionSpace\Repository\InterDimensionalFallbackGraph
    {
        return $this->interDimensionalFallbackGraph;
    }
}
