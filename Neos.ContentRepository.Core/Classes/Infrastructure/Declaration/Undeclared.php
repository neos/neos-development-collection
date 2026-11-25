<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Core\Infrastructure\Declaration;

/**
 * Marker artifact for undeclared members in declarations
 *
 * Implemented as an enum for sameness comparison
 *
 * @api to be used in custom declarations
 */
enum Undeclared
{
    case INSTANCE;
}
