<?php
declare(strict_types=1);

namespace Neos\ContentRepository\NodeAccess\NodeAccessor\Implementation;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\ContentRepository\NodeAccess\NodeAccessor\NodeAccessorFactoryInterface;
use Neos\ContentRepository\NodeAccess\NodeAccessor\NodeAccessorInterface;
use Neos\ContentRepository\Projection\ContentGraph\ContentSubgraphIdentity;
use Neos\ContentRepositoryRegistry\ContentRepositoryRegistry;
use Neos\Flow\Annotations as Flow;
use Neos\ContentRepository\DimensionSpace\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\SharedModel\Workspace\ContentStreamIdentifier;
use Neos\ContentRepository\NodeAccess\NodeAccessor\Exception\InvalidAccessorConfiguration;
use Neos\ContentRepository\SharedModel\VisibilityConstraints;
use Neos\ContentRepository\Projection\ContentGraph\ContentGraphInterface;

#[Flow\Scope('singleton')]
final class ContentSubgraphAccessorFactory implements NodeAccessorFactoryInterface
{
    /**
     * @Flow\Inject
     * @var ContentRepositoryRegistry
     */
    protected $contentRepositoryRegistry;

    public function build(
        ContentSubgraphIdentity $contentSubgraphIdentity,
        ?NodeAccessorInterface $nextAccessor = null
    ): NodeAccessorInterface {
        if ($nextAccessor !== null) {
            throw new InvalidAccessorConfiguration(
                'The ContentSubgraphAccessor must be always configured LAST in the accessor chain,'
                    . ' because it handles all calls exhaustively and NEVER delegates to the next accessor.'
                    . ' You passed in ' . get_class($nextAccessor) . ' as $nextAccessor.',
                1617949321
            );
        }

        $contentRepository = $this->contentRepositoryRegistry->get($contentSubgraphIdentity->contentRepositoryIdentifier);
        $contentGraph = $contentRepository->getContentGraph();
        $subgraph = $contentGraph->getSubgraphByIdentifier(
            $contentSubgraphIdentity->contentStreamIdentifier,
            $contentSubgraphIdentity->dimensionSpacePoint,
            $contentSubgraphIdentity->visibilityConstraints
        );

        return new ContentSubgraphAccessor($subgraph, $contentGraph);
    }
}
