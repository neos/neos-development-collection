<?php
namespace Neos\ContentRepository\Domain\Context\NodeAggregate\Command;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\ContentRepository\Domain;
use Neos\ContentRepository\Domain\Context\ContentStream;
use Neos\ContentRepository\Domain\Context\NodeAggregate;

/**
 * Create a generalization of a node in a content stream
 *
 * Copy a node to a generalized dimension space point respecting further generalization mechanisms
 */
final class CreateNodeGeneralization
{
    /**
     * @var ContentStream\ContentStreamIdentifier
     */
    protected $contentStreamIdentifier;

    /**
     * @var NodeAggregate\NodeAggregateIdentifier
     */
    protected $nodeAggregateIdentifier;

    /**
     * @var Domain\ValueObject\DimensionSpacePoint
     */
    protected $sourceDimensionSpacePoint;

    /**
     * @var Domain\ValueObject\DimensionSpacePoint
     */
    protected $targetDimensionSpacePoint;

    /**
     * @var Domain\ValueObject\NodeIdentifier
     */
    protected $generalizationIdentifier;


    /**
     * @param ContentStream\ContentStreamIdentifier $contentStreamIdentifier
     * @param NodeAggregate\NodeAggregateIdentifier $nodeAggregateIdentifier
     * @param Domain\ValueObject\DimensionSpacePoint $sourceDimensionSpacePoint
     * @param Domain\ValueObject\DimensionSpacePoint $targetDimensionSpacePoint
     * @param Domain\ValueObject\NodeIdentifier $generalizationIdentifier
     */
    public function __construct(
        ContentStream\ContentStreamIdentifier $contentStreamIdentifier,
        NodeAggregate\NodeAggregateIdentifier $nodeAggregateIdentifier,
        Domain\ValueObject\DimensionSpacePoint $sourceDimensionSpacePoint,
        Domain\ValueObject\DimensionSpacePoint $targetDimensionSpacePoint,
        Domain\ValueObject\NodeIdentifier $generalizationIdentifier
    ) {
        $this->contentStreamIdentifier = $contentStreamIdentifier;
        $this->nodeAggregateIdentifier = $nodeAggregateIdentifier;
        $this->sourceDimensionSpacePoint = $sourceDimensionSpacePoint;
        $this->targetDimensionSpacePoint = $targetDimensionSpacePoint;
        $this->generalizationIdentifier = $generalizationIdentifier;
    }

    /**
     * @return ContentStream\ContentStreamIdentifier
     */
    public function getContentStreamIdentifier(): ContentStream\ContentStreamIdentifier
    {
        return $this->contentStreamIdentifier;
    }

    /**
     * @return NodeAggregate\NodeAggregateIdentifier
     */
    public function getNodeAggregateIdentifier(): NodeAggregate\NodeAggregateIdentifier
    {
        return $this->nodeAggregateIdentifier;
    }

    /**
     * @return Domain\ValueObject\DimensionSpacePoint
     */
    public function getSourceDimensionSpacePoint(): Domain\ValueObject\DimensionSpacePoint
    {
        return $this->sourceDimensionSpacePoint;
    }

    /**
     * @return Domain\ValueObject\DimensionSpacePoint
     */
    public function getTargetDimensionSpacePoint(): Domain\ValueObject\DimensionSpacePoint
    {
        return $this->targetDimensionSpacePoint;
    }

    /**
     * @return Domain\ValueObject\NodeIdentifier
     */
    public function getGeneralizationIdentifier(): Domain\ValueObject\NodeIdentifier
    {
        return $this->generalizationIdentifier;
    }
}
