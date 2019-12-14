<?php
declare(strict_types=1);
namespace Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\Command;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\ContentRepository\Domain\ContentStream\ContentStreamIdentifier;
use Neos\ContentRepository\Domain\NodeAggregate\NodeAggregateIdentifier;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\CopyableAcrossContentStreamsInterface;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\MatchableWithNodeAddressInterface;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\OriginDimensionSpacePoint;
use Neos\EventSourcedContentRepository\Domain\ValueObject\PropertyValues;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAddress\NodeAddress;

final class SetNodeProperties implements \JsonSerializable, CopyableAcrossContentStreamsInterface, MatchableWithNodeAddressInterface
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
     * @var OriginDimensionSpacePoint
     */
    private $originDimensionSpacePoint;

    /**
     * @var PropertyValues
     */
    private $propertyValues;

    public function __construct(
        ContentStreamIdentifier $contentStreamIdentifier,
        NodeAggregateIdentifier $nodeAggregateIdentifier,
        OriginDimensionSpacePoint $originDimensionSpacePoint,
        PropertyValues $propertyValues
    ) {
        $this->contentStreamIdentifier = $contentStreamIdentifier;
        $this->nodeAggregateIdentifier = $nodeAggregateIdentifier;
        $this->originDimensionSpacePoint = $originDimensionSpacePoint;
        $this->propertyValues = $propertyValues;
    }

    public static function fromArray(array $array): self
    {
        return new static(
            ContentStreamIdentifier::fromString($array['contentStreamIdentifier']),
            NodeAggregateIdentifier::fromString($array['nodeAggregateIdentifier']),
            new OriginDimensionSpacePoint($array['originDimensionSpacePoint']),
            PropertyValues::fromArray($array['propertyValues'])
        );
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
     * @return OriginDimensionSpacePoint
     */
    public function getOriginDimensionSpacePoint(): OriginDimensionSpacePoint
    {
        return $this->originDimensionSpacePoint;
    }

    /**
     * @return PropertyValues
     */
    public function getPropertyValues(): PropertyValues
    {
        return $this->propertyValues;
    }

    public function jsonSerialize(): array
    {
        return [
            'contentStreamIdentifier' => $this->contentStreamIdentifier,
            'nodeAggregateIdentifier' => $this->nodeAggregateIdentifier,
            'originDimensionSpacePoint' => $this->originDimensionSpacePoint,
            'propertyValues' => $this->propertyValues,
        ];
    }

    public function createCopyForContentStream(ContentStreamIdentifier $targetContentStreamIdentifier): self
    {
        return new SetNodeProperties(
            $targetContentStreamIdentifier,
            $this->nodeAggregateIdentifier,
            $this->originDimensionSpacePoint,
            $this->propertyValues
        );
    }

    public function matchesNodeAddress(NodeAddress $nodeAddress): bool
    {
        return (
            (string)$this->getContentStreamIdentifier() === (string)$nodeAddress->getContentStreamIdentifier()
            && $this->getOriginDimensionSpacePoint()->equals($nodeAddress->getDimensionSpacePoint())
            && $this->getNodeAggregateIdentifier()->equals($nodeAddress->getNodeAggregateIdentifier())
        );
    }
}
