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

use Neos\ContentRepository\DimensionSpace\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\Domain\ContentStream\ContentStreamIdentifier;
use Neos\ContentRepository\Domain\NodeType\NodeTypeConstraints;
use Neos\EventSourcedContentRepository\ContentAccess\NodeAccessorInterface;
use Neos\EventSourcedContentRepository\ContentAccess\Parts\FindChildNodesInterface;
use Neos\EventSourcedContentRepository\Domain\Context\Parameters\VisibilityConstraints;
use Neos\EventSourcedContentRepository\Domain\Projection\Content\ContentSubgraphInterface;
use Neos\EventSourcedContentRepository\Domain\Projection\Content\NodeInterface;

/**
 * @Flow\Scope("singleton")
 */
class ContentSubgraphAccessor implements FindChildNodesInterface
{
    public function findChildNodes(ContentStreamIdentifier $contentStreamIdentifier, DimensionSpacePoint $dimensionSpacePoint, VisibilityConstraints $visibilityConstraints, NodeInterface $parentNode, NodeTypeConstraints $nodeTypeConstraints = null, int $limit = null, int $offset = null): ?\Closure
    {
        return fn() => $this->contentGraph->getSubgraphByIdentifier($contentStreamIdentifier, $dimensionSpacePoint, $visibilityConstraints)->findChildNodes($parentNode->getNodeAggregateIdentifier(), $nodeTypeConstraints, $limit, $offset);
    }
}
