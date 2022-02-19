<?php
declare(strict_types=1);
namespace Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\Event;

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
use Neos\ContentRepository\Domain\NodeType\NodeTypeName;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\NodeAggregateClassification;
use Neos\EventSourcedContentRepository\Domain\ValueObject\UserIdentifier;
use Neos\EventSourcing\Event\DomainEventInterface;
use Neos\Flow\Annotations as Flow;

/**
 * A root node aggregate and its initial node were created
 */
#[Flow\Proxy(false)]
final class RootNodeAggregateWithNodeWasCreated implements DomainEventInterface,
    PublishableToOtherContentStreamsInterface, EmbedsContentStreamAndNodeAggregateIdentifier
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
     * @var NodeTypeName
     */
    protected $nodeTypeName;

    /**
     * Root nodes by definition cover *all* dimension space points; so we need to include the full list here.
     *
     * @var DimensionSpacePointSet
     */
    private $coveredDimensionSpacePoints;

    /**
     * @var NodeAggregateClassification
     */
    private $nodeAggregateClassification;

    /**
     * @var UserIdentifier
     */
    private $initiatingUserIdentifier;

    public function __construct(
        ContentStreamIdentifier $contentStreamIdentifier,
        NodeAggregateIdentifier $nodeAggregateIdentifier,
        NodeTypeName $nodeTypeName,
        DimensionSpacePointSet $coveredDimensionSpacePoints,
        NodeAggregateClassification $nodeAggregateClassification,
        UserIdentifier $initiatingUserIdentifier
    ) {
        $this->contentStreamIdentifier = $contentStreamIdentifier;
        $this->nodeAggregateIdentifier = $nodeAggregateIdentifier;
        $this->nodeTypeName = $nodeTypeName;
        $this->coveredDimensionSpacePoints = $coveredDimensionSpacePoints;
        $this->nodeAggregateClassification = $nodeAggregateClassification;
        $this->initiatingUserIdentifier = $initiatingUserIdentifier;
    }

    public function getContentStreamIdentifier(): ContentStreamIdentifier
    {
        return $this->contentStreamIdentifier;
    }

    public function getNodeAggregateIdentifier(): NodeAggregateIdentifier
    {
        return $this->nodeAggregateIdentifier;
    }

    public function getNodeTypeName(): NodeTypeName
    {
        return $this->nodeTypeName;
    }

    public function getCoveredDimensionSpacePoints(): DimensionSpacePointSet
    {
        return $this->coveredDimensionSpacePoints;
    }

    public function getNodeAggregateClassification(): NodeAggregateClassification
    {
        return $this->nodeAggregateClassification;
    }

    public function getInitiatingUserIdentifier(): UserIdentifier
    {
        return $this->initiatingUserIdentifier;
    }

    public function createCopyForContentStream(ContentStreamIdentifier $targetContentStreamIdentifier): self
    {
        return new self(
            $targetContentStreamIdentifier,
            $this->nodeAggregateIdentifier,
            $this->nodeTypeName,
            $this->coveredDimensionSpacePoints,
            $this->nodeAggregateClassification,
            $this->initiatingUserIdentifier
        );
    }
}
