<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Core\EventStore;

/**
 * Common interface for all Content Repository "domain events"
 *
 * @api
 */
interface EventInterface extends \JsonSerializable
{
    /**
     * @param array<string,mixed> $values
     */
    public static function fromArray(array $values): EventInterface;

    /**
     * @return array<string,mixed>
     */
    public function jsonSerialize(): array;
}
