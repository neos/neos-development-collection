<?php
namespace Neos\Neos\Http;

/*
 * This file is part of the Neos.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Mvc\Routing;
use Neos\Neos\Domain\Context\Content\NodeAddress;

/**
 * The interface for content subgraph URI processors
 */
interface ContentSubgraphUriProcessorInterface
{
    /**
     * @param NodeAddress $nodeAddress
     * @return Routing\Dto\UriConstraints
     */
    public function resolveDimensionUriConstraints(NodeAddress $nodeAddress): Routing\Dto\UriConstraints;
}
