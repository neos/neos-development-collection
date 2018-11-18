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

use Neos\ContentRepository\DimensionSpace\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\Domain\ValueObject\ContentStreamIdentifier;
use Neos\ContentRepository\Domain\ValueObject\NodeAggregateIdentifier;
use Neos\ContentRepository\Domain\ValueObject\NodeIdentifier;
use Neos\ContentRepository\Domain\ValueObject\NodeName;

/**
 * AddNodeToAggregate command
 */
final class AddNodeToAggregate
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
     * Location of the new node in the dimension space
     *
     * @var DimensionSpacePoint
     */
    private $dimensionSpacePoint;

    /**
     * Node identifier of the new node
     *
     * @var NodeIdentifier
     */
    private $nodeIdentifier;

    /**
     * @var NodeIdentifier
     */
    private $parentNodeIdentifier;

    /**
     * @var NodeName
     */
    private $nodeName;

    /**
     * AddNodeToAggregate constructor.
     *
     * @param ContentStreamIdentifier $contentStreamIdentifier
     * @param NodeAggregateIdentifier $nodeAggregateIdentifier
     * @param DimensionSpacePoint $dimensionSpacePoint
     * @param NodeIdentifier $nodeIdentifier
     * @param NodeIdentifier $parentNodeIdentifier
     * @param NodeName $nodeName
     */
    public function __construct(
        ContentStreamIdentifier $contentStreamIdentifier,
        NodeAggregateIdentifier $nodeAggregateIdentifier,
        DimensionSpacePoint $dimensionSpacePoint,
        NodeIdentifier $nodeIdentifier,
        NodeIdentifier $parentNodeIdentifier,
        NodeName $nodeName
    ) {
        $this->contentStreamIdentifier = $contentStreamIdentifier;
        $this->nodeAggregateIdentifier = $nodeAggregateIdentifier;
        $this->dimensionSpacePoint = $dimensionSpacePoint;
        $this->nodeIdentifier = $nodeIdentifier;
        $this->parentNodeIdentifier = $parentNodeIdentifier;
        $this->nodeName = $nodeName;
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
     * @return DimensionSpacePoint
     */
    public function getDimensionSpacePoint(): DimensionSpacePoint
    {
        return $this->dimensionSpacePoint;
    }

    /**
     * @return NodeIdentifier
     */
    public function getNodeIdentifier(): NodeIdentifier
    {
        return $this->nodeIdentifier;
    }

    /**
     * @return NodeIdentifier
     */
    public function getParentNodeIdentifier(): NodeIdentifier
    {
        return $this->parentNodeIdentifier;
    }

    /**
     * @return NodeName
     */
    public function getNodeName(): NodeName
    {
        return $this->nodeName;
    }
}
