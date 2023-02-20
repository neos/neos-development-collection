<?php

/*
 * This file is part of the Neos.ContentRepository.Core package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

declare(strict_types=1);

namespace Neos\ContentRepository\Core\Tests\Behavior\Fixtures;

/**
 * A day of week enumeratoion
 *
 * @see https://schema.org/DayOfWeek
 */
enum DayOfWeek: string
{
    case MONDAY = 'https://schema.org/Monday';
    case TUESDAY = 'https://schema.org/Tuesday';
    case WEDNESDAY = 'https://schema.org/Wednesday';
    case THURSDAY = 'https://schema.org/Thursday';
    case FRIDAY = 'https://schema.org/Friday';
    case SATURDAY = 'https://schema.org/Saturday';
    case SUNDAY = 'https://schema.org/Sunday';
    case PUBLIC_HOLIDAYS = 'https://schema.org/PublicHolidays';
}
