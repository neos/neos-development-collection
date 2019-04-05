<?php
declare(strict_types=1);
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

use Neos\ContentRepository\DimensionSpace\DimensionSpace\DimensionSpacePointSet;
use Neos\ContentRepository\Domain\ContentStream\ContentStreamIdentifier;
use Neos\ContentRepository\Domain\NodeAggregate\NodeAggregateIdentifier;
use Neos\EventSourcedContentRepository\Domain\Context\Node\CopyableAcrossContentStreamsInterface;
use Neos\EventSourcedContentRepository\Domain\Context\Node\MatchableWithNodeAddressInterface;
use Neos\EventSourcedNeosAdjustments\Domain\Context\Content\NodeAddress;

final class ShowNode implements \JsonSerializable, CopyableAcrossContentStreamsInterface, MatchableWithNodeAddressInterface
{

    /**
     * @var ContentStreamIdentifier
     */
    private $contentStreamIdentifier;

    /**
     * Node Aggregate identifier which the user intended to show
     *
     * @var NodeAggregateIdentifier
     */
    private $nodeAggregateIdentifier;

    /**
     * @var DimensionSpacePointSet
     */
    private $affectedDimensionSpacePoints;

    /**
     * ShowNode constructor.
     * @param ContentStreamIdentifier $contentStreamIdentifier
     * @param NodeAggregateIdentifier $nodeAggregateIdentifier
     * @param DimensionSpacePointSet $affectedDimensionSpacePoints
     */
    public function __construct(ContentStreamIdentifier $contentStreamIdentifier, NodeAggregateIdentifier $nodeAggregateIdentifier, DimensionSpacePointSet $affectedDimensionSpacePoints)
    {
        $this->contentStreamIdentifier = $contentStreamIdentifier;
        $this->nodeAggregateIdentifier = $nodeAggregateIdentifier;
        $this->affectedDimensionSpacePoints = $affectedDimensionSpacePoints;
    }

    public static function fromArray(array $array): self
    {
        return new static(
            ContentStreamIdentifier::fromString($array['contentStreamIdentifier']),
            NodeAggregateIdentifier::fromString($array['nodeAggregateIdentifier']),
            new DimensionSpacePointSet($array['affectedDimensionSpacePoints'])
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
     * @return DimensionSpacePointSet
     */
    public function getAffectedDimensionSpacePoints(): DimensionSpacePointSet
    {
        return $this->affectedDimensionSpacePoints;
    }

    public function jsonSerialize(): array
    {
        return [
            'contentStreamIdentifier' => $this->contentStreamIdentifier,
            'nodeAggregateIdentifier' => $this->nodeAggregateIdentifier,
            'affectedDimensionSpacePoints' => $this->affectedDimensionSpacePoints,
        ];
    }

    public function createCopyForContentStream(ContentStreamIdentifier $targetContentStream): self
    {
        return new ShowNode(
            $targetContentStream,
            $this->nodeAggregateIdentifier,
            $this->affectedDimensionSpacePoints
        );
    }

    public function matchesNodeAddress(NodeAddress $nodeAddress): bool
    {
        return (
            (string)$this->getContentStreamIdentifier() === (string)$nodeAddress->getContentStreamIdentifier()
            && $this->getAffectedDimensionSpacePoints()->contains($nodeAddress->getDimensionSpacePoint())
            && $this->getNodeAggregateIdentifier()->equals($nodeAddress->getNodeAggregateIdentifier())
        );
    }
}
