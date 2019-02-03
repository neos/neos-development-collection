<?php
declare(strict_types=1);
namespace Neos\EventSourcedContentRepository\Domain\Context\Node;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Cache\CacheAwareInterface;
use Neos\ContentRepository\DimensionSpace\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\Domain\ValueObject\ContentStreamIdentifier;
use Neos\ContentRepository\Domain\ValueObject\NodeAggregateIdentifier;

/**
 * The node identifier value object
 *
 * Represents the identity of a specific node in the content graph and is thus composed of
 * * the node's aggregate's external identifier
 * * the content stream the node exists in
 * * the dimension space point the node originates in within its aggregate
 *
 * @package Neos\EventSourcedContentRepository
 */
final class NodeIdentifier implements CacheAwareInterface, \JsonSerializable
{
    /**
     * @var NodeAggregateIdentifier
     */
    protected $nodeAggregateIdentifier;

    /**
     * @var ContentStreamIdentifier
     */
    protected $contentStreamIdentifier;

    /**
     * @var DimensionSpacePoint
     */
    protected $originDimensionSpacePoint;

    public function __construct(NodeAggregateIdentifier $nodeAggregateIdentifier, ContentStreamIdentifier $contentStreamIdentifier, DimensionSpacePoint $originDimensionSpacePoint)
    {
        $this->nodeAggregateIdentifier = $nodeAggregateIdentifier;
        $this->contentStreamIdentifier = $contentStreamIdentifier;
        $this->originDimensionSpacePoint = $originDimensionSpacePoint;
    }

    /**
     * @param string $serializedNodeIdentifier
     * @return NodeIdentifier
     * @throws \Exception
     */
    public static function fromJsonString(string $serializedNodeIdentifier): NodeIdentifier
    {
        $jsonArray = json_decode($serializedNodeIdentifier, true);

        if (!isset($jsonArray['nodeAggregateIdentifier']) || !isset($jsonArray['contentStreamIdentifier']) || !isset($jsonArray['originDimensionSpacePoint'])) {
            throw new \InvalidArgumentException($serializedNodeIdentifier . ' is no valid serialization of a node identifier.', 1549227649);
        }

        return new NodeIdentifier(
            new NodeAggregateIdentifier($jsonArray['nodeAggregateIdentifier']),
            new ContentStreamIdentifier($jsonArray['contentStreamIdentifier']),
            DimensionSpacePoint::fromJsonString($jsonArray['originDimensionSpacePoint'])
        );
    }

    public function getNodeAggregateIdentifier(): NodeAggregateIdentifier
    {
        return $this->nodeAggregateIdentifier;
    }

    public function getContentStreamIdentifier(): ContentStreamIdentifier
    {
        return $this->contentStreamIdentifier;
    }

    public function getOriginDimensionSpacePoint(): DimensionSpacePoint
    {
        return $this->originDimensionSpacePoint;
    }

    public function getCacheEntryIdentifier(): string
    {
        return sha1(json_encode($this));
    }

    public function jsonSerialize(): array
    {
        return [
            'nodeAggregateIdentifier' => $this->nodeAggregateIdentifier,
            'contentStreamIdentifier' => $this->contentStreamIdentifier,
            'originDimensionSpacePoint' => $this->originDimensionSpacePoint
        ];
    }
}
