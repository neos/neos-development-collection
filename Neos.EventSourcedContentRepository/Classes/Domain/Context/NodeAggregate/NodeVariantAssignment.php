<?php
namespace Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate;

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
use Neos\ContentRepository\Domain\NodeAggregate\NodeAggregateIdentifier;

/**
 * A node variant assignment, identifying a node variant by node aggregate identifier and origin dimension space point.
 *
 * This is used in structural operations like node move to assign a new node within the same content stream
 * as a new parent, sibling etc.
 *
 * In case of move, this is the "target node" underneath which or next to which we want to move our source.
 *
 * @Flow\Proxy(false)
 */
final class NodeVariantAssignment implements \JsonSerializable
{
    /**
     * @var NodeAggregateIdentifier
     */
    private $nodeAggregateIdentifier;

    /**
     * @var OriginDimensionSpacePoint
     */
    private $originDimensionSpacePoint;

    public function __construct(NodeAggregateIdentifier $nodeAggregateIdentifier, OriginDimensionSpacePoint $originDimensionSpacePoint)
    {
        $this->nodeAggregateIdentifier = $nodeAggregateIdentifier;
        $this->originDimensionSpacePoint = $originDimensionSpacePoint;
    }

    public static function createFromArray(array $array): self
    {
        return new self(
            NodeAggregateIdentifier::fromString($array['nodeAggregateIdentifier']),
            OriginDimensionSpacePoint::fromArray($array['originDimensionSpacePoint'])
        );
    }

    public function getNodeAggregateIdentifier(): NodeAggregateIdentifier
    {
        return $this->nodeAggregateIdentifier;
    }

    public function getOriginDimensionSpacePoint(): OriginDimensionSpacePoint
    {
        return $this->originDimensionSpacePoint;
    }

    public function jsonSerialize(): array
    {
        return [
            'nodeAggregateIdentifier' => $this->nodeAggregateIdentifier,
            'originDimensionSpacePoint' => $this->originDimensionSpacePoint,
        ];
    }

    public function __toString(): string
    {
        return json_encode($this);
    }
}
