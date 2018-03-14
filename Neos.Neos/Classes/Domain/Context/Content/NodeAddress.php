<?php

namespace Neos\Neos\Domain\Context\Content;

/*
 * This file is part of the Neos.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\ContentRepository\Domain as ContentRepository;
use Neos\ContentRepository\Domain\ValueObject\ContentStreamIdentifier;
use Neos\ContentRepository\Domain\ValueObject\DimensionSpacePoint;
use Neos\ContentRepository\Domain\ValueObject\NodeAggregateIdentifier;
use Neos\Flow\Annotations as Flow;
use Neos\Neos\Domain\Context\Workspace\WorkspaceName;

/**
 * A persistent, external "address" of a node; used to link to it.
 *
 * Describes the intention of the user making the current request:
 * Show me
 *  node $nodeAggregateIdentifier
 *  in dimensions $dimensionSpacePoint
 *  in contentStreamIdentifier $contentStreamIdentifier
 *
 * It is used in Neos Routing to build a URI to a node.
 */
final class NodeAddress
{
    /**
     * @var ContentStreamIdentifier
     */
    protected $contentStreamIdentifier;

    /**
     * @var DimensionSpacePoint
     */
    protected $dimensionSpacePoint;

    /**
     * @var NodeAggregateIdentifier
     */
    protected $nodeAggregateIdentifier;

    /**
     * NodeAddress constructor.
     * @param ContentStreamIdentifier $contentStreamIdentifier
     * @param DimensionSpacePoint $dimensionSpacePoint
     * @param NodeAggregateIdentifier $nodeAggregateIdentifier
     */
    public function __construct(ContentStreamIdentifier $contentStreamIdentifier, DimensionSpacePoint $dimensionSpacePoint, NodeAggregateIdentifier $nodeAggregateIdentifier)
    {
        $this->contentStreamIdentifier = $contentStreamIdentifier;
        $this->dimensionSpacePoint = $dimensionSpacePoint;
        $this->nodeAggregateIdentifier = $nodeAggregateIdentifier;
    }

    public static function fromNode(ContentRepository\Projection\Content\NodeInterface $node)
    {
        return new NodeAddress($node->getContentStreamIdentifier(), $node->getDimensionSpacePoint(), $node->getNodeAggregateIdentifier());
    }

    /**
     * @return ContentStreamIdentifier
     */
    public function getContentStreamIdentifier(): ContentStreamIdentifier
    {
        return $this->contentStreamIdentifier;
    }

    /**
     * @return DimensionSpacePoint
     */
    public function getDimensionSpacePoint(): DimensionSpacePoint
    {
        return $this->dimensionSpacePoint;
    }

    /**
     * @return NodeAggregateIdentifier
     */
    public function getNodeAggregateIdentifier(): NodeAggregateIdentifier
    {
        return $this->nodeAggregateIdentifier;
    }

    public function serializeForUri(): string
    {
        return $this->contentStreamIdentifier->jsonSerialize() . '__' . $this->dimensionSpacePoint->serializeForUri() . '__' . $this->nodeAggregateIdentifier->jsonSerialize();
    }

    public static function fromUriString(string $uriString): NodeAddress
    {
        list($contentStreamIdentifierSerialized, $dimensionSpacePointSerialized, $nodeAggregateIdentifierSerialized) = explode('__', $uriString);
        $contentStreamIdentifier = new ContentStreamIdentifier($contentStreamIdentifierSerialized);
        $dimensionSpacePoint = DimensionSpacePoint::fromUriRepresentation($dimensionSpacePointSerialized);
        $nodeAggregateIdentifier = new NodeAggregateIdentifier($nodeAggregateIdentifierSerialized);

        return new NodeAddress($contentStreamIdentifier, $dimensionSpacePoint, $nodeAggregateIdentifier);
    }

    public function withNodeAggregateIdentifier(NodeAggregateIdentifier $nodeAggregateIdentifier): NodeAddress
    {
        return new NodeAddress($this->contentStreamIdentifier, $this->dimensionSpacePoint, $nodeAggregateIdentifier);
    }

    public function withDimensionSpacePoint(DimensionSpacePoint $dimensionSpacePoint): NodeAddress
    {
        return new NodeAddress($this->contentStreamIdentifier, $dimensionSpacePoint, $this->nodeAggregateIdentifier);
    }
}
