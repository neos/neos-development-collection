<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Core\Feature\NodeModification\Dto;

/**
 * If a property is set to NULL, this means the key should be unset,
 * because we treat NULL and "not set" the same from an API perspective.
 *
 * The properties in the event log will still show the key being set to null,
 * but the projections should ignore the key.
 *
 * @api used as part of commands/events
 */
final class UnsetPropertyValue
{
    private static self $instance;

    private function __construct()
    {
    }

    public static function get(): self
    {
        return self::$instance ??= new self();
    }
}
