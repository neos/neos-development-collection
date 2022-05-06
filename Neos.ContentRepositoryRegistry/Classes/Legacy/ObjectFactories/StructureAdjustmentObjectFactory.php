<?php
declare(strict_types=1);

namespace Neos\ContentRepositoryRegistry\Legacy\ObjectFactories;

/*
 * This file is part of the Neos.EventStore package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\ContentRepository\DimensionSpace\DimensionSpace\InterDimensionalVariationGraph;
use Neos\ContentRepository\Feature\StructureAdjustment\DimensionAdjustment;
use Neos\ContentRepository\Feature\StructureAdjustment\DisallowedChildNodeAdjustment;
use Neos\ContentRepository\Feature\StructureAdjustment\ProjectedNodeIterator;
use Neos\ContentRepository\Feature\StructureAdjustment\PropertyAdjustment;
use Neos\ContentRepository\Feature\StructureAdjustment\StructureAdjustmentService;
use Neos\ContentRepository\Feature\StructureAdjustment\TetheredNodeAdjustments;
use Neos\ContentRepository\Feature\StructureAdjustment\UnknownNodeTypeAdjustment;
use Neos\ContentRepository\Infrastructure\Projection\RuntimeBlocker;
use Neos\ContentRepository\Projection\Content\ContentGraphInterface;
use Neos\ContentRepository\Projection\Workspace\WorkspaceFinder;
use Neos\ContentRepository\Service\Infrastructure\ReadSideMemoryCacheManager;
use Neos\ContentRepository\SharedModel\NodeType\NodeTypeManager;
use Neos\EventSourcing\EventStore\EventStore;
use Neos\Flow\Annotations as Flow;

/**
 * @Flow\Scope("singleton")
 */
final class StructureAdjustmentObjectFactory
{
    public function __construct(
        protected readonly EventStore $eventStore,
        protected readonly ContentGraphInterface $contentGraph,
        protected readonly WorkspaceFinder $workspaceFinder,
        protected readonly NodeTypeManager $nodeTypeManager,
        protected readonly InterDimensionalVariationGraph $interDimensionalVariationGraph,
        protected readonly ReadSideMemoryCacheManager $readSideMemoryCacheManager,
        protected readonly RuntimeBlocker $runtimeBlocker,
    ) {}

    public function buildStructureAdjustmentService()
    {
        $projectedNodeIterator = new ProjectedNodeIterator($this->workspaceFinder, $this->contentGraph);

        return new StructureAdjustmentService(
            $this->contentGraph,
            $this->buildTetheredNodeAdjustments($projectedNodeIterator),
            $this->buildUnknownNodeTypeAdjustment($projectedNodeIterator),
            $this->buildDisallowedChildNodeAdjustment($projectedNodeIterator),
            $this->buildPropertyAdjustment($projectedNodeIterator),
            $this->buildDimensionAdjustment($projectedNodeIterator),
        );
    }

    private function buildTetheredNodeAdjustments(ProjectedNodeIterator $projectedNodeIterator): TetheredNodeAdjustments
    {
        return new TetheredNodeAdjustments(
            $this->eventStore,
            $projectedNodeIterator,
            $this->nodeTypeManager,
            $this->interDimensionalVariationGraph,
            $this->contentGraph,
            $this->readSideMemoryCacheManager,
            $this->runtimeBlocker
        );
    }

    private function buildUnknownNodeTypeAdjustment(ProjectedNodeIterator $projectedNodeIterator): UnknownNodeTypeAdjustment
    {
        return new UnknownNodeTypeAdjustment(
            $this->eventStore,
            $projectedNodeIterator,
            $this->nodeTypeManager,
            $this->readSideMemoryCacheManager,
            $this->runtimeBlocker
        );
    }

    private function buildDisallowedChildNodeAdjustment(ProjectedNodeIterator $projectedNodeIterator): DisallowedChildNodeAdjustment
    {
        return new DisallowedChildNodeAdjustment(
            $this->eventStore,
            $projectedNodeIterator,
            $this->nodeTypeManager,
            $this->contentGraph,
            $this->readSideMemoryCacheManager,
            $this->runtimeBlocker
        );
    }


    private function buildPropertyAdjustment(ProjectedNodeIterator $projectedNodeIterator): PropertyAdjustment
    {
        return new PropertyAdjustment(
            $this->eventStore,
            $projectedNodeIterator,
            $this->nodeTypeManager,
            $this->readSideMemoryCacheManager,
            $this->runtimeBlocker
        );
    }

    private function buildDimensionAdjustment(ProjectedNodeIterator $projectedNodeIterator): DimensionAdjustment
    {
        return new DimensionAdjustment(
            $projectedNodeIterator,
            $this->interDimensionalVariationGraph
        );
    }

}
