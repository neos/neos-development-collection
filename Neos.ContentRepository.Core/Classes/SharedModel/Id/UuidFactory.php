<?php

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

declare(strict_types=1);

namespace Neos\ContentRepository\Core\SharedModel\Id;

use Ramsey\Uuid\Uuid;

/**
 * A factory for UUIDs
 *
 * @api
 */
final class UuidFactory
{
    public static function create(): string
    {
        return is_callable('uuid_create')
            ? strtolower(uuid_create(UUID_TYPE_RANDOM))
            /** @phpstan-ignore-next-line not always true */
            : Uuid::uuid4()->toString();
    }
}
