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

namespace Neos\ContentRepository\Feature;

use Neos\ContentRepository\CommandHandler\CommandHandlerInterface;
use Neos\ContentRepository\CommandHandler\CommandInterface;
use Neos\ContentRepository\ContentRepository;
use Neos\ContentRepository\DimensionSpace\DimensionSpace\DimensionSpacePointSet;
use Neos\ContentRepository\EventStore\EventsToPublish;
use Neos\ContentRepository\Feature\Common\Exception\NodeConstraintException;
use Neos\ContentRepository\Feature\Common\Exception\NodeTypeNotFoundException;
use Neos\ContentRepository\Feature\NodeCreation\Command\CreateNodeAggregateWithNode;
use Neos\ContentRepository\Feature\NodeCreation\Command\CreateNodeAggregateWithNodeAndSerializedProperties;
use Neos\ContentRepository\Feature\NodeDisabling\Command\DisableNodeAggregate;
use Neos\ContentRepository\Feature\NodeDisabling\Command\EnableNodeAggregate;
use Neos\ContentRepository\Feature\NodeModification\Command\SetNodeProperties;
use Neos\ContentRepository\Feature\NodeModification\Command\SetSerializedNodeProperties;
use Neos\ContentRepository\Feature\NodeMove\Command\MoveNodeAggregate;
use Neos\ContentRepository\Feature\NodeReferencing\Command\SetNodeReferences;
use Neos\ContentRepository\Feature\NodeReferencing\Command\SetSerializedNodeReferences;
use Neos\ContentRepository\Feature\NodeRemoval\Command\RemoveNodeAggregate;
use Neos\ContentRepository\Feature\NodeRenaming\Command\ChangeNodeAggregateName;
use Neos\ContentRepository\Feature\NodeVariation\Command\CreateNodeVariant;
use Neos\ContentRepository\Feature\RootNodeCreation\Command\CreateRootNodeAggregateWithNode;
use Neos\ContentRepository\Feature\RootNodeCreation\RootNodeCreation;
use Neos\ContentRepository\DimensionSpace\DimensionSpace;
use Neos\ContentRepository\Feature\NodeTypeChange\Command\ChangeNodeAggregateType;
use Neos\ContentRepository\Feature\Common\ConstraintChecks;
use Neos\ContentRepository\Feature\NodeCreation\NodeCreation;
use Neos\ContentRepository\Feature\NodeDisabling\NodeDisabling;
use Neos\ContentRepository\Feature\NodeModification\NodeModification;
use Neos\ContentRepository\Feature\NodeMove\NodeMove;
use Neos\ContentRepository\Feature\NodeReferencing\NodeReferencing;
use Neos\ContentRepository\Feature\NodeRemoval\NodeRemoval;
use Neos\ContentRepository\Feature\NodeRenaming\NodeRenaming;
use Neos\ContentRepository\Feature\NodeTypeChange\NodeTypeChange;
use Neos\ContentRepository\Feature\NodeVariation\NodeVariation;
use Neos\ContentRepository\Feature\Common\TetheredNodeInternals;
use Neos\ContentRepository\SharedModel\NodeType\NodeTypeManager;
use Neos\ContentRepository\Infrastructure\Property\PropertyConverter;

/**
 * @internal from userland, you'll use ContentRepository::handle to dispatch commands
 */
final class NodeAggregateCommandHandler implements CommandHandlerInterface
{
    use ConstraintChecks;
    use RootNodeCreation;
    use NodeCreation;
    use NodeDisabling;
    use NodeModification;
    use NodeMove;
    use NodeReferencing;
    use NodeRemoval;
    use NodeRenaming;
    use NodeTypeChange;
    use NodeVariation;
    use TetheredNodeInternals;

    /**
     * Used for constraint checks against the current outside configuration state of node types
     */
    private NodeTypeManager $nodeTypeManager;

    /**
     * Used for variation resolution from the current outside state of content dimensions
     */
    private DimensionSpace\InterDimensionalVariationGraph $interDimensionalVariationGraph;

    /**
     * Used for constraint checks against the current outside configuration state of content dimensions
     */
    private DimensionSpace\ContentDimensionZookeeper $contentDimensionZookeeper;

    protected PropertyConverter $propertyConverter;

    /**
     * can be disabled in {@see NodeAggregateCommandHandler::withoutAnchestorNodeTypeConstraintChecks()}
     */
    private bool $ancestorNodeTypeConstraintChecksEnabled = true;

    public function __construct(
        NodeTypeManager $nodeTypeManager,
        DimensionSpace\ContentDimensionZookeeper $contentDimensionZookeeper,
        DimensionSpace\InterDimensionalVariationGraph $interDimensionalVariationGraph,
        PropertyConverter $propertyConverter
    ) {
        $this->nodeTypeManager = $nodeTypeManager;
        $this->contentDimensionZookeeper = $contentDimensionZookeeper;
        $this->interDimensionalVariationGraph = $interDimensionalVariationGraph;
        $this->propertyConverter = $propertyConverter;
    }


    public function canHandle(CommandInterface $command): bool
    {
        return $command instanceof SetNodeProperties
            || $command instanceof SetSerializedNodeProperties
            || $command instanceof SetNodeReferences
            || $command instanceof SetSerializedNodeReferences
            || $command instanceof ChangeNodeAggregateType
            || $command instanceof RemoveNodeAggregate
            || $command instanceof CreateNodeAggregateWithNode
            || $command instanceof CreateNodeAggregateWithNodeAndSerializedProperties
            || $command instanceof MoveNodeAggregate
            || $command instanceof CreateNodeVariant
            || $command instanceof CreateRootNodeAggregateWithNode
            || $command instanceof DisableNodeAggregate
            || $command instanceof EnableNodeAggregate
            || $command instanceof ChangeNodeAggregateName;
    }

    /** @codingStandardsIgnoreStart */
    public function handle(CommandInterface $command, ContentRepository $contentRepository): EventsToPublish
    {
        // @phpstan-ignore-next-line
        return match ($command::class) {
            SetNodeProperties::class => $this->handleSetNodeProperties($command, $contentRepository),
            SetSerializedNodeProperties::class => $this->handleSetSerializedNodeProperties($command, $contentRepository),
            SetNodeReferences::class => $this->handleSetNodeReferences($command, $contentRepository),
            SetSerializedNodeReferences::class => $this->handleSetSerializedNodeReferences($command, $contentRepository),
            ChangeNodeAggregateType::class => $this->handleChangeNodeAggregateType($command, $contentRepository),
            RemoveNodeAggregate::class => $this->handleRemoveNodeAggregate($command, $contentRepository),
            CreateNodeAggregateWithNode::class => $this->handleCreateNodeAggregateWithNode($command, $contentRepository),
            CreateNodeAggregateWithNodeAndSerializedProperties::class => $this->handleCreateNodeAggregateWithNodeAndSerializedProperties($command, $contentRepository),
            MoveNodeAggregate::class => $this->handleMoveNodeAggregate($command, $contentRepository),
            CreateNodeVariant::class => $this->handleCreateNodeVariant($command, $contentRepository),
            CreateRootNodeAggregateWithNode::class => $this->handleCreateRootNodeAggregateWithNode($command, $contentRepository),
            DisableNodeAggregate::class => $this->handleDisableNodeAggregate($command, $contentRepository),
            EnableNodeAggregate::class => $this->handleEnableNodeAggregate($command, $contentRepository),
            ChangeNodeAggregateName::class => $this->handleChangeNodeAggregateName($command),
        };
    }
    /** @codingStandardsIgnoreStop */

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

    /**
     * @todo perhaps reuse when ChangeNodeAggregateType is reimplemented
     */
    protected function checkConstraintsImposedByAncestors(ChangeNodeAggregateType $command, ContentRepository $contentRepository): void
    {
        $nodeAggregate = $this->requireProjectedNodeAggregate(
            $command->contentStreamIdentifier,
            $command->nodeAggregateIdentifier,
            $contentRepository
        );
        $newNodeType = $this->requireNodeType($command->newNodeTypeName);
        foreach (
            $contentRepository->getContentGraph()->findParentNodeAggregates(
                $command->contentStreamIdentifier,
                $command->nodeAggregateIdentifier
            ) as $parentAggregate
        ) {
            $parentsNodeType = $this->nodeTypeManager->getNodeType((string)$parentAggregate->getNodeTypeName());
            if (!$parentsNodeType->allowsChildNodeType($newNodeType)) {
                throw new NodeConstraintException(
                    'Node type ' . $command->newNodeTypeName
                        . ' is not allowed below nodes of type ' . $parentAggregate->getNodeTypeName()
                );
            }
            if (
                $nodeAggregate->getNodeName()
                && $parentsNodeType->hasAutoCreatedChildNode($nodeAggregate->getNodeName())
                && $parentsNodeType->getTypeOfAutoCreatedChildNode($nodeAggregate->getNodeName())?->getName()
                    !== (string)$command->newNodeTypeName
            ) {
                throw new NodeConstraintException(
                    'Cannot change type of auto created child node' . $nodeAggregate->getNodeName()
                        . ' to ' . $command->newNodeTypeName
                );
            }
            foreach (
                $contentRepository->getContentGraph()->findParentNodeAggregates(
                    $command->contentStreamIdentifier,
                    $parentAggregate->getIdentifier()
                ) as $grandParentAggregate
            ) {
                $grandParentsNodeType = $this->nodeTypeManager->getNodeType(
                    (string)$grandParentAggregate->getNodeTypeName()
                );
                if (
                    $parentAggregate->getNodeName()
                    && $grandParentsNodeType->hasAutoCreatedChildNode($parentAggregate->getNodeName())
                    && !$grandParentsNodeType->allowsGrandchildNodeType(
                        (string) $parentAggregate->getNodeName(),
                        $newNodeType
                    )
                ) {
                    throw new NodeConstraintException(
                        'Node type "' . $command->newNodeTypeName
                            . '" is not allowed below auto created child nodes "' . $parentAggregate->getNodeName()
                            . '" of nodes of type "' . $grandParentAggregate->getNodeTypeName() . '"',
                        1520011791
                    );
                }
            }
        }
    }
}
