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

namespace Neos\ContentRepository\Core\Feature;

use Neos\ContentRepository\Core\CommandHandler\CommandHandlerInterface;
use Neos\ContentRepository\Core\CommandHandler\CommandInterface;
use Neos\ContentRepository\Core\CommandHandlingDependencies;
use Neos\ContentRepository\Core\ContentRepository;
use Neos\ContentRepository\Core\DimensionSpace;
use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePointSet;
use Neos\ContentRepository\Core\EventStore\EventsToPublish;
use Neos\ContentRepository\Core\Feature\Common\ConstraintChecks;
use Neos\ContentRepository\Core\Feature\Common\TetheredNodeInternals;
use Neos\ContentRepository\Core\Feature\NodeCreation\Command\CreateNodeAggregateWithNode;
use Neos\ContentRepository\Core\Feature\NodeCreation\Command\CreateNodeAggregateWithNodeAndSerializedProperties;
use Neos\ContentRepository\Core\Feature\NodeCreation\NodeCreation;
use Neos\ContentRepository\Core\Feature\NodeDisabling\Command\DisableNodeAggregate;
use Neos\ContentRepository\Core\Feature\NodeDisabling\Command\EnableNodeAggregate;
use Neos\ContentRepository\Core\Feature\NodeDisabling\NodeDisabling;
use Neos\ContentRepository\Core\Feature\NodeModification\Command\SetNodeProperties;
use Neos\ContentRepository\Core\Feature\NodeModification\Command\SetSerializedNodeProperties;
use Neos\ContentRepository\Core\Feature\NodeModification\NodeModification;
use Neos\ContentRepository\Core\Feature\NodeMove\Command\MoveNodeAggregate;
use Neos\ContentRepository\Core\Feature\NodeMove\NodeMove;
use Neos\ContentRepository\Core\Feature\NodeReferencing\Command\SetNodeReferences;
use Neos\ContentRepository\Core\Feature\NodeReferencing\Command\SetSerializedNodeReferences;
use Neos\ContentRepository\Core\Feature\NodeReferencing\NodeReferencing;
use Neos\ContentRepository\Core\Feature\NodeRemoval\Command\RemoveNodeAggregate;
use Neos\ContentRepository\Core\Feature\NodeRemoval\NodeRemoval;
use Neos\ContentRepository\Core\Feature\NodeRenaming\Command\ChangeNodeAggregateName;
use Neos\ContentRepository\Core\Feature\NodeRenaming\NodeRenaming;
use Neos\ContentRepository\Core\Feature\NodeTypeChange\Command\ChangeNodeAggregateType;
use Neos\ContentRepository\Core\Feature\NodeTypeChange\NodeTypeChange;
use Neos\ContentRepository\Core\Feature\NodeVariation\Command\CreateNodeVariant;
use Neos\ContentRepository\Core\Feature\NodeVariation\NodeVariation;
use Neos\ContentRepository\Core\Feature\RootNodeCreation\Command\CreateRootNodeAggregateWithNode;
use Neos\ContentRepository\Core\Feature\RootNodeCreation\Command\UpdateRootNodeAggregateDimensions;
use Neos\ContentRepository\Core\Feature\RootNodeCreation\RootNodeHandling;
use Neos\ContentRepository\Core\Feature\SubtreeTagging\Command\TagSubtree;
use Neos\ContentRepository\Core\Feature\SubtreeTagging\Command\UntagSubtree;
use Neos\ContentRepository\Core\Feature\SubtreeTagging\SubtreeTagging;
use Neos\ContentRepository\Core\Infrastructure\Property\PropertyConverter;
use Neos\ContentRepository\Core\NodeType\NodeTypeManager;

/**
 * @internal from userland, you'll use ContentRepository::handle to dispatch commands
 */
final class NodeAggregateCommandHandler implements CommandHandlerInterface
{
    use ConstraintChecks;
    use RootNodeHandling;
    use NodeCreation;
    use NodeDisabling;
    use SubtreeTagging;
    use NodeModification;
    use NodeMove;
    use NodeReferencing;
    use NodeRemoval;
    use NodeRenaming;
    use NodeTypeChange;
    use NodeVariation;
    use TetheredNodeInternals;

    /**
     * can be disabled in {@see NodeAggregateCommandHandler::withoutAncestorNodeTypeConstraintChecks()}
     */
    private bool $ancestorNodeTypeConstraintChecksEnabled = true;

    public function __construct(
        private readonly NodeTypeManager $nodeTypeManager,
        private readonly DimensionSpace\ContentDimensionZookeeper $contentDimensionZookeeper,
        private readonly DimensionSpace\InterDimensionalVariationGraph $interDimensionalVariationGraph,
        private readonly PropertyConverter $propertyConverter,
    ) {
    }

    public function canHandle(CommandInterface $command): bool
    {
        return method_exists($this, 'handle' . (new \ReflectionClass($command))->getShortName());
    }

    public function handle(CommandInterface $command, CommandHandlingDependencies $commandHandlingDependencies): EventsToPublish
    {
        /** @phpstan-ignore-next-line */
        return match ($command::class) {
            SetNodeProperties::class => $this->handleSetNodeProperties($command, $commandHandlingDependencies),
            SetSerializedNodeProperties::class
            => $this->handleSetSerializedNodeProperties($command, $commandHandlingDependencies),
            SetNodeReferences::class => $this->handleSetNodeReferences($command, $commandHandlingDependencies),
            SetSerializedNodeReferences::class
            => $this->handleSetSerializedNodeReferences($command, $commandHandlingDependencies),
            ChangeNodeAggregateType::class => $this->handleChangeNodeAggregateType($command, $commandHandlingDependencies),
            RemoveNodeAggregate::class => $this->handleRemoveNodeAggregate($command, $commandHandlingDependencies),
            CreateNodeAggregateWithNode::class
            => $this->handleCreateNodeAggregateWithNode($command, $commandHandlingDependencies),
            CreateNodeAggregateWithNodeAndSerializedProperties::class
            => $this->handleCreateNodeAggregateWithNodeAndSerializedProperties($command, $commandHandlingDependencies),
            MoveNodeAggregate::class => $this->handleMoveNodeAggregate($command, $commandHandlingDependencies),
            CreateNodeVariant::class => $this->handleCreateNodeVariant($command, $commandHandlingDependencies),
            CreateRootNodeAggregateWithNode::class
            => $this->handleCreateRootNodeAggregateWithNode($command, $commandHandlingDependencies),
            UpdateRootNodeAggregateDimensions::class
            => $this->handleUpdateRootNodeAggregateDimensions($command, $commandHandlingDependencies),
            DisableNodeAggregate::class => $this->handleDisableNodeAggregate($command, $commandHandlingDependencies),
            EnableNodeAggregate::class => $this->handleEnableNodeAggregate($command, $commandHandlingDependencies),
            TagSubtree::class => $this->handleTagSubtree($command, $commandHandlingDependencies),
            UntagSubtree::class => $this->handleUntagSubtree($command, $commandHandlingDependencies),
            ChangeNodeAggregateName::class => $this->handleChangeNodeAggregateName($command, $commandHandlingDependencies),
        };
    }

    protected function getNodeTypeManager(): NodeTypeManager
    {
        return $this->nodeTypeManager;
    }

    protected function getAllowedDimensionSubspace(): DimensionSpacePointSet
    {
        return $this->contentDimensionZookeeper->getAllowedDimensionSubspace();
    }

    protected function getInterDimensionalVariationGraph(): DimensionSpace\InterDimensionalVariationGraph
    {
        return $this->interDimensionalVariationGraph;
    }

    protected function areAncestorNodeTypeConstraintChecksEnabled(): bool
    {
        return $this->ancestorNodeTypeConstraintChecksEnabled;
    }

    public function getPropertyConverter(): PropertyConverter
    {
        return $this->propertyConverter;
    }

    /**
     * Use this closure to run code with the Ancestor Node Type Checks disabled; e.g.
     * during imports.
     *
     * You can disable this because many old sites have this constraint violated more or less;
     * and it's easy to fix later on; as it does not touch the fundamental integrity of the CR.
     *
     * @param \Closure $callback
     */
    public function withoutAncestorNodeTypeConstraintChecks(\Closure $callback): void
    {
        $previousAncestorNodeTypeConstraintChecksEnabled = $this->ancestorNodeTypeConstraintChecksEnabled;
        $this->ancestorNodeTypeConstraintChecksEnabled = false;

        $callback();

        $this->ancestorNodeTypeConstraintChecksEnabled = $previousAncestorNodeTypeConstraintChecksEnabled;
    }
}
