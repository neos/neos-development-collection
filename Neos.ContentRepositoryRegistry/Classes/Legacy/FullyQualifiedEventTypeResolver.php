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
use Neos\ContentRepository\EventStore\EventInterface;
use Neos\EventSourcing\Event\EventTypeResolverInterface;
use Neos\Flow\Annotations as Flow;

/**
 * Workaround until we have short event names.
 * @Flow\Scope("singleton")
 */
final class FullyQualifiedEventTypeResolver implements EventTypeResolverInterface
{

    private array $shortNameToFullNameMapping;
    private array $fullNameToShortNameMapping;


    public function __construct()
    {
        $supportedEvents = [
            // ContentStreamCreation
            ContentStreamWasCreated::class,
            // ContentStreamForking
            ContentStreamWasForked::class,
            // ContentStreamRemoval
            ContentStreamWasRemoved::class,
            // DimensionSpaceAdjustment
            DimensionShineThroughWasAdded::class,
            DimensionSpacePointWasMoved::class,
            // NodeCreation
            NodeAggregateWithNodeWasCreated::class,
            // NodeDisabling
            NodeAggregateWasDisabled::class,
            NodeAggregateWasEnabled::class,
            // NodeModification
            NodePropertiesWereSet::class,
            // NodeMove
            NodeAggregateWasMoved::class,
            // NodeReferencing
            NodeReferencesWereSet::class,
            // NodeRemoval
            NodeAggregateWasRemoved::class,
            // NodeRenaming
            NodeAggregateNameWasChanged::class,
            // NodeTypeChange
            NodeAggregateTypeWasChanged::class,
            // NodeVariation
            NodeGeneralizationVariantWasCreated::class,
            NodePeerVariantWasCreated::class,
            NodeSpecializationVariantWasCreated::class,
            // RootNodeCreation
            RootNodeAggregateWithNodeWasCreated::class,
            // WorkspaceCreation
            RootWorkspaceWasCreated::class,
            WorkspaceWasCreated::class,
            // WorkspaceDiscarding
            WorkspaceWasDiscarded::class,
            WorkspaceWasPartiallyDiscarded::class,
            // WorkspacePublication
            WorkspaceWasPartiallyPublished::class,
            WorkspaceWasPublished::class,
            // WorkspaceRebase
            WorkspaceRebaseFailed::class,
            WorkspaceWasRebased::class
        ];

        $this->shortNameToFullNameMapping = [];
        $this->fullNameToShortNameMapping = [];
        foreach ($supportedEvents as $eventName) {
            $shortName = (new \ReflectionClass($eventName))->getShortName();

            if (isset($this->shortNameToFullNameMapping[$shortName])) {
                throw new \RuntimeException('TODO: The Event short name ' . $shortName . ' was already used.');
            }

            $this->fullNameToShortNameMapping[$eventName] = $shortName;
            $this->shortNameToFullNameMapping[$shortName] = $eventName;
        }
    }

    public function getEventType(DomainEventInterface $event): string
    {
        $fullName = get_class($event);
        if (!isset($this->fullNameToShortNameMapping[$fullName])) {
            throw new \RuntimeException('Event type "' . $fullName . '" not registered');
        }

        return $this->fullNameToShortNameMapping[$fullName];
    }

    public function getEventClassNameByType(string $eventType): string
    {
        if (!isset($this->shortNameToFullNameMapping[$eventType])) {
            throw new \RuntimeException('Event type "' . $eventType . '" not registered (conversion to full name)');
        }

        return $this->shortNameToFullNameMapping[$eventType];
    }
}
