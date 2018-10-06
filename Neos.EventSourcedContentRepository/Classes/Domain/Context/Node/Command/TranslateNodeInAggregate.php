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
use Neos\ContentRepository\DimensionSpace\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\Domain\ValueObject\NodeIdentifier;

/**
 * Translate node in aggregate command
 *
 * Copies a node in a node aggregate to a translated node with the given dimension space point. The dimension space
 * point must not be an ancestor (generalization) or descendant (specialization) of the source node dimension space point.
 *
 * The node aggregate of the parent of the given node needs to have a visible node in the given dimension space point.
 */
final class TranslateNodeInAggregate
{

    /**
     * @var ContentStreamIdentifier
     */
    private $contentStreamIdentifier;

    /**
     * @var NodeIdentifier
     */
    private $sourceNodeIdentifier;

    /**
     * Node identifier for the translated node
     *
     * @var NodeIdentifier
     */
    private $destinationNodeIdentifier;

    /**
     * Dimension space point for the translated node
     *
     * @var DimensionSpacePoint
     */
    private $dimensionSpacePoint;

    /**
     * Parent node identifier of the translated node (optional, will be resolved if not given)
     *
     * @var NodeIdentifier
     */
    private $destinationParentNodeIdentifier;

    /**
     * TranslateNodeInAggregate constructor.
     *
     * @param ContentStreamIdentifier $contentStreamIdentifier
     * @param NodeIdentifier $sourceNodeIdentifier
     * @param NodeIdentifier $destinationNodeIdentifier
     * @param DimensionSpacePoint $dimensionSpacePoint
     * @param NodeIdentifier $destinationParentNodeIdentifier
     */
    public function __construct(
        ContentStreamIdentifier $contentStreamIdentifier,
        NodeIdentifier $sourceNodeIdentifier,
        NodeIdentifier $destinationNodeIdentifier,
        DimensionSpacePoint $dimensionSpacePoint,
        NodeIdentifier $destinationParentNodeIdentifier = null
    ) {
        $this->contentStreamIdentifier = $contentStreamIdentifier;
        $this->sourceNodeIdentifier = $sourceNodeIdentifier;
        $this->destinationNodeIdentifier = $destinationNodeIdentifier;
        $this->dimensionSpacePoint = $dimensionSpacePoint;
        $this->destinationParentNodeIdentifier = $destinationParentNodeIdentifier;
    }

    /**
     * @return ContentStreamIdentifier
     */
    public function getContentStreamIdentifier(): ContentStreamIdentifier
    {
        return $this->contentStreamIdentifier;
    }

    /**
     * @return NodeIdentifier
     */
    public function getSourceNodeIdentifier(): NodeIdentifier
    {
        return $this->sourceNodeIdentifier;
    }

    /**
     * @return NodeIdentifier
     */
    public function getDestinationNodeIdentifier(): NodeIdentifier
    {
        return $this->destinationNodeIdentifier;
    }

    /**
     * @return DimensionSpacePoint
     */
    public function getDimensionSpacePoint(): DimensionSpacePoint
    {
        return $this->dimensionSpacePoint;
    }

    /**
     * @return NodeIdentifier|null
     */
    public function getDestinationParentNodeIdentifier(): ?NodeIdentifier
    {
        return $this->destinationParentNodeIdentifier;
    }
}
