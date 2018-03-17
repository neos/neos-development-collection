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
use Neos\ContentRepository\Domain\Context\NodeAggregate;
use Neos\ContentRepository\Domain\Context\ContentStream;

final class ChangeNodeAggregateType
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
     * @var Domain\ValueObject\NodeTypeName
     */
    protected $newNodeTypeName;

    /**
     * @var NodeAggregate\NodeAggregateTypeChangeChildConstraintConflictResolutionStrategy
     */
    protected $strategy;


    public function __construct(
        ContentStream\ContentStreamIdentifier $contentStreamIdentifier,
        NodeAggregate\NodeAggregateIdentifier $nodeAggregateIdentifier,
        Domain\ValueObject\NodeTypeName $newNodeTypeName,
        ?NodeAggregate\NodeAggregateTypeChangeChildConstraintConflictResolutionStrategy $strategy
    ) {
        $this->contentStreamIdentifier = $contentStreamIdentifier;
        $this->nodeAggregateIdentifier = $nodeAggregateIdentifier;
        $this->newNodeTypeName = $newNodeTypeName;
        $this->strategy = $strategy;
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
     * @return Domain\ValueObject\NodeTypeName
     */
    public function getNewNodeTypeName(): Domain\ValueObject\NodeTypeName
    {
        return $this->newNodeTypeName;
    }

    /**
     * @return NodeAggregate\NodeAggregateTypeChangeChildConstraintConflictResolutionStrategy|null
     */
    public function getStrategy(): ?NodeAggregate\NodeAggregateTypeChangeChildConstraintConflictResolutionStrategy
    {
        return $this->strategy;
    }
}
