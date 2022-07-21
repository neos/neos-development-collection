<?php
declare(strict_types=1);
namespace Neos\ContentRepositoryRegistry\Legacy;

/*
 * This file is part of the Neos.EventStore package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\ContentGraph\DoctrineDbalAdapter\Domain\Projection\GraphProjector;
use Neos\ContentGraph\PostgreSQLAdapter\Domain\Projection\HypergraphProjector;
use Neos\ContentRepository\Feature\ContentStreamCreation\Event\ContentStreamWasCreated;
use Neos\ContentRepository\Feature\ContentStreamForking\Event\ContentStreamWasForked;
use Neos\ContentRepository\Feature\ContentStreamRemoval\Event\ContentStreamWasRemoved;
use Neos\ContentRepository\Feature\DimensionSpaceAdjustment\Event\DimensionShineThroughWasAdded;
use Neos\ContentRepository\Feature\DimensionSpaceAdjustment\Event\DimensionSpacePointWasMoved;
use Neos\ContentRepository\Feature\NodeCreation\Event\NodeAggregateWithNodeWasCreated;
use Neos\ContentRepository\Feature\NodeDisabling\Event\NodeAggregateWasDisabled;
use Neos\ContentRepository\Feature\NodeDisabling\Event\NodeAggregateWasEnabled;
use Neos\ContentRepository\Feature\NodeModification\Event\NodePropertiesWereSet;
use Neos\ContentRepository\Feature\NodeMove\Event\NodeAggregateWasMoved;
use Neos\ContentRepository\Feature\NodeReferencing\Event\NodeReferencesWereSet;
use Neos\ContentRepository\Feature\NodeRemoval\Event\NodeAggregateWasRemoved;
use Neos\ContentRepository\Feature\NodeRenaming\Event\NodeAggregateNameWasChanged;
use Neos\ContentRepository\Feature\NodeTypeChange\Event\NodeAggregateTypeWasChanged;
use Neos\ContentRepository\Feature\NodeVariation\Event\NodeGeneralizationVariantWasCreated;
use Neos\ContentRepository\Feature\NodeVariation\Event\NodePeerVariantWasCreated;
use Neos\ContentRepository\Feature\NodeVariation\Event\NodeSpecializationVariantWasCreated;
use Neos\ContentRepository\Feature\RootNodeCreation\Event\RootNodeAggregateWithNodeWasCreated;
use Neos\ContentRepository\Feature\WorkspaceCreation\Event\RootWorkspaceWasCreated;
use Neos\ContentRepository\Feature\WorkspaceCreation\Event\WorkspaceWasCreated;
use Neos\ContentRepository\Feature\WorkspaceDiscarding\Event\WorkspaceWasDiscarded;
use Neos\ContentRepository\Feature\WorkspaceDiscarding\Event\WorkspaceWasPartiallyDiscarded;
use Neos\ContentRepository\Feature\WorkspacePublication\Event\WorkspaceWasPartiallyPublished;
use Neos\ContentRepository\Feature\WorkspacePublication\Event\WorkspaceWasPublished;
use Neos\ContentRepository\Feature\WorkspaceRebase\Event\WorkspaceRebaseFailed;
use Neos\ContentRepository\Feature\WorkspaceRebase\Event\WorkspaceWasRebased;
use Neos\ContentRepository\Projection\Changes\ChangeProjector;
use Neos\ContentRepository\Projection\ContentStream\ContentStreamProjector;
use Neos\ContentRepository\Projection\NodeHiddenState\NodeHiddenStateProjector;
use Neos\ContentRepository\Projection\Workspace\WorkspaceProjector;
use Neos\EventSourcing\EventListener\Mapping\EventToListenerMapping;
use Neos\EventSourcing\EventListener\Mapping\EventToListenerMappings;
use Neos\EventSourcing\EventPublisher\DeferEventPublisher;
use Neos\EventSourcing\EventPublisher\EventPublisherFactoryInterface;
use Neos\EventSourcing\EventPublisher\EventPublisherInterface;
use Neos\EventSourcing\EventPublisher\JobQueueEventPublisher;
use Neos\Flow\Annotations as Flow;
use Neos\Neos\FrontendRouting\Projection\DocumentUriPathProjector;

/**
 *
 * @Flow\Scope("singleton")
 */
final class HardcodedEventPublisherFactory implements EventPublisherFactoryInterface
{

    /**
     * A list of all initialized Event Publisher instances, indexed by the "Event Store identifier"
     *
     * @var EventPublisherInterface[]
     */
    private $eventPublisherInstances;
    private EventToListenerMappings $mappings;

    public function __construct()
    {
        $this->mappings = EventToListenerMappings::fromArray([
            // ChangeProjector
            EventToListenerMapping::create(NodeAggregateWasMoved::class, ChangeProjector::class, []),
            EventToListenerMapping::create(NodePropertiesWereSet::class, ChangeProjector::class, []),
            EventToListenerMapping::create(NodeAggregateWithNodeWasCreated::class, ChangeProjector::class, []),
            EventToListenerMapping::create(NodeAggregateWasDisabled::class, ChangeProjector::class, []),
            EventToListenerMapping::create(NodeAggregateWasEnabled::class, ChangeProjector::class, []),
            EventToListenerMapping::create(NodeAggregateWasRemoved::class, ChangeProjector::class, []),
            EventToListenerMapping::create(DimensionSpacePointWasMoved::class, ChangeProjector::class, []),

            // ContentStreamProjector
            EventToListenerMapping::create(ContentStreamWasCreated::class, ContentStreamProjector::class, []),
            EventToListenerMapping::create(RootWorkspaceWasCreated::class, ContentStreamProjector::class, []),
            EventToListenerMapping::create(WorkspaceWasCreated::class, ContentStreamProjector::class, []),
            EventToListenerMapping::create(ContentStreamWasForked::class, ContentStreamProjector::class, []),
            EventToListenerMapping::create(WorkspaceWasDiscarded::class, ContentStreamProjector::class, []),
            EventToListenerMapping::create(WorkspaceWasPartiallyDiscarded::class, ContentStreamProjector::class, []),
            EventToListenerMapping::create(WorkspaceWasPartiallyPublished::class, ContentStreamProjector::class, []),
            EventToListenerMapping::create(WorkspaceWasPublished::class, ContentStreamProjector::class, []),
            EventToListenerMapping::create(WorkspaceWasRebased::class, ContentStreamProjector::class, []),
            EventToListenerMapping::create(WorkspaceRebaseFailed::class, ContentStreamProjector::class, []),
            EventToListenerMapping::create(ContentStreamWasRemoved::class, ContentStreamProjector::class, []),

            // NodeHiddenStateProjector
            EventToListenerMapping::create(NodeAggregateWasDisabled::class, NodeHiddenStateProjector::class, []),
            EventToListenerMapping::create(NodeAggregateWasEnabled::class, NodeHiddenStateProjector::class, []),
            EventToListenerMapping::create(ContentStreamWasForked::class, NodeHiddenStateProjector::class, []),
            EventToListenerMapping::create(DimensionSpacePointWasMoved::class, NodeHiddenStateProjector::class, []),

            // WorkspaceProjector
            EventToListenerMapping::create(WorkspaceWasCreated::class, WorkspaceProjector::class, []),
            EventToListenerMapping::create(RootWorkspaceWasCreated::class, WorkspaceProjector::class, []),
            EventToListenerMapping::create(WorkspaceWasDiscarded::class, WorkspaceProjector::class, []),
            EventToListenerMapping::create(WorkspaceWasPartiallyDiscarded::class, WorkspaceProjector::class, []),
            EventToListenerMapping::create(WorkspaceWasPartiallyPublished::class, WorkspaceProjector::class, []),
            EventToListenerMapping::create(WorkspaceWasPublished::class, WorkspaceProjector::class, []),
            EventToListenerMapping::create(WorkspaceWasRebased::class, WorkspaceProjector::class, []),
            EventToListenerMapping::create(WorkspaceRebaseFailed::class, WorkspaceProjector::class, []),

            // DBAL - MYSQL - GraphProjector
            // NodeVariation Trait
            EventToListenerMapping::create(NodeSpecializationVariantWasCreated::class, GraphProjector::class, []),
            EventToListenerMapping::create(NodeGeneralizationVariantWasCreated::class, GraphProjector::class, []),
            EventToListenerMapping::create(NodePeerVariantWasCreated::class, GraphProjector::class, []),
            // NodeDisabling Trait
            EventToListenerMapping::create(NodeAggregateWasDisabled::class, GraphProjector::class, []),
            // RestrictionRelations Trait: no event listeners
            // NodeRemoval Trait
            EventToListenerMapping::create(NodeAggregateWasRemoved::class, GraphProjector::class, []),
            // NodeMove Trait
            EventToListenerMapping::create(NodeAggregateWasMoved::class, GraphProjector::class, []),
            // MAIN GraphProjector
            EventToListenerMapping::create(RootNodeAggregateWithNodeWasCreated::class, GraphProjector::class, []),
            EventToListenerMapping::create(NodeAggregateWithNodeWasCreated::class, GraphProjector::class, []),
            EventToListenerMapping::create(NodeAggregateNameWasChanged::class, GraphProjector::class, []),
            EventToListenerMapping::create(ContentStreamWasForked::class, GraphProjector::class, []),
            EventToListenerMapping::create(ContentStreamWasRemoved::class, GraphProjector::class, []),
            EventToListenerMapping::create(NodePropertiesWereSet::class, GraphProjector::class, []),
            EventToListenerMapping::create(NodeReferencesWereSet::class, GraphProjector::class, []),
            EventToListenerMapping::create(NodeAggregateWasEnabled::class, GraphProjector::class, []),
            EventToListenerMapping::create(NodeAggregateTypeWasChanged::class, GraphProjector::class, []),
            EventToListenerMapping::create(DimensionSpacePointWasMoved::class, GraphProjector::class, []),
            EventToListenerMapping::create(DimensionShineThroughWasAdded::class, GraphProjector::class, []),

            // DBAL - Postgres - GraphProjector
            // ContentStreamForking Trait
            EventToListenerMapping::create(ContentStreamWasForked::class, HypergraphProjector::class, []),
            // NodeCreation Trait
            EventToListenerMapping::create(RootNodeAggregateWithNodeWasCreated::class, HypergraphProjector::class, []),
            EventToListenerMapping::create(NodeAggregateWithNodeWasCreated::class, HypergraphProjector::class, []),
            // NodeDisabling Trait
            EventToListenerMapping::create(NodeAggregateWasDisabled::class, HypergraphProjector::class, []),
            EventToListenerMapping::create(NodeAggregateWasEnabled::class, HypergraphProjector::class, []),
            // NodeModification Trait
            EventToListenerMapping::create(NodePropertiesWereSet::class, HypergraphProjector::class, []),
            // NodeReferencing Trait
            EventToListenerMapping::create(NodeReferencesWereSet::class, HypergraphProjector::class, []),
            // NodeRemoval Trait
            EventToListenerMapping::create(NodeAggregateWasRemoved::class, HypergraphProjector::class, []),
            // NodeRenaming Trait
            EventToListenerMapping::create(NodeAggregateNameWasChanged::class, HypergraphProjector::class, []),
            // NodeTypeChange Trait
            EventToListenerMapping::create(NodeAggregateTypeWasChanged::class, HypergraphProjector::class, []),
            // NodeVariation Trait
            EventToListenerMapping::create(NodeSpecializationVariantWasCreated::class, HypergraphProjector::class, []),
            EventToListenerMapping::create(NodeGeneralizationVariantWasCreated::class, HypergraphProjector::class, []),
            EventToListenerMapping::create(NodePeerVariantWasCreated::class, HypergraphProjector::class, []),

            // DocumentUriPathProjector
            EventToListenerMapping::create(RootWorkspaceWasCreated::class, DocumentUriPathProjector::class, []),
            EventToListenerMapping::create(RootNodeAggregateWithNodeWasCreated::class, DocumentUriPathProjector::class, []),
            EventToListenerMapping::create(NodeAggregateWithNodeWasCreated::class, DocumentUriPathProjector::class, []),
            EventToListenerMapping::create(NodeAggregateTypeWasChanged::class, DocumentUriPathProjector::class, []),
            EventToListenerMapping::create(NodePeerVariantWasCreated::class, DocumentUriPathProjector::class, []),
            EventToListenerMapping::create(NodeGeneralizationVariantWasCreated::class, DocumentUriPathProjector::class, []),
            EventToListenerMapping::create(NodeSpecializationVariantWasCreated::class, DocumentUriPathProjector::class, []),
            EventToListenerMapping::create(NodeAggregateWasDisabled::class, DocumentUriPathProjector::class, []),
            EventToListenerMapping::create(NodeAggregateWasEnabled::class, DocumentUriPathProjector::class, []),
            EventToListenerMapping::create(NodeAggregateWasRemoved::class, DocumentUriPathProjector::class, []),
            EventToListenerMapping::create(NodePropertiesWereSet::class, DocumentUriPathProjector::class, []),
            EventToListenerMapping::create(NodeAggregateWasMoved::class, DocumentUriPathProjector::class, []),
            EventToListenerMapping::create(DimensionSpacePointWasMoved::class, DocumentUriPathProjector::class, []),
            EventToListenerMapping::create(DimensionShineThroughWasAdded::class, DocumentUriPathProjector::class, []),
        ]);
    }

    /**
     * @return EventToListenerMappings
     */
    public function getMappings(): EventToListenerMappings
    {
        return $this->mappings;
    }

    public function create(string $eventStoreIdentifier): DeferEventPublisher
    {
        if (!isset($this->eventPublisherInstances[$eventStoreIdentifier])) {
            $this->eventPublisherInstances[$eventStoreIdentifier] = DeferEventPublisher::forPublisher(new JobQueueEventPublisher($eventStoreIdentifier, $this->mappings));
        }
        return $this->eventPublisherInstances[$eventStoreIdentifier];
    }
}
