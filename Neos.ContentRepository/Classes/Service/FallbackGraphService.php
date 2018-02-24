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
     * @var DimensionSpace\InterDimensionalVariationGraph
     */
    protected $interDimensionalFallbackGraph;


    /**
     * @param string $subgraphIdentifier
     * @return array
     */
    public function determineAffectedVariantSubgraphIdentifiers(string $subgraphIdentifier): array
    {
        $affectedVariantIdentifiers = [$subgraphIdentifier];
        $subgraph = $this->interDimensionalFallbackGraph->getWeightedDimensionSpacePointByHash($subgraphIdentifier);
        foreach ($subgraph->getSpecializations() as $variantSubgraph) {
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
        $subgraph = $this->interDimensionalFallbackGraph->getWeightedDimensionSpacePointByHash($subgraphIdentifier);
        while ($subgraph->getGeneralizations()) {
            $subgraph = $subgraph->getGeneralizations();
        }
        $connectedVariantIdentifiers = [$subgraph->getIdentityHash()];
        foreach ($subgraph->getSpecializations() as $variantSubgraph) {
            $connectedVariantIdentifiers[] = $variantSubgraph->getIdentityHash();
        }

        return $connectedVariantIdentifiers;
    }
}
