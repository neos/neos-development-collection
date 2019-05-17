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
use Neos\ContentRepository\Domain\NodeType\NodeTypeName;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\NodeAggregateTypeChangeChildConstraintConflictResolutionStrategy;

final class ChangeNodeAggregateType
{
    /**
     * @var ContentStreamIdentifier
     */
    protected $contentStreamIdentifier;

    /**
     * @var NodeAggregateIdentifier
     */
    protected $nodeAggregateIdentifier;

    /**
     * @var NodeTypeName
     */
    protected $newNodeTypeName;

    /**
     * @var NodeAggregateTypeChangeChildConstraintConflictResolutionStrategy
     */
    protected $strategy;

    public function __construct(ContentStreamIdentifier $contentStreamIdentifier, NodeAggregateIdentifier $nodeAggregateIdentifier, NodeTypeName $newNodeTypeName, ?NodeAggregateTypeChangeChildConstraintConflictResolutionStrategy $strategy)
    {
        $this->contentStreamIdentifier = $contentStreamIdentifier;
        $this->nodeAggregateIdentifier = $nodeAggregateIdentifier;
        $this->newNodeTypeName = $newNodeTypeName;
        $this->strategy = $strategy;
    }

    /**
     * @param array $array
     * @return ChangeNodeAggregateType
     * @throws \Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\Exception\NodeAggregateTypeChangeChildConstraintConflictResolutionStrategyIsUnknown
     */
    public static function fromArray(array $array): self
    {
        return new static(
            ContentStreamIdentifier::fromString($array['contentStreamIdentifier']),
            NodeAggregateIdentifier::fromString($array['nodeAggregateIdentifier']),
            NodeTypeName::fromString($array['newNodeTypeName']),
            isset($array['strategy'])
                ? NodeAggregateTypeChangeChildConstraintConflictResolutionStrategy::fromString($array['strategy'])
                : null

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
     * @return NodeTypeName
     */
    public function getNewNodeTypeName(): NodeTypeName
    {
        return $this->newNodeTypeName;
    }

    /**
     * @return NodeAggregateTypeChangeChildConstraintConflictResolutionStrategy|null
     */
    public function getStrategy(): ?NodeAggregateTypeChangeChildConstraintConflictResolutionStrategy
    {
        return $this->strategy;
    }
}
