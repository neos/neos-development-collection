<?php
declare(strict_types=1);

namespace Neos\ContentRepository\Feature\Migration\Transformation;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\ContentRepository\SharedModel\Workspace\ContentStreamIdentifier;
use Neos\ContentRepository\SharedModel\NodeType\NodeTypeName;
use Neos\ContentRepository\Feature\NodeTypeChange\Command\ChangeNodeAggregateType;
use Neos\ContentRepository\Feature\NodeAggregateCommandHandler;
/** @codingStandardsIgnoreStart */
use Neos\ContentRepository\Feature\NodeTypeChange\Command\NodeAggregateTypeChangeChildConstraintConflictResolutionStrategy;
/** @codingStandardsIgnoreEnd */
use Neos\ContentRepository\SharedModel\Node\ReadableNodeAggregateInterface;
use Neos\ContentRepository\Infrastructure\Projection\CommandResult;
use Neos\ContentRepository\SharedModel\User\UserIdentifier;
use Neos\ContentRepository\Feature\Migration\Transformation\NodeAggregateBasedTransformationInterface;

/**
 * Change the node type.
 */
class ChangeNodeType implements NodeAggregateBasedTransformationInterface
{
    protected NodeAggregateCommandHandler $nodeAggregateCommandHandler;

    /**
     * The new Node Type to use as a string
     */
    protected string $newType;

    private NodeAggregateTypeChangeChildConstraintConflictResolutionStrategy
        $nodeAggregateTypeChangeChildConstraintConflictResolutionStrategy;

    public function __construct(NodeAggregateCommandHandler $nodeAggregateCommandHandler)
    {
        $this->nodeAggregateCommandHandler = $nodeAggregateCommandHandler;
        // by default, we won't delete anything.
        $this->nodeAggregateTypeChangeChildConstraintConflictResolutionStrategy
            = NodeAggregateTypeChangeChildConstraintConflictResolutionStrategy::STRATEGY_HAPPY_PATH;
    }

    public function setNewType(string $newType): void
    {
        $this->newType = $newType;
    }

    public function setForceDeleteNonMatchingChildren(bool $forceDeleteNonMatchingChildren): void
    {
        $this->nodeAggregateTypeChangeChildConstraintConflictResolutionStrategy = $forceDeleteNonMatchingChildren
            ? NodeAggregateTypeChangeChildConstraintConflictResolutionStrategy::STRATEGY_DELETE
            : NodeAggregateTypeChangeChildConstraintConflictResolutionStrategy::STRATEGY_HAPPY_PATH;
    }

    public function execute(
        ReadableNodeAggregateInterface $nodeAggregate,
        ContentStreamIdentifier $contentStreamForWriting
    ): CommandResult {
        return $this->nodeAggregateCommandHandler->handleChangeNodeAggregateType(new ChangeNodeAggregateType(
            $contentStreamForWriting,
            $nodeAggregate->getIdentifier(),
            NodeTypeName::fromString($this->newType),
            $this->nodeAggregateTypeChangeChildConstraintConflictResolutionStrategy,
            UserIdentifier::forSystemUser()
        ));
    }
}
