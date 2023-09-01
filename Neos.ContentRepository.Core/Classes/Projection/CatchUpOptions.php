<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Core\Projection;

use Closure;
use Neos\ContentRepository\Core\ContentRepository;
use Neos\ContentRepository\Core\EventStore\EventInterface;
use Neos\EventStore\Model\Event\SequenceNumber;
use Neos\EventStore\Model\EventEnvelope;

/**
 * Options for {@see ContentRepository::catchUpProjection()}
 *
 * @api *NOTE:** The signature of the {@see create()} and {@see with()} methods might be extended in the future, so they should only ever be used with named arguments
 */
final class CatchUpOptions
{
    /**
     * @param SequenceNumber|null $maximumSequenceNumber If specified the catch-up will stop at the specified {@see SequenceNumber}
     * @param Closure(EventInterface $event, EventEnvelope $eventEnvelope): void|null $progressCallback If specified the given closure will be invoked for every event with the current {@see EventInterface} and {@see EventEnvelope} passed as arguments
     */
    private function __construct(
        public readonly ?SequenceNumber $maximumSequenceNumber,
        public readonly ?Closure $progressCallback,
    ) {
    }

    /**
     * Creates an instance for the specified options
     *
     * Note: The signature of this method might be extended in the future, so it should always be used with named arguments
     * @see https://www.php.net/manual/en/functions.arguments.php#functions.named-arguments
     */
    public static function create(
        SequenceNumber|int|null $maximumSequenceNumber = null,
        Closure|null $progressCallback = null,
    ): self {
        if (is_int($maximumSequenceNumber)) {
            $maximumSequenceNumber = SequenceNumber::fromInteger($maximumSequenceNumber);
        }
        return new self($maximumSequenceNumber, $progressCallback);
    }


    /**
     * Returns a new instance with the specified additional options
     *
     * Note: The signature of this method might be extended in the future, so it should always be used with named arguments
     * @see https://www.php.net/manual/en/functions.arguments.php#functions.named-arguments
     */
    public function with(
        SequenceNumber|int|null $maximumSequenceNumber = null,
        Closure|null $progressCallback = null,
    ): self {
        return self::create(
            $maximumSequenceNumber ?? $this->maximumSequenceNumber,
            $progressCallback ?? $this->progressCallback,
        );
    }
}
