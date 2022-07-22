<?php
declare(strict_types=1);
namespace Neos\ContentRepository\EventStore;

/**
 * Common interface for all Content Repository "domain events"
 */
interface EventInterface extends \JsonSerializable
{
    public static function fromArray(array $values): self;
}
