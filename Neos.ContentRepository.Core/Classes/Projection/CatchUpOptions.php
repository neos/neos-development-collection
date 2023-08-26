<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Core\Projection;

use Neos\ContentRepository\Core\ContentRepository;
use Neos\EventStore\Model\Event\SequenceNumber;

/**
 * Options for {@see ContentRepository::catchUpProjection()}
 *
 * @api *NOTE:** The signature of the {@see create()} and {@see with()} methods might be extended in the future, so they should only ever be used with named arguments
 */
final class CatchUpOptions
{
    /**
     * @param SequenceNumber|null $maximumSequenceNumber If specified the catch-up will stop at the specified {@see SequenceNumber}
     */
    private function __construct(
        public readonly ?SequenceNumber $maximumSequenceNumber,
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
    ): self {
        if (is_int($maximumSequenceNumber)) {
            $maximumSequenceNumber = SequenceNumber::fromInteger($maximumSequenceNumber);
        }
        return new self($maximumSequenceNumber);
    }


    /**
     * Returns a new instance with the specified additional options
     *
     * Note: The signature of this method might be extended in the future, so it should always be used with named arguments
     * @see https://www.php.net/manual/en/functions.arguments.php#functions.named-arguments
     */
    public function with(
        SequenceNumber|int|null $maximumSequenceNumber = null,
    ): self {
        return self::create(
            $maximumSequenceNumber ?? $this->maximumSequenceNumber,
        );
    }
}
