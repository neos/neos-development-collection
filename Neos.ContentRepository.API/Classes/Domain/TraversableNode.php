<?php
declare(strict_types=1);

namespace Neos\ContentRepository\Api\Domain;

/*
 * This file is part of the Neos.ContentRepository.Api package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\ContentRepository\Domain\Projection\Content\NodeInterface;
use Neos\EventSourcedContentRepository\Domain\Projection\Content\ContentSubgraphInterface;

/**
 * The interface to be implemented by node based read models
 */
final class TraversableNode implements NodeBasedReadModelInterface
{
    use Feature\NodeIdentity;
    use Feature\NodeMetadata;
    use Feature\PropertyAccess;
    use Feature\SubgraphTraversal;

    public function __construct(NodeInterface $node, ContentSubgraphInterface $subgraph)
    {
        $this->node = $node;
        $this->subgraph = $subgraph;
    }
}
