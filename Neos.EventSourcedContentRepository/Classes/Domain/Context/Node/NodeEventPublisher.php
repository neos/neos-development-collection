<?php
namespace Neos\EventSourcedContentRepository\Domain\Context\Node;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\EventSourcedContentRepository\Domain\Context\Node\Event\CopyableAcrossContentStreamsInterface;
use Neos\EventSourcing\Event\Decorator\EventWithMetadata;
use Neos\EventSourcing\Event\DomainEventInterface;
use Neos\EventSourcing\Event\DomainEvents;
use Neos\EventSourcing\EventStore\EventStoreManager;
use Neos\EventSourcing\EventStore\ExpectedVersion;
use Neos\EventSourcing\EventStore\StreamName;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Property\PropertyMapper;
use Neos\Flow\Property\PropertyMappingConfiguration;

/**
 * Ensure all invariants are held for Node-based events:
 *
 * - all node events need to implement CopyableAcrossContentStreamsInterface; otherwise
 *   they could not be published to the live workspace.
 * - the first event gets a metadata "commandClass" and "commandPayload"; so it can be rebased.
 *
 * @Flow\Scope("singleton")
 */
final class NodeEventPublisher
{

    /**
     * @Flow\Inject
     * @var EventStoreManager
     */
    protected $eventStoreManager;

    /**
     * @Flow\Inject
     * @var PropertyMapper
     */
    protected $propertyMapper;

    /**
     * safeguard that the "withCommand()" method is never called recursively.
     * @var bool
     */
    private $currentlyInCommandClosure = false;

    private $command;

    /**
     * @param $command
     * @param $callback
     * @return mixed
     */
    public function withCommand($command, $callback)
    {
        if ($this->currentlyInCommandClosure) {
            throw new \RuntimeException('TODO: withCommand() is not allowed to be called recursively!');
        }

        if (!$command) {
            throw new \RuntimeException('TODO: withCommand() has to have a command passed in');
        }
        $this->command = $command;

        $this->currentlyInCommandClosure = true;
        try {
            $result = $callback();
            if ($this->command !== null) {
                // if command has not been reset, we know that publish() or publishMany() has never been called.
                // Thus, we need to throw an exception; as we are not allowed to loose information.

                throw new \RuntimeException(sprintf('TODO: Command %s did not lead to the creation of events', get_class($command)));
            }
            return $result;
        } finally {
            $this->currentlyInCommandClosure = false;
        }
    }

    /**
     * @param StreamName $streamName
     * @param DomainEventInterface $event
     * @param int $expectedVersion
     * @throws \Neos\Flow\Property\Exception
     * @throws \Neos\Flow\Security\Exception
     */
    public function publish(StreamName $streamName, DomainEventInterface $event, int $expectedVersion = ExpectedVersion::ANY): void
    {
        $this->publishMany($streamName, DomainEvents::withSingleEvent($event), $expectedVersion);
    }

    /**
     * @param StreamName $streamName
     * @param DomainEvents $events
     * @param int $expectedVersion
     * @throws \Neos\Flow\Property\Exception
     * @throws \Neos\Flow\Security\Exception
     */
    public function publishMany(StreamName $streamName, DomainEvents $events, int $expectedVersion = ExpectedVersion::ANY): void
    {
        if (count($events) === 0) {
            throw new \RuntimeException('TODO: publishMany() must be called with at least one event');
        }
        $processedEvents = DomainEvents::createEmpty();
        foreach ($events as $event) {
            if (!($event instanceof CopyableAcrossContentStreamsInterface)) {
                throw new \RuntimeException(sprintf('TODO: Event %s has to implement CopyableAcrossContentStreamsInterface', get_class($event)));
            }

            if ($this->command) {
                $commandPayload = $this->propertyMapper->convert($this->command, 'array');

                if (!isset($commandPayload['contentStreamIdentifier'])) {
                    throw new \RuntimeException(sprintf('TODO: Command %s does not have a property "contentStreamIdentifier" (which is required).', get_class($this->command)));
                }
                $metadata = [
                    'commandClass' => get_class($this->command),
                    'commandPayload' => $commandPayload
                ];
                $event = new EventWithMetadata($event, $metadata);
                $this->command = null;
            }
            $processedEvents = $processedEvents->appendEvent($event);
        }

        $eventStore = $this->eventStoreManager->getEventStoreForStreamName($streamName);
        $eventStore->commit($streamName, $processedEvents, $expectedVersion);
    }
}
