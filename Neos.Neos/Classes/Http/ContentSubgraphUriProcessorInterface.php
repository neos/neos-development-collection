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

use Neos\ContentRepository\Domain\Model\NodeInterface;
use Neos\Flow\Mvc\Routing;

/**
 * The interface for content subgraph URI processors
 */
interface ContentSubgraphUriProcessorInterface
{
    /**
     * @param NodeInterface $node
     * @return Routing\Dto\UriConstraints
     */
    public function resolveDimensionUriConstraints(NodeInterface $node): Routing\Dto\UriConstraints;
}
