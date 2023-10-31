<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Core\Feature\Common;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\ContentRepository\Core\CommandHandler\CommandInterface;
use Neos\ContentRepository\Core\EventStore\DecoratedEvent;
use Neos\ContentRepository\Core\EventStore\Events;
use Neos\EventStore\Model\Event\EventMetadata;

/**
 * Stores the command in the event's metadata for events on a content stream. This is an important prerequisite
 * for the rebase functionality-
 *
 * @internal
 */
final class NodeAggregateEventPublisher
{
    public static function enrichWithCommand(
        CommandInterface $command,
        Events $events,
    ): Events {
        $processedEvents = [];
        $causationId = null;
        $i = 0;
        foreach ($events as $event) {
            if ($event instanceof DecoratedEvent) {
                $undecoratedEvent = $event->innerEvent;
                if (!$undecoratedEvent instanceof PublishableToOtherContentStreamsInterface) {
                    throw new \RuntimeException(sprintf(
                        'TODO: Event %s has to implement PublishableToOtherContentStreamsInterface',
                        get_class($event)
                    ));
                }
            } else {
                if (!$event instanceof PublishableToOtherContentStreamsInterface) {
                    throw new \RuntimeException(sprintf(
                        'TODO: Event %s has to implement PublishableToOtherContentStreamsInterface',
                        get_class($event)
                    ));
                }
            }

            if ($i === 0) {
                if (!$command instanceof \JsonSerializable) {
                    throw new \RuntimeException(sprintf(
                        'Command %s must be JSON Serializable to be used with NodeAggregateEventPublisher.',
                        get_class($command)
                    ));
                }
                $commandPayload = $command->jsonSerialize();

                if (!isset($commandPayload['contentStreamId'])) {
                    throw new \RuntimeException(sprintf(
                        'TODO: Command %s does not have a property "contentStreamId" (which is required).',
                        get_class($command)
                    ));
                }
                $metadata = EventMetadata::fromArray([
                    'commandClass' => get_class($command),
                    'commandPayload' => $commandPayload
                ]);
                $event = DecoratedEvent::withMetadata($event, $metadata);
                // we remember the 1st event's identifier as causation identifier for all the others
                $causationId = $event->eventId;
            } else {
                // event 2,3,4,...n get a causation identifier set, as they all originate from the 1st event.
                if ($causationId !== null) {
                    $event = DecoratedEvent::withCausationId($event, $causationId);
                }
            }
            $processedEvents[] = $event;
            $i++;
        }


        return Events::fromArray($processedEvents);
    }
}
