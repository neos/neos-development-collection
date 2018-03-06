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

final class ChangeNodeAggregateType
{
    /**
     * @var Domain\ValueObject\ContentStreamIdentifier
     */
    protected $contentStreamIdentifier;

    /**
     * @var Domain\ValueObject\NodeAggregateIdentifier
     */
    protected $nodeAggregateIdentifier;

    /**
     * @var Domain\ValueObject\NodeTypeName
     */
    protected $newNodeTypeName;

    /**
     * @var Domain\Context\NodeAggregate\NodeAggregateTypeChangeChildConstraintConflictResolutionStrategy
     */
    protected $strategy;


    /**
     * @param Domain\ValueObject\ContentStreamIdentifier $contentStreamIdentifier
     * @param Domain\ValueObject\NodeAggregateIdentifier $nodeAggregateIdentifier
     * @param Domain\ValueObject\NodeTypeName $newNodeTypeName
     * @param Domain\Context\NodeAggregate\NodeAggregateTypeChangeChildConstraintConflictResolutionStrategy|null $strategy
     */
    public function __construct(
        Domain\ValueObject\ContentStreamIdentifier $contentStreamIdentifier,
        Domain\ValueObject\NodeAggregateIdentifier $nodeAggregateIdentifier,
        Domain\ValueObject\NodeTypeName $newNodeTypeName,
        ?Domain\Context\NodeAggregate\NodeAggregateTypeChangeChildConstraintConflictResolutionStrategy $strategy
    ) {
        $this->contentStreamIdentifier = $contentStreamIdentifier;
        $this->nodeAggregateIdentifier = $nodeAggregateIdentifier;
        $this->newNodeTypeName = $newNodeTypeName;
        $this->strategy = $strategy;
    }

    /**
     * @return Domain\ValueObject\ContentStreamIdentifier
     */
    public function getContentStreamIdentifier(): Domain\ValueObject\ContentStreamIdentifier
    {
        return $this->contentStreamIdentifier;
    }

    /**
     * @return Domain\ValueObject\NodeAggregateIdentifier
     */
    public function getNodeAggregateIdentifier(): Domain\ValueObject\NodeAggregateIdentifier
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
     * @return Domain\Context\NodeAggregate\NodeAggregateTypeChangeChildConstraintConflictResolutionStrategy|null
     */
    public function getStrategy(): ?Domain\Context\NodeAggregate\NodeAggregateTypeChangeChildConstraintConflictResolutionStrategy
    {
        return $this->strategy;
    }
}
