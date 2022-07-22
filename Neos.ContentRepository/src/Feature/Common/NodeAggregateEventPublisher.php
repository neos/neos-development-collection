<?php
declare(strict_types=1);
namespace Neos\ContentRepository\Feature\Common;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\ContentRepository\Feature\Common\PublishableToOtherContentStreamsInterface;
use Neos\EventSourcing\Event\DecoratedEvent;
use Neos\ContentRepository\EventStore\EventInterface;
use Neos\EventSourcing\Event\DomainEvents;
use Neos\EventSourcing\EventStore\EventStore;
use Neos\EventSourcing\EventStore\ExpectedVersion;
use Neos\EventSourcing\EventStore\StreamName;
use Neos\Flow\Annotations as Flow;

/**
 * Ensure all invariants are held for Node-based events:
 *
 * - all node events need to implement PublishableToOtherContentStreamsInterface; otherwise
 *   they could not be published to the live workspace.
 * - the first event gets a metadata "commandClass" and "commandPayload"; so it can be rebased.
 *
 * @Flow\Scope("singleton")
 */
final class NodeAggregateEventPublisher
{
    private EventStore $eventStore;

    /**
     * safeguard that the "withCommand()" method is never called recursively.
     */
    private bool $currentlyInCommandClosure = false;

    private ?\JsonSerializable $command = null;

    public function __construct(EventStore $eventStore)
    {
        $this->eventStore = $eventStore;
    }

    public function withCommand(\JsonSerializable $command, callable $callback): mixed
    {
        if ($this->currentlyInCommandClosure) {
            throw new \RuntimeException('TODO: withCommand() is not allowed to be called recursively!');
        }
        $this->command = $command;
        $this->currentlyInCommandClosure = true;
        try {
            $result = $callback();
            /** @var ?\JsonSerializable $commandAfterCallback */
            $commandAfterCallback = $this->command;
            if (!is_null($commandAfterCallback)) {
                // if command has not been reset, we know that publish() or publishMany() has never been called.
                // Thus, we need to throw an exception; as we are not allowed to loose information.

                throw new \RuntimeException(sprintf(
                    'TODO: Command %s did not lead to the creation of events',
                    get_class($command)
                ));
            }
            return $result;
        } finally {
            $this->currentlyInCommandClosure = false;
        }
    }

    /**
     * @throws \Neos\Flow\Property\Exception
     * @throws \Neos\Flow\Security\Exception
     */
    public function publish(
        StreamName $streamName,
        DomainEventInterface $event,
        int $expectedVersion = ExpectedVersion::ANY
    ): void {
        $this->publishMany($streamName, DomainEvents::withSingleEvent($event), $expectedVersion);
    }

    /**
     * @throws \Neos\Flow\Property\Exception
     * @throws \Neos\Flow\Security\Exception
     */
    public function publishMany(
        StreamName $streamName,
        DomainEvents $events,
        int $expectedVersion = ExpectedVersion::ANY
    ): void {
        if (count($events) === 0) {
            throw new \RuntimeException('TODO: publishMany() must be called with at least one event');
        }
        $processedEvents = DomainEvents::createEmpty();
        $causationIdentifier = null;
        foreach ($events as $event) {
            if ($event instanceof DecoratedEvent) {
                $undecoratedEvent = $event->getWrappedEvent();
                if (!$undecoratedEvent instanceof PublishableToOtherContentStreamsInterface) {
                    throw new \RuntimeException(sprintf(
                        'TODO: Event %s has to implement PublishableToOtherContentStreamsInterface',
                        get_class($event)
                    ));
                }
            } else {
                throw new \RuntimeException(sprintf(
                    'TODO: You need to use DecoratedEvent, given: %s',
                    get_class($event)
                ));
            }

            if ($this->command) {
                $commandPayload = $this->command->jsonSerialize();

                if (!isset($commandPayload['contentStreamIdentifier'])) {
                    throw new \RuntimeException(sprintf(
                        'TODO: Command %s does not have a property "contentStreamIdentifier" (which is required).',
                        get_class($this->command)
                    ));
                }
                $metadata = [
                    'commandClass' => get_class($this->command),
                    'commandPayload' => $commandPayload
                ];
                $event = DecoratedEvent::addMetadata($event, $metadata);
                // we remember the 1st event's identifier as causation identifier for all the others
                $causationIdentifier = $event->getIdentifier();
                $this->command = null;
            } else {
                // event 2,3,4,...n get a causation identifier set, as they all originate from the 1st event.
                if ($causationIdentifier !== null) {
                    $event = DecoratedEvent::addCausationIdentifier($event, $causationIdentifier);
                }
            }
            $processedEvents = $processedEvents->appendEvent($event);
        }

        $this->eventStore->commit($streamName, $processedEvents, $expectedVersion);
    }
}
