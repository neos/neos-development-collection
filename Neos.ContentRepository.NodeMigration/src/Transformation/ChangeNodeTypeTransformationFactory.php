<?php

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

declare(strict_types=1);

namespace Neos\ContentRepository\NodeMigration\Transformation;

use Neos\ContentRepository\CommandHandler\CommandResult;
use Neos\ContentRepository\ContentRepository;
use Neos\ContentRepository\Feature\NodeTypeChange\Command\ChangeNodeAggregateType;
use Neos\ContentRepository\Feature\NodeTypeChange\Command\NodeAggregateTypeChangeChildConstraintConflictResolutionStrategy;
use Neos\ContentRepository\Projection\ContentGraph\NodeAggregate;
use Neos\ContentRepository\SharedModel\NodeType\NodeTypeName;
use Neos\ContentRepository\SharedModel\User\UserIdentifier;
use Neos\ContentRepository\SharedModel\Workspace\ContentStreamIdentifier;

/** @codingStandardsIgnoreStart */
/** @codingStandardsIgnoreEnd */

/**
 * Change the node type.
 */
class ChangeNodeTypeTransformationFactory implements TransformationFactoryInterface
{
    /**
     * @param array<string,mixed> $settings
     */
    public function build(
        array $settings,
        ContentRepository $contentRepository
    ): GlobalTransformationInterface|NodeAggregateBasedTransformationInterface|NodeBasedTransformationInterface {
        // by default, we won't delete anything.
        $nodeAggregateTypeChangeChildConstraintConflictResolutionStrategy
            = NodeAggregateTypeChangeChildConstraintConflictResolutionStrategy::STRATEGY_HAPPY_PATH;
        if (isset($settings['forceDeleteNonMatchingChildren']) && $settings['forceDeleteNonMatchingChildren']) {
            $nodeAggregateTypeChangeChildConstraintConflictResolutionStrategy
                = NodeAggregateTypeChangeChildConstraintConflictResolutionStrategy::STRATEGY_DELETE;
        }

        return new class (
            $settings['newType'],
            $nodeAggregateTypeChangeChildConstraintConflictResolutionStrategy,
            $contentRepository
        ) implements NodeAggregateBasedTransformationInterface {
            public function __construct(
                /**
                 * The new Node Type to use as a string
                 */
                private readonly string $newType,
                private readonly NodeAggregateTypeChangeChildConstraintConflictResolutionStrategy $strategy,
                private readonly ContentRepository $contentRepository
            ) {
            }

            public function execute(
                NodeAggregate $nodeAggregate,
                ContentStreamIdentifier $contentStreamForWriting
            ): CommandResult {
                return $this->contentRepository->handle(new ChangeNodeAggregateType(
                    $contentStreamForWriting,
                    $nodeAggregate->nodeAggregateIdentifier,
                    NodeTypeName::fromString($this->newType),
                    $this->strategy,
                    UserIdentifier::forSystemUser()
                ));
            }
        };
    }
}
