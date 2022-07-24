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

use Neos\ContentRepository\CommandHandler\CommandInterface;
use Neos\ContentRepository\EventStore\Events;

/**
 * Ensure all invariants are held for Node-based events:
 */
final class NodeAggregateEventPublisher
{

    /**
     * @throws \Neos\Flow\Property\Exception
     * @throws \Neos\Flow\Security\Exception
     */
    public static function enrichWithCommand(
        CommandInterface $command,
        Events $events,
    ): Events {
        $processedEvents = [];
        $causationIdentifier = null;
        $i = 0;
        foreach ($events as $event) {
            if ($event instanceof \Neos\ContentRepository\EventStore\DecoratedEvent) {
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
                $commandPayload = $command->jsonSerialize();

                if (!isset($commandPayload['contentStreamIdentifier'])) {
                    throw new \RuntimeException(sprintf(
                        'TODO: Command %s does not have a property "contentStreamIdentifier" (which is required).',
                        get_class($command)
                    ));
                }
                $metadata = [
                    'commandClass' => get_class($command),
                    'commandPayload' => $commandPayload
                ];
                $event = \Neos\ContentRepository\EventStore\DecoratedEvent::withMetadata($event, $metadata);
                // we remember the 1st event's identifier as causation identifier for all the others
                $causationIdentifier = $event->eventId;
            } else {
                // event 2,3,4,...n get a causation identifier set, as they all originate from the 1st event.
                if ($causationIdentifier !== null) {
                    $event = \Neos\ContentRepository\EventStore\DecoratedEvent::withCausationIdentifier($event, $causationIdentifier);
                }
            }
            $processedEvents[] = $event;
            $i++;
        }


        return Events::fromArray($processedEvents);
    }
}
