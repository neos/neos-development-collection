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
use Neos\ContentRepository\Core\Infrastructure\Property\PropertyConverter;
use Neos\ContentRepository\Core\NodeType\NodeTypeManager;
use Neos\ContentRepository\Core\Projection\ContentGraph\NodeAggregate;
use Neos\ContentRepository\Core\SharedModel\Exception\NodeConstraintException;

/**
 * @internal from userland, you'll use ContentRepository::handle to dispatch commands
 */
final class NodeAggregateCommandHandler implements CommandHandlerInterface
{
    use ConstraintChecks;
    use RootNodeHandling;
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
        return method_exists($this, 'handle' . (new \ReflectionClass($command))->getShortName());
    }

    public function handle(CommandInterface $command, ContentRepository $contentRepository): EventsToPublish
    {
        /** @phpstan-ignore-next-line */
        return match ($command::class) {
            SetNodeProperties::class => $this->handleSetNodeProperties($command, $contentRepository),
            SetSerializedNodeProperties::class
                => $this->handleSetSerializedNodeProperties($command, $contentRepository),
            SetNodeReferences::class => $this->handleSetNodeReferences($command, $contentRepository),
            SetSerializedNodeReferences::class
                => $this->handleSetSerializedNodeReferences($command, $contentRepository),
            ChangeNodeAggregateType::class => $this->handleChangeNodeAggregateType($command, $contentRepository),
            RemoveNodeAggregate::class => $this->handleRemoveNodeAggregate($command, $contentRepository),
            CreateNodeAggregateWithNode::class
                => $this->handleCreateNodeAggregateWithNode($command, $contentRepository),
            CreateNodeAggregateWithNodeAndSerializedProperties::class
                => $this->handleCreateNodeAggregateWithNodeAndSerializedProperties($command, $contentRepository),
            MoveNodeAggregate::class => $this->handleMoveNodeAggregate($command, $contentRepository),
            CreateNodeVariant::class => $this->handleCreateNodeVariant($command, $contentRepository),
            CreateRootNodeAggregateWithNode::class
                => $this->handleCreateRootNodeAggregateWithNode($command, $contentRepository),
            UpdateRootNodeAggregateDimensions::class
                => $this->handleUpdateRootNodeAggregateDimensions($command, $contentRepository),
            DisableNodeAggregate::class => $this->handleDisableNodeAggregate($command, $contentRepository),
            EnableNodeAggregate::class => $this->handleEnableNodeAggregate($command, $contentRepository),
            ChangeNodeAggregateName::class => $this->handleChangeNodeAggregateName($command, $contentRepository),
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

    /**
     * @todo perhaps reuse when ChangeNodeAggregateType is reimplemented
     */
    protected function checkConstraintsImposedByAncestors(
        ChangeNodeAggregateType $command,
        ContentRepository $contentRepository
    ): void {
        $nodeAggregate = $this->requireProjectedNodeAggregate(
            $command->contentStreamId,
            $command->nodeAggregateId,
            $contentRepository
        );
        $newNodeType = $this->requireNodeType($command->newNodeTypeName);
        foreach (
            $contentRepository->getContentGraph()->findParentNodeAggregates(
                $command->contentStreamId,
                $command->nodeAggregateId
            ) as $parentAggregate
        ) {
            /* @var $parentAggregate NodeAggregate */
            $parentsNodeType = $this->nodeTypeManager->getNodeType((string)$parentAggregate->nodeTypeName);
            if (!$parentsNodeType->allowsChildNodeType($newNodeType)) {
                throw new NodeConstraintException(
                    'Node type ' . $command->newNodeTypeName
                        . ' is not allowed below nodes of type ' . $parentAggregate->nodeTypeName
                );
            }
            if (
                $nodeAggregate->nodeName
                && $parentsNodeType->hasAutoCreatedChildNode($nodeAggregate->nodeName)
                && $parentsNodeType->getTypeOfAutoCreatedChildNode($nodeAggregate->nodeName)?->name
                    !== (string)$command->newNodeTypeName
            ) {
                throw new NodeConstraintException(
                    'Cannot change type of auto created child node' . $nodeAggregate->nodeName
                        . ' to ' . $command->newNodeTypeName
                );
            }
            foreach (
                $contentRepository->getContentGraph()->findParentNodeAggregates(
                    $command->contentStreamId,
                    $parentAggregate->nodeAggregateId
                ) as $grandParentAggregate
            ) {
                /* @var $grandParentAggregate NodeAggregate */
                $grandParentsNodeType = $this->nodeTypeManager->getNodeType(
                    (string)$grandParentAggregate->nodeTypeName
                );
                if (
                    $parentAggregate->nodeName
                    && $grandParentsNodeType->hasAutoCreatedChildNode($parentAggregate->nodeName)
                    && !$grandParentsNodeType->allowsGrandchildNodeType(
                        (string) $parentAggregate->nodeName,
                        $newNodeType
                    )
                ) {
                    throw new NodeConstraintException(
                        'Node type "' . $command->newNodeTypeName
                            . '" is not allowed below auto created child nodes "' . $parentAggregate->nodeName
                            . '" of nodes of type "' . $grandParentAggregate->nodeTypeName . '"',
                        1520011791
                    );
                }
            }
        }
    }
}
