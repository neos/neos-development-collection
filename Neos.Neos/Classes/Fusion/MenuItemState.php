<?php

declare(strict_types=1);

namespace Neos\Neos\Fusion;

/**
 * The menu item state value object
 */
enum MenuItemState: string
{
    case NORMAL = 'normal';
    case CURRENT = 'current';
    case ACTIVE = 'active';
    case ABSENT = 'absent';
}
