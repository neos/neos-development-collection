<?php

namespace Neos\ContentGraph\Domain\Projection;

/*
 * This file is part of the Neos.ContentGraph package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */
use Neos\ContentRepository\Domain\Context\Node\Event;
use Neos\ContentRepository\Domain as ContentRepository;
use Neos\EventSourcing\Projection\ProjectorInterface;
use Neos\Flow\Annotations as Flow;

/**
 * The abstract alternate reality-aware graph projector to be used for arbitrary database back ends
 */
abstract class AbstractGraphProjector implements ProjectorInterface
{

    final public function whenNodePropertyWasSet(Event\NodePropertyWasSet $event)
    {

    }

    abstract protected function transactional(callable $operations);

    abstract protected function addNode(Node $node);

    abstract protected function getNode(ContentRepository\ValueObject\NodeIdentifier $nodeIdentifier, ContentRepository\ValueObject\ContentStreamIdentifier $contentStreamIdentifier, ContentRepository\ValueObject\DimensionSpacePoint $dimensionSpacePoint): ?Node;

    abstract protected function connectHierarchy(
        ContentRepository\ValueObject\NodeIdentifier $parentNodeIdentifier,
        ContentRepository\ValueObject\NodeIdentifier $childNodeIdentifier,
        ContentRepository\ValueObject\NodeIdentifier $precedingSiblingNodeIdentifier = null,
        ContentRepository\ValueObject\NodeName $edgeName = null,
        ContentRepository\ValueObject\ContentStreamIdentifier $contentStreamIdentifier,
        ContentRepository\ValueObject\DimensionSpacePointSet $dimensionSpacePointSet
    );

    abstract protected function connectRelation(string $startNodesIdentifierInGraph, string $endNodesIdentifierInGraph, string $relationshipName, array $properties, array $subgraphIdentifiers);

    abstract protected function reconnectHierarchy(string $fallbackNodesIdentifierInGraph, string $newVariantNodesIdentifierInGraph, array $subgraphIdentifiers);
}
