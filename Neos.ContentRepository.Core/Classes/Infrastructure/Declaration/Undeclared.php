<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Core\Infrastructure\Declaration;

/**
 * Marker artifact for undeclared members in declarations
 *
 * Implemented as an enum for sameness comparison
 *
 * @internal
 */
enum Undeclared
{
    case INSTANCE;
}
