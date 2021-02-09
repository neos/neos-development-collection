<?php
declare(strict_types=1);
namespace Neos\EventSourcedContentRepository\Domain\Context\DimensionSpace;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\EventSourcedContentRepository\Domain\Context\ContentStream\ContentStreamEventStreamName;
use Neos\Flow\Annotations as Flow;
use Neos\EventSourcedContentRepository\Domain\ValueObject\CommandResult;
use Neos\EventSourcedContentRepository\Service\Infrastructure\ReadSideMemoryCacheManager;
use Neos\EventSourcing\Event\DecoratedEvent;
use Neos\EventSourcing\Event\DomainEvents;
use Neos\EventSourcing\EventStore\EventStore;
use Ramsey\Uuid\Uuid;

/**
 * @Flow\Scope("singleton")
 * ContentStreamCommandHandler
 */
final class DimensionSpaceCommandHandler
{

    /**
     * @var EventStore
     */
    protected $eventStore;

    /**
     * @var ReadSideMemoryCacheManager
     */
    protected $readSideMemoryCacheManager;


    public function __construct(EventStore $eventStore, ReadSideMemoryCacheManager $readSideMemoryCacheManager)
    {
        $this->eventStore = $eventStore;
        $this->readSideMemoryCacheManager = $readSideMemoryCacheManager;
    }


    /**
     * @param Command\MoveDimensionSpacePoint $command
     * @return CommandResult
     */
    public function handleMoveDimensionSpacePoint(Command\MoveDimensionSpacePoint $command): CommandResult
    {
        $this->readSideMemoryCacheManager->disableCache();
        $streamName = ContentStreamEventStreamName::fromContentStreamIdentifier($command->getContentStreamIdentifier())->getEventStreamName();



        // TODO: check constraints

        $events = DomainEvents::withSingleEvent(
            DecoratedEvent::addIdentifier(
                new Event\DimensionSpacePointWasMoved(
                    $command->getContentStreamIdentifier(),
                    $command->getSource(),
                    $command->getTarget()
                ),
                Uuid::uuid4()->toString()
            )
        );
        $this->eventStore->commit($streamName, $events);
        return CommandResult::fromPublishedEvents($events);
    }
}
