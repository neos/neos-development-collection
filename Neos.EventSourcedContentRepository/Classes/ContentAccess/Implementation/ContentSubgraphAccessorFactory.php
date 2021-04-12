<?php
declare(strict_types=1);

namespace Neos\EventSourcedContentRepository\ContentAccess\Implementation;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Annotations as Flow;
use Neos\ContentRepository\DimensionSpace\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\Domain\ContentStream\ContentStreamIdentifier;
use Neos\EventSourcedContentRepository\ContentAccess\Exception\InvalidAccessorConfiguration;
use Neos\EventSourcedContentRepository\ContentAccess\NodeAccessorFactoryInterface;
use Neos\EventSourcedContentRepository\ContentAccess\NodeAccessorInterface;
use Neos\EventSourcedContentRepository\ContentAccess\Parts\FindChildNodesInterface;
use Neos\EventSourcedContentRepository\Domain\Context\Parameters\VisibilityConstraints;
use Neos\EventSourcedContentRepository\Domain\Projection\Content\ContentGraphInterface;

/**
 * @Flow\Scope("singleton")
 */
final class ContentSubgraphAccessorFactory implements NodeAccessorFactoryInterface
{
    /**
     * @Flow\Inject
     * @var ContentGraphInterface
     */
    protected $contentGraph;

    public function build(ContentStreamIdentifier $contentStreamIdentifier, DimensionSpacePoint $dimensionSpacePoint, VisibilityConstraints $visibilityConstraints, ?NodeAccessorInterface $nextAccessor = null): NodeAccessorInterface
    {
        if ($nextAccessor !== null) {
            throw new InvalidAccessorConfiguration('The ContentSubgraphAccessor must be always configured LAST in the accessor chain, because it handles all calls exhaustively and NEVER delegates to the next accessor. You passed in ' . get_class($nextAccessor) . ' as $nextAccessor.', 1617949321);
        }

        $subgraph = $this->contentGraph->getSubgraphByIdentifier($contentStreamIdentifier, $dimensionSpacePoint, $visibilityConstraints);
        return new ContentSubgraphAccessor($subgraph);
    }
}
