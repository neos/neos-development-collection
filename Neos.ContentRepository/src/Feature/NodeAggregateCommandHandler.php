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

use Neos\ContentRepository\DimensionSpace\DimensionSpace\DimensionSpacePointSet;
use Neos\ContentRepository\Exception\NodeConstraintException;
use Neos\ContentRepository\Exception\NodeTypeNotFoundException;
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
use Neos\ContentRepository\Feature\Common\NodeAggregateEventPublisher;
use Neos\ContentRepository\Projection\Content\ContentGraphInterface;
use Neos\ContentRepository\SharedModel\NodeType\NodeTypeManager;
use Neos\ContentRepository\Infrastructure\Projection\RuntimeBlocker;
use Neos\ContentRepository\Infrastructure\Property\PropertyConverter;
use Neos\ContentRepository\Service\Infrastructure\ReadSideMemoryCacheManager;

final class NodeAggregateCommandHandler
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

    private ContentStreamRepository $contentStreamRepository;

    /**
     * Used for constraint checks against the current outside configuration state of node types
     */
    private NodeTypeManager $nodeTypeManager;

    /**
     * The graph projection used for soft constraint checks
     */
    private ContentGraphInterface $contentGraph;

    /**
     * Used for variation resolution from the current outside state of content dimensions
     */
    private DimensionSpace\InterDimensionalVariationGraph $interDimensionalVariationGraph;

    /**
     * Used for constraint checks against the current outside configuration state of content dimensions
     */
    private DimensionSpace\ContentDimensionZookeeper $contentDimensionZookeeper;

    /**
     * Used for publishing events
     */
    private NodeAggregateEventPublisher $nodeEventPublisher;

    private ReadSideMemoryCacheManager $readSideMemoryCacheManager;

    protected PropertyConverter $propertyConverter;

    /**
     * can be disabled in {@see NodeAggregateCommandHandler::withoutAnchestorNodeTypeConstraintChecks()}
     */
    private bool $ancestorNodeTypeConstraintChecksEnabled = true;

    private RuntimeBlocker $runtimeBlocker;

    public function __construct(
        ContentStreamRepository $contentStreamRepository,
        NodeTypeManager $nodeTypeManager,
        DimensionSpace\ContentDimensionZookeeper $contentDimensionZookeeper,
        ContentGraphInterface $contentGraph,
        DimensionSpace\InterDimensionalVariationGraph $interDimensionalVariationGraph,
        NodeAggregateEventPublisher $nodeEventPublisher,
        ReadSideMemoryCacheManager $readSideMemoryCacheManager,
        RuntimeBlocker $runtimeBlocker,
        PropertyConverter $propertyConverter
    ) {
        $this->contentStreamRepository = $contentStreamRepository;
        $this->nodeTypeManager = $nodeTypeManager;
        $this->contentDimensionZookeeper = $contentDimensionZookeeper;
        $this->contentGraph = $contentGraph;
        $this->interDimensionalVariationGraph = $interDimensionalVariationGraph;
        $this->nodeEventPublisher = $nodeEventPublisher;
        $this->readSideMemoryCacheManager = $readSideMemoryCacheManager;
        $this->runtimeBlocker = $runtimeBlocker;
        $this->propertyConverter = $propertyConverter;
    }

    protected function getContentGraph(): ContentGraphInterface
    {
        return $this->contentGraph;
    }

    protected function getContentStreamRepository(): ContentStreamRepository
    {
        return $this->contentStreamRepository;
    }

    protected function getNodeTypeManager(): NodeTypeManager
    {
        return $this->nodeTypeManager;
    }

    protected function getReadSideMemoryCacheManager(): ReadSideMemoryCacheManager
    {
        return $this->readSideMemoryCacheManager;
    }

    protected function getNodeAggregateEventPublisher(): NodeAggregateEventPublisher
    {
        return $this->nodeEventPublisher;
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

    public function getRuntimeBlocker(): RuntimeBlocker
    {
        return $this->runtimeBlocker;
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
    protected function checkConstraintsImposedByAncestors(ChangeNodeAggregateType $command): void
    {
        $nodeAggregate = $this->requireProjectedNodeAggregate(
            $command->getContentStreamIdentifier(),
            $command->getNodeAggregateIdentifier()
        );
        $newNodeType = $this->requireNodeType($command->getNewNodeTypeName());
        foreach ($this->contentGraph->findParentNodeAggregates(
            $command->getContentStreamIdentifier(),
            $command->getNodeAggregateIdentifier()
        ) as $parentAggregate) {
            $parentsNodeType = $this->nodeTypeManager->getNodeType((string)$parentAggregate->getNodeTypeName());
            if (!$parentsNodeType->allowsChildNodeType($newNodeType)) {
                throw new NodeConstraintException(
                    'Node type ' . $command->getNewNodeTypeName()
                        . ' is not allowed below nodes of type ' . $parentAggregate->getNodeTypeName()
                );
            }
            if ($nodeAggregate->getNodeName()
                && $parentsNodeType->hasAutoCreatedChildNode($nodeAggregate->getNodeName())
                && $parentsNodeType->getTypeOfAutoCreatedChildNode($nodeAggregate->getNodeName())?->getName()
                    !== (string)$command->getNewNodeTypeName()
            ) {
                throw new NodeConstraintException(
                    'Cannot change type of auto created child node' . $nodeAggregate->getNodeName()
                        . ' to ' . $command->getNewNodeTypeName()
                );
            }
            foreach ($this->contentGraph->findParentNodeAggregates(
                $command->getContentStreamIdentifier(),
                $parentAggregate->getIdentifier()
            ) as $grandParentAggregate) {
                $grandParentsNodeType = $this->nodeTypeManager->getNodeType(
                    (string)$grandParentAggregate->getNodeTypeName()
                );
                if ($parentAggregate->getNodeName()
                    && $grandParentsNodeType->hasAutoCreatedChildNode($parentAggregate->getNodeName())
                    && !$grandParentsNodeType->allowsGrandchildNodeType(
                        (string) $parentAggregate->getNodeName(),
                        $newNodeType
                    )
                ) {
                    throw new NodeConstraintException(
                        'Node type "' . $command->getNewNodeTypeName()
                            . '" is not allowed below auto created child nodes "' . $parentAggregate->getNodeName()
                            . '" of nodes of type "' . $grandParentAggregate->getNodeTypeName() . '"',
                        1520011791
                    );
                }
            }
        }
    }

    /**
     * @todo perhaps reuse when ChangeNodeAggregateType is reimplemented
     *
     * @throws NodeConstraintException
     * @throws NodeTypeNotFoundException
     */
    protected function checkConstraintsImposedOnAlreadyPresentDescendants(ChangeNodeAggregateType $command): void
    {
        $newNodeType = $this->nodeTypeManager->getNodeType((string)$command->getNewNodeTypeName());

        foreach ($this->contentGraph->findChildNodeAggregates(
            $command->getContentStreamIdentifier(),
            $command->getNodeAggregateIdentifier()
        ) as $childAggregate) {
            $childsNodeType = $this->nodeTypeManager->getNodeType((string)$childAggregate->getNodeTypeName());
            if (!$newNodeType->allowsChildNodeType($childsNodeType)) {
                if (!$command->getStrategy()) {
                    throw new NodeConstraintException(
                        'Node type ' . $command->getNewNodeTypeName()
                            . ' does not allow children of type  ' . $childAggregate->getNodeTypeName()
                            . ', which already exist. Please choose a resolution strategy.',
                        1520014467
                    );
                }
            }

            if ($childAggregate->getNodeName() && $newNodeType->hasAutoCreatedChildNode(
                $childAggregate->getNodeName()
            )) {
                foreach ($this->contentGraph->findChildNodeAggregates(
                    $command->getContentStreamIdentifier(),
                    $childAggregate->getIdentifier()
                ) as $grandChildAggregate) {
                    $grandChildsNodeType = $this->nodeTypeManager->getNodeType(
                        (string)$grandChildAggregate->getNodeTypeName()
                    );
                    if (!$newNodeType->allowsGrandchildNodeType(
                        (string)$childAggregate->getNodeName(),
                        $grandChildsNodeType
                    )) {
                        if (!$command->getStrategy()) {
                            throw new NodeConstraintException(
                                'Node type ' . $command->getNewNodeTypeName()
                                    . ' does not allow auto created child nodes "' . $childAggregate->getNodeName()
                                    . '" to have children of type  ' . $grandChildAggregate->getNodeTypeName()
                                    . ', which already exist. Please choose a resolution strategy.',
                                1520151998
                            );
                        }
                    }
                }
            }
        }
    }
}
