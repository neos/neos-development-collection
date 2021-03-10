<?php
declare(strict_types=1);
namespace Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate;

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
use Neos\ContentRepository\Exception\NodeConstraintException;
use Neos\ContentRepository\Exception\NodeTypeNotFoundException;
use Neos\EventSourcedContentRepository\Domain\Context\ContentStream;
use Neos\ContentRepository\DimensionSpace\DimensionSpace;
use Neos\EventSourcedContentRepository\Domain\Context\ContentStream\ContentStreamRepository;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\Exception\NodeAggregatesTypeIsAmbiguous;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\Feature\ConstraintChecks;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\Feature\NodeCreation;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\Feature\NodeDisabling;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\Feature\NodeModification;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\Feature\NodeMove;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\Feature\NodeReferencing;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\Feature\NodeRemoval;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\Feature\NodeRenaming;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\Feature\NodeRetyping;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\Feature\NodeVariation;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\Feature\TetheredNodeInternals;
use Neos\EventSourcedContentRepository\Domain\Projection\Content\ContentGraphInterface;
use Neos\ContentRepository\Domain\Service\NodeTypeManager;
use Neos\EventSourcedContentRepository\Service\Infrastructure\ReadSideMemoryCacheManager;

final class NodeAggregateCommandHandler
{
    use ConstraintChecks;
    use NodeCreation;
    use NodeDisabling;
    use NodeModification;
    use NodeMove;
    use NodeReferencing;
    use NodeRemoval;
    use NodeRenaming;
    use NodeRetyping;
    use NodeVariation;
    use TetheredNodeInternals;

    /**
     * @var ContentStream\ContentStreamRepository
     */
    protected $contentStreamRepository;

    /**
     * Used for constraint checks against the current outside configuration state of node types
     *
     * @var NodeTypeManager
     */
    protected $nodeTypeManager;

    /**
     * Used for constraint checks against the current outside configuration state of content dimensions
     *
     * @var DimensionSpacePointSet
     */
    protected $allowedDimensionSubspace;

    /**
     * The graph projection used for soft constraint checks
     *
     * @var ContentGraphInterface
     */
    protected $contentGraph;

    /**
     * Used for variation resolution from the current outside state of content dimensions
     *
     * @var DimensionSpace\InterDimensionalVariationGraph
     */
    protected $interDimensionalVariationGraph;

    /**
     * Used for publishing events
     *
     * @var NodeAggregateEventPublisher
     */
    protected $nodeEventPublisher;

    /**
     * @var ReadSideMemoryCacheManager
     */
    protected $readSideMemoryCacheManager;

    /**
     * can be disabled in {@see NodeAggregateCommandHandler::withoutAnchestorNodeTypeConstraintChecks()}
     * @var bool
     */
    protected $ancestorNodeTypeConstraintChecksEnabled = true;

    public function __construct(
        ContentStream\ContentStreamRepository $contentStreamRepository,
        NodeTypeManager $nodeTypeManager,
        DimensionSpace\ContentDimensionZookeeper $contentDimensionZookeeper,
        ContentGraphInterface $contentGraph,
        DimensionSpace\InterDimensionalVariationGraph $interDimensionalVariationGraph,
        NodeAggregateEventPublisher $nodeEventPublisher,
        ReadSideMemoryCacheManager $readSideMemoryCacheManager
    ) {
        $this->contentStreamRepository = $contentStreamRepository;
        $this->nodeTypeManager = $nodeTypeManager;
        $this->allowedDimensionSubspace = $contentDimensionZookeeper->getAllowedDimensionSubspace();
        $this->contentGraph = $contentGraph;
        $this->interDimensionalVariationGraph = $interDimensionalVariationGraph;
        $this->nodeEventPublisher = $nodeEventPublisher;
        $this->readSideMemoryCacheManager = $readSideMemoryCacheManager;
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
        return $this->allowedDimensionSubspace;
    }

    protected function getInterDimensionalVariationGraph(): DimensionSpace\InterDimensionalVariationGraph
    {
        return $this->interDimensionalVariationGraph;
    }

    protected function areAncestorNodeTypeConstraintChecksEnabled(): bool
    {
        return $this->ancestorNodeTypeConstraintChecksEnabled;
    }

    /**
     * Use this closure to run code with the Anchestor Node Type Checks disabled; e.g.
     * during imports.
     *
     * You can disable this because many old sites have this constraint violated more or less;
     * and it's easy to fix lateron; as it does not touch the fundamental integrity of the CR.
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
     *
     * @param Command\ChangeNodeAggregateType $command
     * @throws NodeConstraintException
     * @throws NodeTypeNotFoundException
     * @throws NodeAggregatesTypeIsAmbiguous
     * @return void
     */
    protected function checkConstraintsImposedByAncestors(Command\ChangeNodeAggregateType $command): void
    {
        $nodeAggregate = $this->contentGraph->findNodeAggregateByIdentifier($command->getContentStreamIdentifier(), $command->getNodeAggregateIdentifier());
        $newNodeType = $this->nodeTypeManager->getNodeType((string)$command->getNewNodeTypeName());
        foreach ($this->contentGraph->findParentNodeAggregates($command->getContentStreamIdentifier(), $command->getNodeAggregateIdentifier()) as $parentAggregate) {
            $parentsNodeType = $this->nodeTypeManager->getNodeType((string)$parentAggregate->getNodeTypeName());
            if (!$parentsNodeType->allowsChildNodeType($newNodeType)) {
                throw new NodeConstraintException('Node type ' . $command->getNewNodeTypeName() . ' is not allowed below nodes of type ' . $parentAggregate->getNodeTypeName());
            }
            if ($nodeAggregate->getNodeName()
                && $parentsNodeType->hasAutoCreatedChildNode($nodeAggregate->getNodeName())
                && $parentsNodeType->getTypeOfAutoCreatedChildNode($nodeAggregate->getNodeName())->getName() !== (string)$command->getNewNodeTypeName()) {
                throw new NodeConstraintException('Cannot change type of auto created child node' . $nodeAggregate->getNodeName() . ' to ' . $command->getNewNodeTypeName());
            }
            foreach ($this->contentGraph->findParentNodeAggregates($command->getContentStreamIdentifier(), $parentAggregate->getIdentifier()) as $grandParentAggregate) {
                $grandParentsNodeType = $this->nodeTypeManager->getNodeType((string)$grandParentAggregate->getNodeTypeName());
                if ($parentAggregate->getNodeName()
                    && $grandParentsNodeType->hasAutoCreatedChildNode($parentAggregate->getNodeName())
                    && !$grandParentsNodeType->allowsGrandchildNodeType((string) $parentAggregate->getNodeName(), $newNodeType)) {
                    throw new NodeConstraintException('Node type "' . $command->getNewNodeTypeName() . '" is not allowed below auto created child nodes "' . $parentAggregate->getNodeName()
                        . '" of nodes of type "' . $grandParentAggregate->getNodeTypeName() . '"', 1520011791);
                }
            }
        }
    }

    /**
     * @todo perhaps reuse when ChangeNodeAggregateType is reimplemented
     *
     * @param Command\ChangeNodeAggregateType $command
     * @throws NodeConstraintException
     * @throws NodeTypeNotFoundException
     * @return \void
     */
    protected function checkConstraintsImposedOnAlreadyPresentDescendants(Command\ChangeNodeAggregateType $command): void
    {
        $newNodeType = $this->nodeTypeManager->getNodeType((string)$command->getNewNodeTypeName());

        foreach ($this->contentGraph->findChildNodeAggregates($command->getContentStreamIdentifier(), $command->getNodeAggregateIdentifier()) as $childAggregate) {
            $childsNodeType = $this->nodeTypeManager->getNodeType((string)$childAggregate->getNodeTypeName());
            if (!$newNodeType->allowsChildNodeType($childsNodeType)) {
                if (!$command->getStrategy()) {
                    throw new NodeConstraintException('Node type ' . $command->getNewNodeTypeName() . ' does not allow children of type  ' . $childAggregate->getNodeTypeName()
                        . ', which already exist. Please choose a resolution strategy.', 1520014467);
                }
            }

            if ($childAggregate->getNodeName() && $newNodeType->hasAutoCreatedChildNode($childAggregate->getNodeName())) {
                foreach ($this->contentGraph->findChildNodeAggregates($command->getContentStreamIdentifier(), $childAggregate->getIdentifier()) as $grandChildAggregate) {
                    $grandChildsNodeType = $this->nodeTypeManager->getNodeType((string)$grandChildAggregate->getNodeTypeName());
                    if ($childAggregate->getNodeName() && !$newNodeType->allowsGrandchildNodeType((string)$childAggregate->getNodeName(), $grandChildsNodeType)) {
                        if (!$command->getStrategy()) {
                            throw new NodeConstraintException('Node type ' . $command->getNewNodeTypeName() . ' does not allow auto created child nodes "' . $childAggregate->getNodeName()
                                . '" to have children of type  ' . $grandChildAggregate->getNodeTypeName() . ', which already exist. Please choose a resolution strategy.', 1520151998);
                        }
                    }
                }
            }
        }
    }
}
