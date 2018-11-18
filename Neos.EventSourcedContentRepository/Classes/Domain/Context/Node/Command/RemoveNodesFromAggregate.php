<?php
namespace Neos\EventSourcedContentRepository\Domain\Context\Node\Command;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\ContentRepository\Domain\ValueObject\ContentStreamIdentifier;
use Neos\ContentRepository\Domain\ValueObject\NodeAggregateIdentifier;
use Neos\ContentRepository\DimensionSpace\DimensionSpace\DimensionSpacePointSet;
use Neos\EventSourcedContentRepository\Exception;

/**
 * From the NodeAggregate identified by ContentStreamIdentifier and NodeAggregateIdentifier,
 * *recursively* remove the nodes in the given DimensionSpacePointSet. This does NOT take the Dimension Tree into account;
 * if you want to remove all specializations of dimensions, you need to specify them all in the DimensionSpacePointSet.
 *
 * NOTE: If the last edge pointing to a node is removed, the corresponding node is removed as well (as it
 * is not reachable anymore).
 */
final class RemoveNodesFromAggregate
{

    /**
     * @var ContentStreamIdentifier
     */
    private $contentStreamIdentifier;

    /**
     * @var NodeAggregateIdentifier
     */
    private $nodeAggregateIdentifier;

    /**
     * @var DimensionSpacePointSet
     */
    private $dimensionSpacePointSet;

    /**
     * RemoveNode constructor.
     * @param ContentStreamIdentifier $contentStreamIdentifier
     * @param NodeAggregateIdentifier $nodeAggregateIdentifier
     * @param DimensionSpacePointSet $dimensionSpacePointSet
     */
    public function __construct(ContentStreamIdentifier $contentStreamIdentifier, NodeAggregateIdentifier $nodeAggregateIdentifier, DimensionSpacePointSet $dimensionSpacePointSet)
    {
        $this->contentStreamIdentifier = $contentStreamIdentifier;
        $this->nodeAggregateIdentifier = $nodeAggregateIdentifier;
        $this->dimensionSpacePointSet = $dimensionSpacePointSet;

        if (count($this->dimensionSpacePointSet->getPoints()) === 0) {
            throw new Exception('You need to specify a non-empty DimensionSpacePointSet in RemoveNodesFromAggregate!', 1532091306);
        }
    }

    /**
     * @return ContentStreamIdentifier
     */
    public function getContentStreamIdentifier(): ContentStreamIdentifier
    {
        return $this->contentStreamIdentifier;
    }

    /**
     * @return NodeAggregateIdentifier
     */
    public function getNodeAggregateIdentifier(): NodeAggregateIdentifier
    {
        return $this->nodeAggregateIdentifier;
    }

    /**
     * @return DimensionSpacePointSet
     */
    public function getDimensionSpacePointSet(): DimensionSpacePointSet
    {
        return $this->dimensionSpacePointSet;
    }
}
