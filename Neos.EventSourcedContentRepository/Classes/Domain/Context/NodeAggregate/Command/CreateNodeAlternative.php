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

use Neos\ContentRepository\Domain\ValueObject\ContentStreamIdentifier;
use Neos\ContentRepository\DimensionSpace\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\Domain\ValueObject\NodeAggregateIdentifier;

/**
 * Create an alternative variant of a node in a content stream.
 *
 * Alternatives are those variants that are neither generalizations nor specializations
 *
 * Copy a node identified by node aggregate identifier and source dimension space point to a specialized dimension space point in a content stream
 * respecting possible specialization mechanisms.
 */
final class CreateNodeAlternative implements \JsonSerializable
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
     * @var DimensionSpacePoint
     */
    private $sourceDimensionSpacePoint;

    /**
     * @var DimensionSpacePoint
     */
    private $targetDimensionSpacePoint;

    /**
     * CreateNodeAlternative constructor.
     * @param ContentStreamIdentifier $contentStreamIdentifier
     * @param NodeAggregateIdentifier $nodeAggregateIdentifier
     * @param DimensionSpacePoint $sourceDimensionSpacePoint
     * @param DimensionSpacePoint $targetDimensionSpacePoint
     */
    public function __construct(
        ContentStreamIdentifier $contentStreamIdentifier,
        NodeAggregateIdentifier $nodeAggregateIdentifier,
        DimensionSpacePoint $sourceDimensionSpacePoint,
        DimensionSpacePoint $targetDimensionSpacePoint
    ) {
        $this->contentStreamIdentifier = $contentStreamIdentifier;
        $this->nodeAggregateIdentifier = $nodeAggregateIdentifier;
        $this->sourceDimensionSpacePoint = $sourceDimensionSpacePoint;
        $this->targetDimensionSpacePoint = $targetDimensionSpacePoint;
    }

    public static function fromArray(array $array): self
    {
        return new static(
            ContentStreamIdentifier::fromString($array['contentStreamIdentifier']),
            NodeAggregateIdentifier::fromString($array['nodeAggregateIdentifier']),
            new DimensionSpacePoint($array['sourceDimensionSpacePoint']),
            new DimensionSpacePoint($array['targetDimensionSpacePoint'])
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
     * @return DimensionSpacePoint
     */
    public function getSourceDimensionSpacePoint(): DimensionSpacePoint
    {
        return $this->sourceDimensionSpacePoint;
    }

    /**
     * @return DimensionSpacePoint
     */
    public function getTargetDimensionSpacePoint(): DimensionSpacePoint
    {
        return $this->targetDimensionSpacePoint;
    }

    public function jsonSerialize(): array
    {
        return [
            'contentStreamIdentifier' => $this->contentStreamIdentifier,
            'nodeAggregateIdentifier' => $this->nodeAggregateIdentifier,
            'sourceDimensionSpacePoint' => $this->sourceDimensionSpacePoint,
            'targetDimensionSpacePoint' => $this->targetDimensionSpacePoint
        ];
    }
}